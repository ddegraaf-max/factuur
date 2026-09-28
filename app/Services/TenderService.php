<?php

namespace App\Services;

use App\Mail\TenderMail;
use App\Models\Company;
use App\Models\Quote;
use App\Models\Subcontractor;
use App\Models\TenderRequest;
use App\Models\TenderRound;
use App\Models\WorkPackage;
use App\Support\Audit;
use App\Support\DocumentLocale;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Uitvragen bij onderaannemers: per werkpakket een ronde met een handvol
 * bedrijven, elk met een eigen tokenlink om prijs en beschikbaarheid door te
 * geven; herinneren, vergelijken, gunnen. De eigen verkoopprijs gaat nooit
 * mee in de mail — de calculatie dient alleen ter vergelijking.
 */
class TenderService
{
    /** Na zoveel dagen zonder reactie gaat er één herinnering uit. */
    public const REMIND_AFTER_DAYS = 3;

    /** Standaardbibliotheek voor aan- en verbouw; per bedrijf aan te passen. */
    public const DEFAULT_PACKAGES = [
        ['Schroefpalen & fundering', 'Aantal palen of strekkende meter fundering, sondering aanwezig?, bereikbaarheid achterom, bouwtekening.'],
        ['Grondwerk, riolering & afvoer', 'm³ ontgraven, lengte riolering, aansluitpunt, hemelwaterafvoer, afvoer grond.'],
        ['Sloopwerk & geveldoorbraak (incl. staal)', 'Breedte doorbraak, staalprofiel (HEA/IPE) en lengte, stempelwerk, constructieberekening, afvoer puin.'],
        ['Houtskeletbouw (HSB-wanden)', 'm² wand, hoogte, isolatiewaarde (Rc), kozijnsparingen, dampremmende laag, tekening.'],
        ['Metselwerk', 'm² gevel, steenkeuze en voeg, lateien, spouwisolatie, steiger.'],
        ['Dakconstructie & dakbedekking (bitumen)', 'm² dak, balklaag en hoogte, isolatie, bitumen dakbedekking met onderlaag inclusief randafwerking en hemelwaterafvoer, lichtkoepels.'],
        ['Kozijnen & beglazing', 'Aantal en maten, materiaal (hout/kunststof/aluminium), HR++ of triple, levering én montage.'],
        ['Stukadoorswerk', 'm² wand en plafond, afwerking (glad, spachtelputz), hoeken en dagkanten.'],
        ['Tegelwerk', 'm² vloer en wand, tegelmaat, legpatroon, kitwerk, ondervloer.'],
        ['Elektra (E-installatie)', 'Aantal groepen, wandcontactdozen en schakelaars, verlichting, aansluiting groepenkast, tekening.'],
        ['Loodgieter & verwarming (W-installatie)', 'Radiatoren of vloerverwarming (m²), aansluitingen water en afvoer, cv-ketel of warmtepomp.'],
        ['Schilderwerk', 'm² binnen en buiten, ondergrond, aantal lagen, kleur (RAL).'],
    ];

    /** Voegt de standaardpakketten toe die er nog niet zijn; geeft het aantal nieuwe terug. */
    public function seedDefaultPackages(Company $company): int
    {
        $existing = WorkPackage::withoutGlobalScope('company')->where('company_id', $company->id)
            ->pluck('name')->map(fn ($name) => mb_strtolower($name))->all();
        $order = (int) WorkPackage::withoutGlobalScope('company')->where('company_id', $company->id)->max('sort_order');
        $added = 0;

        foreach (self::DEFAULT_PACKAGES as [$name, $description]) {
            if (in_array(mb_strtolower($name), $existing, true)) {
                continue;
            }
            WorkPackage::create([
                'company_id' => $company->id,
                'name' => $name,
                'description' => $description,
                'sort_order' => ++$order,
            ]);
            $added++;
        }

        return $added;
    }

    /** Pakketten met hun bedrijven, voor de kiezer in het uitvraagvenster. */
    public function packagesForPicker(Company $company): array
    {
        return WorkPackage::withoutGlobalScope('company')->where('company_id', $company->id)
            ->with('subcontractors')
            ->orderBy('sort_order')->orderBy('name')
            ->get()
            ->map(fn (WorkPackage $package) => [
                'id' => $package->id,
                'name' => $package->name,
                'description' => $package->description,
                'subcontractors' => $package->subcontractors->map(fn (Subcontractor $s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'city' => $s->city,
                    'has_email' => filled($s->email),
                ])->values()->all(),
            ])->values()->all();
    }

