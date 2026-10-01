<?php

namespace App\Services;

use App\Mail\TenderMail;
use App\Models\Company;
use App\Models\Quote;
use App\Models\ShortLink;
use App\Models\Subcontractor;
use App\Models\TenderRequest;
use App\Models\TenderRound;
use App\Models\WorkPackage;
use App\Support\Audit;
use App\Support\DocumentLocale;
use App\Support\PhoneNumber;
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
    /**
     * Meerdere bedrijven tegelijk in de pool: één record per bedrijf, zonder
     * dubbelen op naam. Pakketten worden op naam gezocht (ook op het begin van
     * de naam). Geeft [toegevoegd, overgeslagen] terug.
     *
     * @param  array<int, array<string, mixed>>  $records  name, email, phone, city, website, notes, packages (namen)
     * @return array{0: int, 1: int}
     */
    public function importRecords(Company $company, array $records, string $source = 'import'): array
    {
        $packages = WorkPackage::withoutGlobalScope('company')->where('company_id', $company->id)->orderBy('sort_order')->get();
        $findPackage = function (string $name) use ($packages) {
            $needle = mb_strtolower(trim($name));

            return $needle === '' ? null : $packages->first(fn (WorkPackage $p) => mb_strtolower($p->name) === $needle
                || str_starts_with(mb_strtolower($p->name), $needle));
        };
        $existing = Subcontractor::withoutGlobalScope('company')->where('company_id', $company->id)->pluck('name')
            ->map(fn ($n) => mb_strtolower(trim((string) $n)))->flip();

        $added = 0;
        $skipped = 0;
        foreach ($records as $record) {
            $name = trim((string) ($record['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            if ($existing->has(mb_strtolower($name))) {
                $skipped++;
                continue;
            }
            $email = filter_var(trim((string) ($record['email'] ?? '')), FILTER_VALIDATE_EMAIL) ? trim((string) $record['email']) : null;
            $subcontractor = Subcontractor::create([
                'company_id' => $company->id,
                'name' => mb_substr($name, 0, 160),
                'email' => $email,
                'phone' => mb_substr(trim((string) ($record['phone'] ?? '')), 0, 40) ?: null,
                'city' => mb_substr(trim((string) ($record['city'] ?? '')), 0, 120) ?: null,
                'website' => mb_substr(trim((string) ($record['website'] ?? '')), 0, 180) ?: null,
                'notes' => mb_substr(trim((string) ($record['notes'] ?? '')), 0, 2000) ?: null,
                'source' => $source,
            ]);
            $ids = collect($record['packages'] ?? [])->map(fn ($n) => $findPackage((string) $n)?->id)->filter()->unique()->values()->all();
            $subcontractor->workPackages()->sync($ids);
            $existing->put(mb_strtolower($name), true);
            $added++;
        }

        return [$added, $skipped];
    }

    /**
     * Plaklijst: één bedrijf per regel — naam; e-mail; telefoon; plaats; werkpakketten (komma's).
     *
     * @return array{0: int, 1: int}
     */
    public function importLines(Company $company, string $lines): array
    {
        $records = [];
        foreach (preg_split('/\r?\n/', $lines) as $line) {
            $parts = array_map('trim', explode(';', $line));
            $records[] = [
                'name' => $parts[0] ?? '', 'email' => $parts[1] ?? '', 'phone' => $parts[2] ?? '', 'city' => $parts[3] ?? '',
                'packages' => array_filter(array_map('trim', explode(',', $parts[4] ?? ''))),
            ];
        }

        return $this->importRecords($company, $records);
    }

    /**
     * Startlijsten: kant-en-klare lijsten met bedrijven voor een werkpakket, uit
     * resources/data/startlijsten. Alleen voor de eigenaar van het platform.
     *
     * @return array<int, array{key: string, package: string, description: string, count: int, with_email: int, region: string}>
     */
    public function startlists(): array
    {
        $lists = [];
        foreach (glob(resource_path('data/startlijsten/*.json')) ?: [] as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            if (! is_array($data) || empty($data['package']['name']) || empty($data['companies'])) {
                continue;
            }
            $lists[] = [
                'key' => basename($file, '.json'),
                'package' => (string) $data['package']['name'],
                'description' => (string) ($data['package']['description'] ?? ''),
                'count' => count($data['companies']),
                'with_email' => collect($data['companies'])->filter(fn ($c) => filled($c['email'] ?? null))->count(),
                'region' => (string) ($data['region'] ?? ''),
            ];
        }

        return $lists;
    }

    /**
     * Een startlijst in de pool zetten: het werkpakket komt erbij als het nog
     * niet bestaat, de bedrijven worden toegevoegd (dubbelen op naam overgeslagen).
     *
     * @return array{0: int, 1: int}
     */
    public function applyStartlist(Company $company, string $key): array
    {
        $file = resource_path('data/startlijsten/' . preg_replace('/[^a-z0-9-]/', '', $key) . '.json');
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        if (! is_array($data) || empty($data['package']['name'])) {
            throw new \DomainException(__('Deze startlijst bestaat niet.'));
        }
        $name = (string) $data['package']['name'];
        $package = WorkPackage::withoutGlobalScope('company')->where('company_id', $company->id)
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])->first();
        if (! $package) {
            $package = WorkPackage::create([
                'company_id' => $company->id,
                'name' => $name,
                'description' => $data['package']['description'] ?? null,
                'sort_order' => (int) WorkPackage::withoutGlobalScope('company')->where('company_id', $company->id)->max('sort_order') + 1,
            ]);
        }
        $records = collect($data['companies'])->map(fn ($c) => [
            'name' => $c['name'] ?? '', 'email' => $c['email'] ?? '', 'phone' => $c['phone'] ?? '', 'city' => $c['city'] ?? '',
            'website' => $c['website'] ?? '', 'notes' => $c['note'] ?? '', 'packages' => [$package->name],
        ])->all();
        [$added, $skipped] = $this->importRecords($company, $records, 'startlist');
        Audit::log('created', $package, __('Startlijst :package: :added bedrijven toegevoegd', ['package' => $package->name, 'added' => $added]), [], $company->id);

        return [$added, $skipped];
    }

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
        $sms = app(SmsService::class)->available($company);

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
                    // Zonder e-mailadres maar met een mobiel nummer: de aanvraag gaat per sms.
                    'by_sms' => blank($s->email) && $sms && PhoneNumber::isMobile($s->phone),
                ])->values()->all(),
            ])->values()->all();
    }

    /** Is dit bedrijf aan te schrijven: per mail, of anders per sms naar een mobiel nummer? */
    public function reachable(Subcontractor $subcontractor, Company $company): bool
    {
        return filled($subcontractor->email)
            || (PhoneNumber::isMobile($subcontractor->phone) && app(SmsService::class)->available($company));
    }

    /**
     * Opent een ronde: één aanvraag per gekozen bedrijf, elk met eigen
     * tokenlink, en meteen de aanvraagmail — met de bijlagen (tekening, bestek)
     * erbij. Een bedrijf zonder e-mailadres maar met een mobiel nummer krijgt
     * de aanvraag per sms, als sms voor deze administratie aanstaat.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function open(Company $company, ?Quote $quote, array $data, array $files = []): TenderRound
    {
        $package = WorkPackage::withoutGlobalScope('company')->where('company_id', $company->id)
            ->findOrFail($data['work_package_id']);
        $subcontractors = Subcontractor::withoutGlobalScope('company')->where('company_id', $company->id)
            ->whereIn('id', $data['subcontractor_ids'] ?? [])
            ->orderBy('name')
            ->get()
            ->filter(fn (Subcontractor $s) => $this->reachable($s, $company))
            ->values();

        if ($subcontractors->isEmpty()) {
            throw new \DomainException(app(SmsService::class)->available($company)
                ? __('Kies minstens één bedrijf met een e-mailadres of een mobiel nummer.')
                : __('Kies minstens één bedrijf met een e-mailadres.'));
        }

        $round = DB::transaction(function () use ($company, $quote, $package, $subcontractors, $data, $files) {
            $round = TenderRound::create([
                'company_id' => $company->id,
                'quote_id' => $quote?->id,
                // De uitvraag hoort bij het project van de offerte.
                'project_id' => $quote?->project_id,
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
            if ($this->notify($request, 'request')) {
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
        if (! $this->notify($request, 'reminder')) {
            return false;
        }
        $request->forceFill(['reminded_at' => now()])->save();

        return true;
    }

    /**
     * Stuurt het bedrijf een sms met de link naar de aanvraag: als eerste
     * bericht, of als herinnering na de mail. De tekst mag de ondernemer
     * aanpassen; de link moet erin blijven staan.
     */
    public function sms(TenderRequest $request, ?string $text = null, ?int $userId = null): void
    {
        $round = $request->round;
        if ($request->status !== 'sent' || ! $round?->isOpen()) {
            throw new \DomainException(__('Een sms kan alleen zolang de uitvraag open is en het bedrijf nog niet heeft gereageerd.'));
        }

        $link = $this->shortUrl($request);
        $text = filled($text) ? trim($text) : $this->smsText($request);
        if (! str_contains($text, $link)) {
            throw new \DomainException(__('Laat de link in het bericht staan: zonder link kan het bedrijf niet reageren.'));
        }

        app(SmsService::class)->send($round->company, $request->subcontractor?->phone, $text, $request, $userId);
        $request->forceFill(['sms_at' => now(), 'sent_at' => $request->sent_at ?? now()])->save();

        Audit::log('updated', $round, __(':label: sms naar :name', [
            'label' => Audit::label($round), 'name' => $request->subcontractor?->name,
        ]), [], $round->company_id);
    }

    /** De tekst die klaarstaat voor de sms, in de taal waarin de mail uitgaat. */
    public function smsText(TenderRequest $request, ?string $kind = null): string
    {
        $round = $request->round;
        $company = $round->company;
        $kind ??= $request->sent_at ? 'reminder' : 'request';
        $vars = [
            'company' => $company->name,
            'title' => $round->title,
            'place' => filled($round->location) ? ' (' . $round->location . ')' : '',
            'link' => $this->shortUrl($request),
        ];

        return DocumentLocale::using(DocumentLocale::default(), function () use ($kind, $vars, $company) {
            $text = match ($kind) {
                'reminder' => __('Herinnering van :company: wij ontvangen graag uw prijs voor :title:place. Reageren: :link', $vars),
                'award' => __(':company gunt u de opdracht voor :title:place. Wij nemen contact met u op over de planning.', $vars),
                'reject' => __('Bedankt voor uw prijsopgave voor :title. :company heeft voor dit project een andere partij gekozen.', $vars),
                default => __(':company vraagt u om een prijs voor :title:place. Bekijk de aanvraag en reageer: :link', $vars),
            };
            if (filled($company->phone) && in_array($kind, ['request', 'reminder'], true)) {
                $text .= ' ' . __('Vragen? Bel :phone', ['phone' => $company->phone]);
            }

            return $text;
        });
    }

    /** Het korte adres van de reactiepagina, voor in een sms. */
    public function shortUrl(TenderRequest $request): string
    {
        return ShortLink::for($request->responseUrl(), $request->round?->company_id)->shortUrl();
    }

    /** Het bedrijf geeft prijs en beschikbaarheid door (mag bijwerken zolang de ronde open is). */
    public function respond(TenderRequest $request, array $data, ?UploadedFile $file = null): void
    {
        if (! $request->round?->isOpen() || $request->status === 'rejected') {
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
            // Een nieuwe of aangepaste prijsopgave krijgt een nieuwe offertecheck.
            'review' => null,
            'reviewed_at' => null,
            'review_error' => null,
            'review_attempts' => 0,
        ])->save();
    }

    /**
     * Vragen over een prijsopgave mailen aan het bedrijf (meestal de vragen uit
     * de offertecheck, door de ondernemer nagelezen). Het bedrijf kan zijn
     * prijsopgave via dezelfde link aanvullen.
     *
     * @param  array<int, string>  $questions
     */
    public function askQuestions(TenderRequest $request, array $questions): void
    {
        if (! $request->round?->isOpen() || $request->status !== 'responded') {
            throw new \DomainException(__('Vragen stellen kan alleen zolang de uitvraag open is en het bedrijf een prijs heeft doorgegeven.'));
        }
        $questions = collect($questions)->map(fn ($q) => trim((string) $q))->filter()->take(5)->values()->all();
        if ($questions === []) {
            throw new \DomainException(__('Schrijf minstens één vraag.'));
        }
        $email = $request->subcontractor?->email;
        if (! $email) {
            throw new \DomainException(__('Dit bedrijf heeft geen e-mailadres; bel het of stuur zelf een bericht.'));
        }
        try {
            DocumentLocale::using(DocumentLocale::default(), fn () => Mail::to($email)->send(new TenderMail($request, 'questions', $questions)));
        } catch (\Throwable $e) {
            Log::error('Vragenmail mislukt', ['request' => $request->id, 'error' => $e->getMessage()]);
            throw new \DomainException(__('De vragen konden niet worden gemaild. Probeer het later opnieuw.'));
        }
        $review = is_array($request->review) ? $request->review : [];
        $review['questions_sent'] = ['at' => now()->toDateTimeString(), 'questions' => $questions];
        $request->forceFill(['review' => $review])->save();

        Audit::log('sent', $request->round, __(':label: vragen gemaild aan :name', [
            'label' => Audit::label($request->round), 'name' => $request->subcontractor?->name,
        ]), [], $request->round->company_id);
    }

    public function decline(TenderRequest $request, ?string $reason = null): void
    {
        if (! $request->round?->isOpen() || $request->status === 'rejected') {
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

    /** Het bericht dat klaarstaat bij een afwijzing, in de taal waarin de mail uitgaat. */
    public function defaultRejection(): string
    {
        return DocumentLocale::using(DocumentLocale::default(), fn () => __('Bedankt voor uw prijsopgave en voor de tijd die u erin heeft gestoken. Wij hebben uw aanbieding zorgvuldig bekeken en gaan er voor dit project niet mee verder. Wij houden u graag in beeld voor volgende projecten.'));
    }

    /**
     * Wijst één prijsopgave af met een bericht van de ondernemer; de ronde
     * blijft open voor de andere bedrijven. Lukt de mail niet, dan verandert
     * er niets, zodat afwijzen opnieuw kan.
     */
    public function reject(TenderRequest $request, ?string $message = null): void
    {
        if (! $request->round?->isOpen() || $request->status !== 'responded') {
            throw new \DomainException(__('Afwijzen kan alleen zolang de uitvraag open is en het bedrijf een prijs heeft doorgegeven.'));
        }

        // Nog niet opslaan: de mail leest het bericht van de aanvraag.
        $request->reject_message = filled($message) ? trim($message) : $this->defaultRejection();
        if (! $this->notify($request, 'reject')) {
            throw new \DomainException(__('De afwijzing kon niet worden gemaild. Er is niets gewijzigd; probeer het later opnieuw.'));
        }

        $request->forceFill(['status' => 'rejected', 'rejected_at' => now()])->save();

        Audit::log('updated', $request->round, __(':label: offerte van :name afgewezen', [
            'label' => Audit::label($request->round), 'name' => $request->subcontractor?->name,
        ]), [], $request->round->company_id);
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
     * zelfde bijlagen. Wie al meedoet of niet te bereiken is (geen
     * e-mailadres en geen mobiel nummer voor een sms), slaat het over.
     */
    public function invite(TenderRound $round, array $subcontractorIds): int
    {
        if (! $round->isOpen()) {
            throw new \DomainException(__('Deze uitvraag is al gegund of gesloten; bedrijven toevoegen kan niet meer.'));
        }

        $subcontractors = Subcontractor::withoutGlobalScope('company')->where('company_id', $round->company_id)
            ->whereIn('id', $subcontractorIds)
            ->whereNotIn('id', $round->requests()->pluck('subcontractor_id'))
            ->orderBy('name')
            ->get()
            ->filter(fn (Subcontractor $s) => $this->reachable($s, $round->company))
            ->values();

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
            if ($this->notify($request, 'request')) {
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
                ->update(['status' => 'rejected', 'rejected_at' => now()]);
            $round->forceFill(['status' => 'awarded', 'awarded_request_id' => $winner->id, 'awarded_at' => now()])->save();
        });

        $this->notify($winner->fresh(['round', 'subcontractor']), 'award');
        foreach (TenderRequest::whereIn('id', $losers)->with(['round', 'subcontractor'])->get() as $request) {
            $this->notify($request, 'reject');
        }
        // Hoort de uitvraag bij een project, dan staat het gegunde werk meteen op de tijdslijn.
        app(ProjectPlanService::class)->syncRound($round->fresh());

        Audit::log('awarded', $round, __(':label gegund aan :name', [
            'label' => Audit::label($round), 'name' => $winner->subcontractor?->name,
        ]), [], $round->company_id);
    }

    /**
     * Een gunning intrekken: het bedrijf krijgt een nette mail, de uitvraag gaat
     * weer open en de winnaar staat weer als 'prijs ontvangen'. De bedrijven die
     * bij de gunning een afwijzing kregen, doen desgewenst weer mee (zonder
     * bericht; bij een nieuwe gunning krijgen ze gewoon de opdrachtmail).
     */
    public function revoke(TenderRound $round, ?string $message = null, bool $reopenOthers = true): void
    {
        if ($round->status !== 'awarded' || ! $round->awardedRequest) {
            throw new \DomainException(__('Deze uitvraag is niet gegund.'));
        }
        $winner = $round->awardedRequest->loadMissing('subcontractor');
        $awardedAt = $round->awarded_at;

        // Eerst de mail: lukt die niet, dan verandert er niets.
        if (filled($winner->subcontractor?->email)) {
            try {
                DocumentLocale::using(DocumentLocale::default(), fn () => Mail::to($winner->subcontractor->email)->send(new TenderMail($winner, 'revoke', [], filled($message) ? trim($message) : null)));
            } catch (\Throwable $e) {
                Log::error('Intrekmail mislukt', ['request' => $winner->id, 'error' => $e->getMessage()]);
                throw new \DomainException(__('De mail aan het bedrijf kon niet worden verstuurd. Er is niets gewijzigd; probeer het later opnieuw.'));
            }
        }

        DB::transaction(function () use ($round, $winner, $awardedAt, $reopenOthers) {
            $winner->forceFill(['status' => 'responded'])->save();
            if ($reopenOthers && $awardedAt) {
                // Alleen wie door déze gunning werd afgewezen; een eigen afwijzing met bericht blijft staan.
                $round->requests()->where('status', 'rejected')->whereNull('reject_message')
                    ->where('rejected_at', '>=', $awardedAt->copy()->subMinute())
                    ->update(['status' => 'responded', 'rejected_at' => null]);
            }
            $round->forceFill(['status' => 'open', 'awarded_request_id' => null, 'awarded_at' => null])->save();
            // Het onderdeel dat de gunning op de projectplanning zette, gaat weer weg zolang er nog niets mee is gebeurd.
            \App\Models\ProjectPlanItem::where('tender_round_id', $round->id)->where('status', 'planned')->delete();
        });

        Audit::log('updated', $round, __(':label: gunning aan :name ingetrokken', [
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

    /**
     * Bericht naar het bedrijf: per mail, en zonder e-mailadres per sms naar
     * het mobiele nummer. Een mislukt bericht breekt de ronde niet.
     */
    protected function notify(TenderRequest $request, string $kind): bool
    {
        if (filled($request->subcontractor?->email)) {
            return $this->mail($request, $kind);
        }

        $company = $request->round?->company;
        if (! $company || ! $request->subcontractor || ! $this->reachable($request->subcontractor, $company)) {
            return false;
        }

        try {
            app(SmsService::class)->send($company, $request->subcontractor->phone, $this->smsText($request, $kind), $request);
            $request->forceFill(['sms_at' => now()])->save();

            return true;
        } catch (\DomainException $e) {
            Log::warning('Uitvraag-sms niet verstuurd', ['request' => $request->id, 'kind' => $kind, 'reason' => $e->getMessage()]);

            return false;
        }
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