    /**
     * Opent een ronde: één aanvraag per gekozen bedrijf (alleen bedrijven mét
     * e-mailadres), elk met eigen tokenlink, en meteen de aanvraagmail — met
     * de bijlagen (tekening, bestek) erbij.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function open(Company $company, ?Quote $quote, array $data, array $files = []): TenderRound
    {
        $package = WorkPackage::withoutGlobalScope('company')->where('company_id', $company->id)
            ->findOrFail($data['work_package_id']);
        $subcontractors = Subcontractor::withoutGlobalScope('company')->where('company_id', $company->id)
            ->whereIn('id', $data['subcontractor_ids'] ?? [])
            ->whereNotNull('email')
            ->orderBy('name')
            ->get();

        if ($subcontractors->isEmpty()) {
            throw new \DomainException(__('Kies minstens één bedrijf met een e-mailadres.'));
        }

        $round = DB::transaction(function () use ($company, $quote, $package, $subcontractors, $data, $files) {
            $round = TenderRound::create([
                'company_id' => $company->id,
                'quote_id' => $quote?->id,
                'work_package_id' => $package->id,
                'title' => trim((string) ($data['title'] ?? '')) ?: $package->name,
                'description' => $data['description'] ?? null,
                'location' => $data['location'] ?? null,
                'start_week' => $data['start_week'] ?? null,
                'deadline' => $data['deadline'],
                'budget' => $data['budget'] ?? null,
                'status' => 'open',
            ]);

            foreach ($subcontractors as $subcontractor) {
                $round->requests()->create([
                    'subcontractor_id' => $subcontractor->id,
                    'token' => bin2hex(random_bytes(32)),
                    'status' => 'sent',
                ]);
            }

            // Vóór het mailen, zodat de bijlagen meteen meegaan.
            $this->attach($round, $files);

            return $round;
        });

        foreach ($round->requests()->with(['round', 'subcontractor'])->get() as $request) {
            if ($this->mail($request, 'request')) {
                $request->forceFill(['sent_at' => now()])->save();
            }
        }

        Audit::log('created', $round, __(':label geopend: :count bedrijven aangeschreven', [
            'label' => Audit::label($round), 'count' => $subcontractors->count(),
        ]), [], $company->id);

        return $round->fresh(['requests.subcontractor', 'workPackage']);
    }

    /**
     * Bijlagen bij de uitvraag: gaan mee met de mail en staan op de
     * reactiepagina van elk bedrijf. Geeft het aantal toegevoegde bestanden terug.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function attach(TenderRound $round, array $files): int
    {
        foreach ($files as $file) {
            $round->attachments()->create([
                'company_id' => $round->company_id,
                'filename' => mb_substr($file->getClientOriginalName(), 0, 255),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size_bytes' => $file->getSize(),
                'file_data' => base64_encode(file_get_contents($file->getRealPath())),
                'for_customer' => true,
            ]);
        }

        return count($files);
    }

    /** Herinnert iedereen die na drie dagen nog niets liet horen, zolang de ronde open is. */
    public function remindDue(): int
    {
        $due = TenderRequest::query()
            ->where('status', 'sent')
            ->whereNull('reminded_at')
            ->whereNotNull('sent_at')
            ->where('sent_at', '<=', now()->subDays(self::REMIND_AFTER_DAYS))
            ->whereHas('round', fn ($q) => $q->where('status', 'open')->whereDate('deadline', '>=', now()->toDateString()))
            ->with(['round', 'subcontractor'])
            ->get();

        $count = 0;
        foreach ($due as $request) {
            if ($this->remind($request)) {
                $count++;
            }
        }

        return $count;
    }

    public function remind(TenderRequest $request): bool
    {
        if ($request->status !== 'sent' || ! $request->round?->isOpen()) {
            return false;
        }
        if (! $this->mail($request, 'reminder')) {
            return false;
        }
        $request->forceFill(['reminded_at' => now()])->save();

        return true;
    }

    /** Het bedrijf geeft prijs en beschikbaarheid door (mag bijwerken zolang de ronde open is). */
    public function respond(TenderRequest $request, array $data, ?UploadedFile $file = null): void
    {
        if (! $request->round?->isOpen()) {
            throw new \DomainException(__('Deze prijsaanvraag is gesloten.'));
        }

        if ($file) {
            // Een nieuwe offerte vervangt de vorige. Oudere staan mogelijk nog op schijf.
            if ($request->attachment_path) {
                Storage::disk('local')->delete($request->attachment_path);
            }
            $request->attachments()->delete();
            $request->attachments()->create([
                'company_id' => $request->round->company_id,
                'filename' => mb_substr($file->getClientOriginalName(), 0, 255),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size_bytes' => $file->getSize(),
                'file_data' => base64_encode(file_get_contents($file->getRealPath())),
                'for_customer' => false,
            ]);
            $request->attachment_path = null;
            $request->attachment_name = mb_substr($file->getClientOriginalName(), 0, 255);
        }

        $request->forceFill([
            'status' => 'responded',
            'price' => $data['price'],
            'available_week' => $data['available_week'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'decline_reason' => null,
            'responded_at' => now(),
        ])->save();
    }

    public function decline(TenderRequest $request, ?string $reason = null): void
    {
        if (! $request->round?->isOpen()) {
            throw new \DomainException(__('Deze prijsaanvraag is gesloten.'));
        }

        $request->forceFill([
            'status' => 'declined',
            'decline_reason' => $reason,
            'responded_at' => now(),
        ])->save();
    }

    /**
     * De ondernemer legt zelf vast dat een bedrijf heeft afgezegd (telefonisch
     * of per mail). Er gaat geen bericht uit, en ook geen herinnering meer.
     */
    public function markDeclined(TenderRequest $request, ?string $reason = null): void
    {
        if (! $request->round?->isOpen() || ! in_array($request->status, ['sent', 'responded'], true)) {
            throw new \DomainException(__('Afzeggen kan alleen zolang de uitvraag open is en het bedrijf nog meedoet.'));
        }

        $request->forceFill([
            'status' => 'declined',
            'decline_reason' => filled($reason) ? trim($reason) : __('Afgezegd (door jou vastgelegd)'),
            'responded_at' => now(),
        ])->save();
    }

    /** Haalt een bedrijf uit de ronde, met alles wat het had ingestuurd. Er gaat geen bericht uit. */
    public function removeRequest(TenderRequest $request): void
    {
        if (! $request->round?->isOpen()) {
            throw new \DomainException(__('Deze uitvraag is al gegund of gesloten; verwijderen kan niet meer.'));
        }

        $round = $request->round;
        $name = $request->subcontractor?->name;
        if ($request->attachment_path) {
            Storage::disk('local')->delete($request->attachment_path);
        }
        $request->attachments()->delete();
        $request->delete();

        Audit::log('updated', $round, __(':label: :name uit de uitvraag gehaald', [
            'label' => Audit::label($round), 'name' => $name,
        ]), [], $round->company_id);
    }

    /**
     * Schrijft extra bedrijven aan in een lopende ronde: zelfde aanvraag,
     * zelfde bijlagen. Wie al meedoet of geen e-mailadres heeft, slaat het over.
     */
    public function invite(TenderRound $round, array $subcontractorIds): int
    {
        if (! $round->isOpen()) {
            throw new \DomainException(__('Deze uitvraag is al gegund of gesloten; bedrijven toevoegen kan niet meer.'));
        }

        $subcontractors = Subcontractor::withoutGlobalScope('company')->where('company_id', $round->company_id)
            ->whereIn('id', $subcontractorIds)
            ->whereNotIn('id', $round->requests()->pluck('subcontractor_id'))
            ->whereNotNull('email')
            ->orderBy('name')
            ->get();

        if ($subcontractors->isEmpty()) {
            throw new \DomainException(__('Kies minstens één bedrijf met een e-mailadres dat nog niet is aangeschreven.'));
        }

        foreach ($subcontractors as $subcontractor) {
            $request = $round->requests()->create([
                'subcontractor_id' => $subcontractor->id,
                'token' => bin2hex(random_bytes(32)),
                'status' => 'sent',
            ]);
            $request->setRelation('round', $round)->setRelation('subcontractor', $subcontractor);
            if ($this->mail($request, 'request')) {
                $request->forceFill(['sent_at' => now()])->save();
            }
        }

        Audit::log('updated', $round, __(':label: :count bedrijven extra aangeschreven', [
            'label' => Audit::label($round), 'count' => $subcontractors->count(),
        ]), [], $round->company_id);

        return $subcontractors->count();
    }

    /** Gunt de ronde: opdracht naar de winnaar, nette afwijzing naar wie een prijs gaf. */
    public function award(TenderRound $round, TenderRequest $winner): void
    {
        if (! $round->isOpen()) {
            throw new \DomainException(__('Deze uitvraag is al gegund of gesloten.'));
        }
        if ((int) $winner->tender_round_id !== (int) $round->id || $winner->status !== 'responded') {
            throw new \DomainException(__('Alleen een bedrijf dat een prijs heeft doorgegeven kun je de opdracht gunnen.'));
        }

        $losers = $round->requests()->where('id', '!=', $winner->id)->where('status', 'responded')->pluck('id');

        DB::transaction(function () use ($round, $winner) {
            $winner->forceFill(['status' => 'awarded'])->save();
            $round->requests()->where('id', '!=', $winner->id)
                ->whereIn('status', ['sent', 'responded'])
                ->update(['status' => 'rejected']);
            $round->forceFill(['status' => 'awarded', 'awarded_request_id' => $winner->id, 'awarded_at' => now()])->save();
        });

        $this->mail($winner->fresh(['round', 'subcontractor']), 'award');
        foreach (TenderRequest::whereIn('id', $losers)->with(['round', 'subcontractor'])->get() as $request) {
            $this->mail($request, 'reject');
        }

        Audit::log('awarded', $round, __(':label gegund aan :name', [
            'label' => Audit::label($round), 'name' => $winner->subcontractor?->name,
        ]), [], $round->company_id);
    }

    /** Sluit een ronde zonder te gunnen (bijvoorbeeld: project gaat niet door). */
    public function close(TenderRound $round): void
    {
        if (! $round->isOpen()) {
            return;
        }
        $round->forceFill(['status' => 'closed'])->save();
        Audit::log('closed', $round, __(':label gesloten zonder gunning', ['label' => Audit::label($round)]), [], $round->company_id);
    }

    /**
     * Hoe een bedrijf zich gedraagt in de pool: reageert het, hoe snel, wint het,
     * en zit het boven of onder het gemiddelde van de rondes waarin het meedeed.
     */
    public function stats(Subcontractor $subcontractor): array
    {
        $requests = $subcontractor->requests()->with('round.requests')->get();
        $priced = $requests->filter(fn (TenderRequest $r) => $r->hasPrice());

        $hours = $priced->filter(fn (TenderRequest $r) => $r->sent_at && $r->responded_at)
            ->map(fn (TenderRequest $r) => $r->sent_at->diffInHours($r->responded_at))
            ->avg();

        // Prijsniveau: eigen prijs t.o.v. het gemiddelde van de ronde, alleen waar iets te vergelijken valt.
        $index = $priced->map(function (TenderRequest $r) {
            $others = $r->round?->requests->filter(fn (TenderRequest $x) => $x->hasPrice()) ?? collect();
            if ($others->count() < 2) {
                return null;
            }
            $avg = (float) $others->avg('price');

            return $avg > 0 ? ((float) $r->price / $avg - 1) * 100 : null;
        })->filter(fn ($v) => $v !== null)->avg();

        return [
            'requests' => $requests->count(),
            'responded' => $priced->count(),
            'declined' => $requests->where('status', 'declined')->count(),
            'won' => $requests->where('status', 'awarded')->count(),
            'avg_response_hours' => $hours !== null ? (int) round($hours) : null,
            'price_index' => $index !== null ? (int) round($index) : null,
        ];
    }

    /** Mail naar het bedrijf, in de taal van de markt; een mislukte mail breekt de ronde niet. */
    protected function mail(TenderRequest $request, string $kind): bool
    {
        $email = $request->subcontractor?->email;
        if (! $email) {
            return false;
        }

        try {
            DocumentLocale::using(DocumentLocale::default(), fn () => Mail::to($email)->send(new TenderMail($request, $kind)));

            return true;
        } catch (\Throwable $e) {
            Log::error('Uitvraagmail mislukt', ['request' => $request->id, 'kind' => $kind, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
