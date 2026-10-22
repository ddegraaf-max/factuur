<?php

namespace App\Mail;

use App\Models\Provider;
use App\Models\ServiceJob;
use App\Models\ServiceJobOffer;
use App\Support\Fachora;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Alle e-mail van Fachora in één sjabloon: een kop, een titel, alinea's,
 * een tabel met feiten en één knop. Welke tekst, hangt af van kind
 * (wat er gebeurde) en audience (klant, bedrijf of eigenaar). De teksten
 * staan hier als Nederlandse sleutels; de Poolse en Engelse vertalingen in
 * lang/pl.json en lang/en.json, de eigenaar leest Nederlands.
 */
class FachoraMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $kind,
        public string $audience, // customer | provider | owner
        public ?ServiceJob $job = null,
        public ?ServiceJobOffer $offer = null,
        public ?Provider $provider = null,
        /** @var array<string, mixed> */
        public array $extra = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), (string) brand('name')),
            replyTo: [new Address((string) brand('email'), (string) brand('name'))],
            subject: $this->compose()['subject'],
        );
    }

    public function content(): Content
    {
        $c = $this->compose();

        return new Content(
            view: 'emails.fachora',
            text: 'emails.fachora-text',
            with: $c + ['audienceKey' => $this->audience, 'kindKey' => $this->kind],
        );
    }

    /**
     * Onderwerp, kop en inhoud per soort bericht.
     *
     * @return array{subject: string, eyebrow: string, title: string, paragraphs: list<string>, facts: array<string, string>, button: ?array{label: string, url: string}, footnote: ?string, quote: ?string}
     */
    public function compose(): array
    {
        $job = $this->job;
        $provider = $this->provider ?: $job?->provider;
        $ref = $job?->reference ?? '';
        $name = $provider?->display_name ?? '';
        $amount = Fachora::money($job?->agreed_amount);
        $when = $this->appointment();
        $facts = $this->facts();
        $out = ['subject' => '', 'eyebrow' => 'Fachora', 'title' => '', 'paragraphs' => [], 'facts' => [], 'button' => null, 'footnote' => null, 'quote' => null];

        $set = function (string $subject, string $title, array $paragraphs, ?array $button = null, array $facts = [], ?string $eyebrow = null) use (&$out) {
            $out['subject'] = $subject;
            $out['title'] = $title;
            $out['paragraphs'] = $paragraphs;
            $out['button'] = $button;
            $out['facts'] = $facts;
            $out['eyebrow'] = $eyebrow ?: $out['eyebrow'];
        };

        if ($this->audience === 'customer' && $job) {
            $jobUrl = $job->url();
            switch ($this->kind) {
                case 'confirm':
                    $set(__('Bevestig je aanvraag :ref', ['ref' => $ref]), __('Bevestig je aanvraag'), [
                        __('Je hebt op :domain een aanvraag gedaan voor :category in :city. Bevestig met de knop hieronder; pas daarna bieden wij je klus aan bij gecontroleerde bedrijven in de buurt.', ['domain' => brand('domain'), 'category' => $job->categoryName(), 'city' => $job->city]),
                        __('Heb je deze aanvraag niet gedaan? Dan hoef je niets te doen; zonder bevestiging verdwijnt ze vanzelf.'),
                    ], ['label' => __('Bevestig mijn aanvraag'), 'url' => $job->confirmUrl()], $facts);
                    break;
                case 'choose':
                    // 1.86: de klant kiest zelf de bedrijven; deze mail brengt hem terug als hij de pagina sloot.
                    $set(__('Kies de bedrijven voor je aanvraag :ref', ['ref' => $ref]), __('Kies je bedrijven'), array_values(array_filter([
                        ($this->extra['again'] ?? false) ? __('De bedrijven die je koos, zijn niet meer bereikbaar. Kies er hieronder opnieuw een paar; een betaalde bijdrage blijft gewoon staan.') : null,
                        __('Op je opdrachtpagina staan bedrijven uit jouw buurt. Kies er tot :n; alleen zij krijgen je aanvraag en geven een prijs.', ['n' => (int) config('fachora.max_offers', 5)]),
                        __('Bewaar deze mail: de link hieronder is jouw persoonlijke opdrachtpagina.'),
                    ])), ['label' => __('Bedrijven kiezen'), 'url' => $jobUrl], $facts);
                    break;
                case 'confirmed':
                    $set(__('Je aanvraag :ref staat open', ['ref' => $ref]), __('We zoeken een bedrijf voor je'), [
                        ! empty($job->selected_providers)
                            ? __('Je aanvraag is verstuurd naar de :n bedrijven die je koos. Zodra een bedrijf een prijs geeft, krijg je een mail en zie je de prijs en de beschikbaarheid op je opdrachtpagina.', ['n' => $job->offers()->count()])
                            : __('Je klus wordt nu aangeboden aan maximaal :n Fachora Verified bedrijven in de buurt. Zodra een bedrijf een prijs geeft, krijg je een mail en zie je de prijs, de beschikbaarheid en de beoordelingen op je opdrachtpagina.', ['n' => (int) config('fachora.max_offers', 5)]),
                        __('Bewaar deze mail: de link hieronder is jouw persoonlijke opdrachtpagina.'),
                    ], ['label' => __('Mijn opdracht'), 'url' => $jobUrl], $facts);
                    break;
                case 'offer':
                    $o = $this->offer ?: $job->offers()->whereKey((int) ($this->extra['offer_id'] ?? 0))->first();
                    $set(__('Nieuwe prijs voor je klus :ref', ['ref' => $ref]), __('Een bedrijf gaf een prijs'), [
                        __(':provider gaf een prijs van :amount (:type). Bekijk de prijs, de beschikbaarheid en de beoordelingen, en kies het bedrijf dat jou het beste past. Je kunt rustig wachten op meer prijzen.', [
                            'provider' => $o?->provider?->display_name ?? '', 'amount' => Fachora::money($o?->price), 'type' => $this->priceType($o?->price_type),
                        ]),
                    ], ['label' => __('Bekijk de aanbiedingen'), 'url' => $jobUrl]);
                    break;
                case 'no_match':
                    $set(__('Nog geen bedrijf gevonden voor :ref', ['ref' => $ref]), __('Nog geen bedrijf gevonden'), [
                        __('In :city hebben we op dit moment geen Fachora Verified bedrijf voor :category dat kan reageren. Wij zoeken zelf verder en nemen contact met je op zodra we een bedrijf hebben.', ['city' => $job->city, 'category' => $job->categoryName()]),
                    ], ['label' => __('Mijn opdracht'), 'url' => $jobUrl]);
                    break;
                case 'agreed':
                    $set(__('Je koos :provider voor :ref', ['provider' => $name, 'ref' => $ref]), __('Je hebt :provider gekozen', ['provider' => $name]), $job->protect
                        ? [
                            __('Reserveer nu het afgesproken bedrag van :amount via Fachora Protect. Het geld staat veilig bij Fachora en wordt pas uitbetaald als jij het werk hebt goedgekeurd. Extra werk gebeurt alleen na jouw goedkeuring.', ['amount' => $amount]),
                            __('Afspraak: :when.', ['when' => $when]),
                        ]
                        : [
                            __('Afspraak: :when. Afgesproken: :amount (:type).', ['when' => $when, 'amount' => $amount, 'type' => $this->priceType($job->agreed_type)]),
                            __('Je betaalt het bedrijf na afloop. Vraag altijd om een factuur; dat is bij Fachora afgesproken.'),
                        ], ['label' => $job->protect ? __('Reserveer het bedrag') : __('Mijn opdracht'), 'url' => $jobUrl], $facts);
                    break;
                case 'reserved':
                    $set(__('Betaling gereserveerd voor :ref', ['ref' => $ref]), __('Betaling gereserveerd'), [
                        __(':amount staat veilig bij Fachora. :provider komt :when. Het bedrag wordt pas uitbetaald als jij het werk hebt goedgekeurd.', ['amount' => $amount, 'provider' => $name, 'when' => $when]),
                    ], ['label' => __('Mijn opdracht'), 'url' => $jobUrl], $facts);
                    break;
                case 'extra':
                    $e = $job->extras()->whereKey((int) ($this->extra['extra_id'] ?? 0))->first();
                    $set(__('Voorstel voor extra werk bij :ref', ['ref' => $ref]), __('Voorstel voor extra werk'), [
                        __(':provider stelt extra werk voor: :desc voor :amount. Keur het goed of wijs het af op je opdrachtpagina; zonder jouw goedkeuring wordt het niet uitgevoerd.', ['provider' => $name, 'desc' => $e?->description ?? '', 'amount' => Fachora::money($e?->amount)]),
                    ], ['label' => __('Beoordeel het voorstel'), 'url' => $jobUrl]);
                    break;
                case 'done':
                    $set(__('Is het werk naar wens? :ref', ['ref' => $ref]), __('Is het werk naar wens?'), [
                        __(':provider meldt dat het werk klaar is. Keur het goed op je opdrachtpagina, of open een geschil als er iets niet klopt.', ['provider' => $name]),
                        __('Reageer je niet binnen :n dagen, dan geldt het werk als goedgekeurd.', ['n' => (int) config('fachora.auto_accept_days', 5)]),
                    ], ['label' => __('Bekijk en keur goed'), 'url' => $jobUrl]);
                    break;
                case 'accepted':
                    $set(__('Bedankt! Opdracht :ref is afgerond', ['ref' => $ref]), __('De opdracht is afgerond'), array_values(array_filter([
                        __('Je hebt het werk goedgekeurd.') . ' ' . ($job->isPaid() ? __('Het gereserveerde bedrag is uitbetaald aan :provider.', ['provider' => $name]) : __('Je betaalt :provider rechtstreeks.', ['provider' => $name])),
                        $job->warranty_months ? __('Garantie op het werk: :n maanden. Is er binnen die tijd iets mis, laat het weten via je opdrachtpagina.', ['n' => $job->warranty_months]) : null,
                        __('Je factuur ontvang je van het bedrijf. Help anderen: beoordeel :provider in een minuut.', ['provider' => $name]),
                    ])), ['label' => __('Beoordeel het bedrijf'), 'url' => $jobUrl]);
                    break;
                case 'dispute_opened':
                    $set(__('Je geschil over :ref is geregistreerd', ['ref' => $ref]), __('Je geschil is geregistreerd'), [
                        __(':provider heeft :hours uur om te reageren; daarna beslist Fachora. Het gereserveerde bedrag blijft staan tot er een uitkomst is.', ['provider' => $name, 'hours' => (int) config('fachora.dispute_hours', 48)]),
                    ], ['label' => __('Mijn opdracht'), 'url' => $jobUrl]);
                    break;
                case 'dispute_reply':
                    $set(__(':provider reageerde op je geschil (:ref)', ['provider' => $name, 'ref' => $ref]), __('Het bedrijf reageerde'), [
                        __('Fachora beoordeelt beide kanten en laat je de uitkomst weten.'),
                    ], ['label' => __('Mijn opdracht'), 'url' => $jobUrl]);
                    $out['quote'] = $job->dispute_reply;
                    break;
                case 'resolved':
                    $set(__('Uitkomst van het geschil over :ref', ['ref' => $ref]), __('Uitkomst van het geschil'), array_values(array_filter([
                        $this->resolutionText(),
                        $this->extra['note'] ?? null,
                    ])), ['label' => __('Mijn opdracht'), 'url' => $jobUrl]);
                    break;
                case 'cancelled':
                    $callout = (float) ($this->extra['callout'] ?? 0);
                    $set(__('Opdracht :ref geannuleerd', ['ref' => $ref]), __('Opdracht geannuleerd'), array_values(array_filter([
                        __('De opdracht :ref is geannuleerd.', ['ref' => $ref]),
                        $this->extra['reason'] ?? null,
                        $callout > 0 ? __('Het bedrijf houdt de voorrijkosten (:amount) omdat je binnen :hours uur voor de afspraak annuleerde; de rest gaat terug naar je rekening.', ['amount' => Fachora::money($callout), 'hours' => $this->extra['hours'] ?? config('fachora.cancel_free_hours', 24)]) : null,
                        $callout <= 0 && $job->refunded_at ? __('Het gereserveerde bedrag gaat terug naar je rekening (meestal binnen enkele werkdagen).') : null,
                    ])), null);
                    break;
            }
        } elseif ($this->audience === 'provider' && $provider) {
            $panel = route('fachora.dashboard');
            $request = (bool) ($this->extra['request'] ?? false);
            $out['footnote'] = $request && $provider->optout_token
                // Klantaanvraag aan een bedrijf zonder profiel (1.86): waarom dit bericht, waar de gegevens vandaan komen, afmelden (art. 14 AVG).
                // Volledige art. 14-informatie in het eerste bericht (art. 14 lid 3 sub b AVG), kort met verwijzing naar het privacybeleid.
                ? __('U ontvangt dit bericht omdat een klant uw bedrijf op Fachora koos voor deze opdracht. Beheerder van uw gegevens: :owner (:email). Bron: het openbare register CEIDG (naam, NIP, adres, PKD, e-mail, telefoon). Doel: aanvragen van klanten doorgeven; grondslag: gerechtvaardigd belang (art. 6 lid 1 sub f AVG). Op de site tonen we alleen naam, plaats en soort werk. We bewaren de gegevens zolang u aanvragen kunt krijgen, of tot u bezwaar maakt. U hebt recht op inzage, correctie, beperking en bezwaar, en op een klacht bij de Prezes UODO. Meer: :privacy. Geen aanvragen meer of profiel verwijderen: :url', [
                    'owner' => brand('owner_name') ?: 'Creditline B.V.',
                    'email' => brand('email') ?: 'czesc@fachora.pl',
                    'privacy' => url('/polityka-prywatnosci'),
                    'url' => $provider->optoutUrl(),
                ])
                : ($provider->optout_token
                    ? __('U ontvangt dit bericht omdat u het profiel van :name op Fachora beheert. Geen berichten meer: :url', ['name' => $name, 'url' => $provider->optoutUrl()])
                    : null);
            switch ($this->kind) {
                case 'job':
                case 'reminder':
                    $title = $job?->ai['title_pl'] ?? $job?->title ?? '';
                    $set($this->kind === 'reminder' ? __('Herinnering: zlecenie :ref wacht op uw prijs', ['ref' => $ref]) : __('Nowe zlecenie: :title (:city)', ['title' => $title, 'city' => $job?->city]), $title, array_values(array_filter([
                        $request ? __('Een klant in :city koos uw bedrijf op Fachora en vraagt u om een prijs. U krijgt van ons alleen berichten over aanvragen van klanten.', ['city' => $job?->city]) : null,
                        // Eerste bericht aan een bedrijf zonder profiel: alleen de vraag van de klant, geen reclame
                        // voor Fachora (anders informacja handlowa / art. 398 PKE). De prijs ziet het op de pagina.
                        $this->offer && ! $request && app(\App\Services\Fachora\LeadService::class)->enabled() ? $this->leadLine() : null,
                        __('Een klant in :city zoekt een :category. De prijs die u opgeeft, is de prijs voor de klant; het adres krijgt u zodra de klant u kiest.', ['city' => $job?->city, 'category' => $job?->categoryName('pl')]),
                        $job?->description_pl ?: $job?->description,
                        $job?->protect || ($this->offer?->provider?->protectReady()) ? __('Betaling via Fachora Protect: de klant reserveert het bedrag vooraf, u wordt uitbetaald na goedkeuring.') : null,
                        $this->kind === 'reminder' ? __('De klant wacht nog op prijzen. Reageren kan tot :deadline.', ['deadline' => $job?->offers_deadline_at?->translatedFormat('j F, H:i')]) : null,
                    ])), ['label' => __('Chcę przyjąć zlecenie'), 'url' => $this->offer?->url() ?? $panel], $facts, __('Nowe zlecenie'));
                    break;
                case 'chosen':
                    $set(__('De klant koos u voor :ref', ['ref' => $ref]), __('De klant koos u'), array_values(array_filter([
                        __('Gefeliciteerd: de klant koos uw prijs van :amount. Hieronder staan adres en telefoonnummer; bevestig de afspraak met de klant.', ['amount' => $amount]),
                        $job?->protect ? __('De klant reserveert eerst het bedrag via Fachora Protect; u krijgt bericht zodra dat is gebeurd. Begin pas daarna.') : __('De klant betaalt u rechtstreeks na afloop. Zet een factuur klaar in uw administratie.'),
                        __('Extra werk alleen na goedkeuring van de klant, via de opdrachtpagina in uw panel (Fachora Standard 04).'),
                        $this->extra['note'] ?? null,
                    ])), ['label' => __('Open de opdracht'), 'url' => $job ? route('fachora.jobs.show', $job) : $panel], $facts + array_filter([
                        __('Adres') => $job?->address_line,
                        __('Klant') => $job?->customer_name,
                        __('Telefoon') => $job?->customer_phone,
                        __('E-mail') => $job?->customer_email,
                    ]));
                    break;
                case 'rejected':
                    $set(__('Zlecenie :ref: de klant koos een ander bedrijf', ['ref' => $ref]), __('Niet gekozen'), [
                        __('Bedankt voor uw prijs. Voor deze opdracht koos de klant een ander bedrijf. Volgende opdrachten in uw regio ontvangt u gewoon weer.'),
                    ], null);
                    break;
                case 'reserved':
                    $set(__('Betaling gereserveerd voor :ref', ['ref' => $ref]), __('Betaling gereserveerd'), [
                        __('De klant heeft :amount gereserveerd via Fachora Protect. Na goedkeuring van het werk wordt het bedrag minus de fee (:fee%) uitbetaald op uw rekening.', ['amount' => $amount, 'fee' => $provider->feePercent()]),
                        __('Afspraak: :when.', ['when' => $when]),
                    ], ['label' => __('Open de opdracht'), 'url' => $job ? route('fachora.jobs.show', $job) : $panel], $facts);
                    break;
                case 'extra_decided':
                    $e = $job?->extras()->whereKey((int) ($this->extra['extra_id'] ?? 0))->first();
                    $ok = (bool) ($this->extra['approved'] ?? false);
                    $set($ok ? __('Meerwerk goedgekeurd (:ref)', ['ref' => $ref]) : __('Meerwerk afgewezen (:ref)', ['ref' => $ref]), $ok ? __('Meerwerk goedgekeurd') : __('Meerwerk afgewezen'), [
                        $ok ? __('De klant keurde goed: :desc voor :amount. U kunt het uitvoeren; het staat op de factuur.', ['desc' => $e?->description, 'amount' => Fachora::money($e?->amount)])
                            : __('De klant wees af: :desc. Voer dit werk niet uit.', ['desc' => $e?->description]),
                    ], ['label' => __('Open de opdracht'), 'url' => $job ? route('fachora.jobs.show', $job) : $panel]);
                    break;
                case 'accepted':
                    $set(__('Opdracht :ref afgerond', ['ref' => $ref]), __('De klant keurde het werk goed'), array_values(array_filter([
                        $job?->isPaid() ? __(':payout is onderweg naar uw rekening (fee Fachora: :fee).', ['payout' => Fachora::money($job->payout_amount), 'fee' => Fachora::money($job->fee_amount)]) : __('De klant betaalt u rechtstreeks. Fee Fachora over deze opdracht: :fee.', ['fee' => Fachora::money($job?->fee_amount)]),
                        $job?->invoice_id ? __('Er staat een conceptfactuur klaar in uw administratie; controleer en verstuur hem naar de klant.') : null,
                    ])), ['label' => $job?->invoice_id ? __('Naar de factuur') : __('Naar het panel'), 'url' => $job?->invoice_id ? route('invoices.show', $job->invoice_id) : $panel]);
                    break;
                case 'dispute':
                    $set(__('Geschil over :ref', ['ref' => $ref]), __('De klant opende een geschil'), [
                        __('Reageer binnen :hours uur via uw panel (Fachora Standard 08). Daarna beslist Fachora op basis van beide kanten.', ['hours' => (int) config('fachora.dispute_hours', 48)]),
                    ], ['label' => __('Reageer op het geschil'), 'url' => $job ? route('fachora.jobs.show', $job) : $panel]);
                    $out['quote'] = $job?->dispute_reason;
                    break;
                case 'resolved':
                    $set(__('Uitkomst van het geschil over :ref', ['ref' => $ref]), __('Uitkomst van het geschil'), array_values(array_filter([$this->resolutionText(), $this->extra['note'] ?? null])), ['label' => __('Open de opdracht'), 'url' => $job ? route('fachora.jobs.show', $job) : $panel]);
                    break;
                case 'cancelled':
                    $set(__('Opdracht :ref geannuleerd', ['ref' => $ref]), __('Opdracht geannuleerd'), array_values(array_filter([__('De opdracht :ref is geannuleerd.', ['ref' => $ref]), $this->extra['reason'] ?? null])), null);
                    break;
                case 'claim':
                    $set(__('Bevestig uw profiel op Fachora: :name', ['name' => $name]), __('Bevestig dat u :name vertegenwoordigt', ['name' => $name]), [
                        __('Klik op de knop om het profiel over te nemen en een wachtwoord te kiezen. De link is 7 dagen geldig.'),
                        __('Heeft u dit niet aangevraagd? Negeer deze mail; er verandert dan niets.'),
                    ], ['label' => __('Profiel overnemen'), 'url' => $provider->claimUrl()]);
                    $out['footnote'] = null;
                    break;
                case 'verification_pending':
                    $set(__('Uw aanvraag voor Fachora Verified is ontvangen'), __('Aanvraag ontvangen'), [
                        __('Wij controleren de registers en bellen u mogelijk voor een korte kennismaking. Zorg dat alle stappen in uw panel groen zijn; dan kunnen we snel goedkeuren.'),
                    ], ['label' => __('Naar de verificatie'), 'url' => route('fachora.verification')]);
                    break;
                case 'verified':
                    $set(__('Gefeliciteerd: :name is Fachora Verified', ['name' => $name]), __('U bent Fachora Verified'), [
                        __('Vanaf nu ontvangt u opdrachten uit uw werkgebied (:radius km rond :city) per e-mail. Reageer snel: klanten kiezen vaak binnen een dag.', ['radius' => $provider->radius_km, 'city' => $provider->city]),
                        config('fachora.lead_pricing')
                            ? __('Geen abonnement. Bekijken is gratis; u betaalt alleen voor een aanvraag die u ontgrendelt om te reageren (:min tot :max, naar grootte van de klus). De eerste is gratis.', ['min' => Fachora::money(config('fachora.lead_min')), 'max' => Fachora::money(config('fachora.lead_max'))])
                            : __('Fee: :fee% over uitgevoerde opdrachten; de eerste :days dagen :intro%. Geen abonnement, geen kosten per aanvraag.', ['fee' => config('fachora.fee_percent'), 'days' => config('fachora.intro_days'), 'intro' => config('fachora.intro_fee_percent')]),
                    ], ['label' => __('Naar het panel'), 'url' => $panel]);
                    break;
                case 'suspended':
                    $set(__('Uw profiel is tijdelijk geschorst'), __('Profiel geschorst'), array_values(array_filter([
                        $this->extra['reason'] ?? null,
                        ! empty($this->extra['until']) ? __('De schorsing duurt tot :date. Daarna beoordelen wij opnieuw.', ['date' => $this->extra['until']]) : __('Neem contact met ons op om de schorsing te bespreken.'),
                    ])), ['label' => __('Naar het panel'), 'url' => $panel]);
                    break;
                case 'blocked':
                    $set(__('Uw profiel is verwijderd van Fachora'), __('Profiel verwijderd'), array_values(array_filter([$this->extra['reason'] ?? null])), null);
                    break;
                case 'insurance_expiring':
                    $set(__('Uw verzekering verloopt op :date', ['date' => $this->extra['until'] ?? '']), __('Verzekering verloopt binnenkort'), [
                        __('Uw polis is geldig tot :date. Upload de nieuwe polis in uw panel; zonder geldige polis verdwijnt het vinkje "ubezpieczony" van uw profiel.', ['date' => $this->extra['until'] ?? '']),
                    ], ['label' => __('Naar de verificatie'), 'url' => route('fachora.verification')]);
                    break;
                case 'register_notice':
                    // Art. 14 AVG (1.87): puur informatie, geen reclame. Geen knop naar claimen of prijzen.
                    $set(__('Informatie over de gegevens van :name op Fachora', ['name' => $name]), __('Informatie over uw gegevens'), [
                        __('Wij zijn verplicht u te laten weten dat de gegevens van uw bedrijf :name in de database van het platform Fachora (:domain) staan. Dit is geen reclame; u hoeft niets te doen.', ['name' => $name, 'domain' => brand('domain')]),
                        __('Bron: het openbare register CEIDG. Welke gegevens: naam, NIP, adres, PKD-codes, e-mailadres en telefoonnummer zoals ze in CEIDG staan. Op Fachora tonen we alleen naam, plaats en soort werk, nooit uw e-mail, telefoon of adres.'),
                        __('Doel: klanten in uw regio kunnen uw bedrijf vinden en zelf kiezen om u een concrete aanvraag te sturen. Grondslag: gerechtvaardigd belang (art. 6 lid 1 sub f AVG). We bewaren de gegevens zolang uw bedrijf actief is in CEIDG of tot u bezwaar maakt.'),
                        __('Uw rechten: inzage, correctie, beperking en bezwaar (art. 15–21 AVG), en een klacht bij de toezichthouder (Prezes UODO). Beheerder: :owner, :email.', ['owner' => brand('owner_name') ?: 'Creditline B.V.', 'email' => brand('email') ?: 'czesc@fachora.pl']),
                        __('Wilt u geen aanvragen van klanten per e-mail, of wilt u dat we uw profiel verwijderen? Dat kan met één klik via de link hieronder.'),
                    ], ['label' => __('Geen e-mail meer of profiel verwijderen'), 'url' => $provider->optoutUrl()], array_filter([
                        __('Profiel') => $provider->publicUrl(),
                        __('Privacybeleid') => url('/polityka-prywatnosci'),
                    ]), __('Informatie'));
                    $out['footnote'] = __('U ontvangt dit eenmalige bericht omdat de wet (art. 14 AVG) ons verplicht u te informeren. U krijgt van ons geen reclame.');
                    break;
                case 'invite':
                    $set((config('fachora.lead_pricing') ? __('Nieuwe klanten in uw regio, geen abonnement') : __('Nieuwe klanten in uw regio, betaal alleen voor uitgevoerde opdrachten')), __('Nieuwe klanten in uw regio'), [
                        config('fachora.lead_pricing')
                            ? __('Fachora brengt klanten in contact met gecontroleerde vakmannen. 0 zł abonnement; u betaalt alleen voor een aanvraag waarop u wilt reageren (:min tot :max), en de eerste is gratis.', ['min' => Fachora::money(config('fachora.lead_min')), 'max' => Fachora::money(config('fachora.lead_max'))])
                            : __('Fachora brengt klanten in contact met gecontroleerde vakmannen. 0 zł abonnement, 0 zł per aanvraag; alleen :fee% over een uitgevoerde opdracht.', ['fee' => config('fachora.fee_percent')]),
                        __('Uw bedrijf staat al in onze database. Neem het profiel over, vul werkgebied en talen in en vraag het keurmerk Fachora Verified aan.'),
                        __('U ontvangt deze mail omdat u toestemming gaf voor informatie van partners (:source).', ['source' => $provider->consent_source ?: '-']),
                    ], ['label' => __('Profiel overnemen'), 'url' => route('fachora.claim', ['firma' => $provider->slug])]);
                    break;
            }
        } elseif ($this->audience === 'owner') {
            $adminJob = $job ? route('fachora.admin.jobs.show', $job) : null;
            $adminProvider = $provider ? route('fachora.admin.providers.show', $provider) : null;
            switch ($this->kind) {
                case 'no_match':
                    $set("Fachora: geen bedrijf voor {$ref} ({$job?->categoryName('pl')}, {$job?->city})", 'Geen Verified bedrijf gevonden', [
                        "Voor opdracht {$ref} in {$job?->city} is geen Fachora Verified bedrijf dat kan reageren. Bel bedrijven uit de bellijst op de opdrachtpagina, of bied de opdracht handmatig aan.",
                        $job?->description,
                    ], ['label' => 'Open de opdracht', 'url' => $adminJob], $facts);
                    break;
                case 'verification_requested':
                    $set("Fachora: {$name} vraagt Fachora Verified aan", 'Verificatie aangevraagd', [
                        "{$name} ({$provider?->city}) heeft de verificatie aangevraagd. Controleer de stappen en keur goed, of bel eerst.",
                    ], ['label' => 'Open het bedrijf', 'url' => $adminProvider]);
                    break;
                case 'dispute':
                    $set("Fachora: geschil over {$ref}", 'Geschil geopend', ["De klant opende een geschil over {$ref} ({$name}). Het bedrijf heeft " . (int) config('fachora.dispute_hours', 48) . ' uur om te reageren; daarna beslis jij.'], ['label' => 'Open de opdracht', 'url' => $adminJob]);
                    $out['quote'] = $job?->dispute_reason;
                    break;
                case 'dispute_reply':
                    $set("Fachora: {$name} reageerde op geschil {$ref}", 'Reactie op geschil', ['Beide kanten zijn nu bekend; beslis op de opdrachtpagina.'], ['label' => 'Open de opdracht', 'url' => $adminJob]);
                    $out['quote'] = $job?->dispute_reply;
                    break;
                case 'payout_failed':
                    $set("Fachora: uitbetaling mislukt voor {$ref}", 'Uitbetaling mislukt', ['Stripe weigerde de transfer: ' . ($this->extra['error'] ?? '?'), 'Controleer het Connect-account van het bedrijf en probeer opnieuw vanaf de opdrachtpagina.'], ['label' => 'Open de opdracht', 'url' => $adminJob]);
                    break;
                case 'provider_suspended':
                    $set("Fachora: {$name} automatisch geschorst", 'Bedrijf geschorst', ['Bij de wekelijkse hercontrole stond de NIP niet meer in de btw-lijst. Controleer CEIDG/KRS en herstel of blokkeer.'], ['label' => 'Open het bedrijf', 'url' => $adminProvider]);
                    break;
            }
        }

        if ($out['subject'] === '') {
            $out['subject'] = 'Fachora ' . $ref;
            $out['title'] = 'Fachora';
        }

        return $out;
    }

    /** De feiten van de opdracht, voor de tabel in de mail. @return array<string, string> */
    private function facts(): array
    {
        $job = $this->job;
        if (! $job) {
            return [];
        }
        $urgency = match ($job->urgency) {
            'now' => __('zo snel mogelijk'),
            'week' => __('deze week'),
            'month' => __('binnen een maand'),
            default => $job->wanted_date?->translatedFormat('j F Y') ?? '',
        };
        $languages = (array) config('fachora.languages', []);
        $facts = [
            __('Kenmerk') => $job->reference,
            __('Categorie') => $job->categoryName(),
            __('Plaats') => trim($job->postal_code . ' ' . $job->city),
            __('Wanneer') => trim($urgency . ($job->time_window ? ', ' . $job->time_window : '')),
        ];
        if ($this->audience === 'provider') {
            $facts[__('Taal van de klant')] = ($languages[$job->language]['flag'] ?? '') . ' ' . ($languages[$job->language]['pl'] ?? strtoupper($job->language));
            if ($job->estimate_low !== null && $job->estimate_high !== null) {
                $facts[__('Indicatie')] = Fachora::money($job->estimate_low) . ' – ' . Fachora::money($job->estimate_high);
            }
            if ($this->kind === 'job' || $this->kind === 'reminder') {
                $facts[__('Reageren vóór')] = $job->offers_deadline_at?->translatedFormat('j F, H:i') ?? '';
            }
        }
        if ($job->agreed_amount !== null) {
            $facts[__('Afgesproken')] = Fachora::money($job->agreed_amount) . ' (' . $this->priceType($job->agreed_type) . ')';
        }
        if ($job->scheduled_at) {
            $facts[__('Afspraak')] = $this->appointment();
        }

        return array_filter($facts, fn ($v) => $v !== '' && $v !== null);
    }

    private function appointment(): string
    {
        $job = $this->job;
        if (! $job?->scheduled_at) {
            return __('in overleg');
        }

        return trim($job->scheduled_at->translatedFormat('l j F, H:i') . ($job->scheduled_window ? ' (' . $job->scheduled_window . ')' : ''));
    }

    private function priceType(?string $type): string
    {
        return match ($type) {
            'hourly' => __('uurtarief'),
            'estimate' => __('richtprijs'),
            default => __('vaste prijs'),
        };
    }

    private function resolutionText(): ?string
    {
        $job = $this->job;

        return match ($this->extra['resolution'] ?? $job?->dispute_resolution) {
            'payout' => __('Fachora besliste dat het werk is geleverd zoals afgesproken: het bedrag is uitbetaald aan het bedrijf.'),
            'refund' => __('Fachora besliste in het voordeel van de klant: het volledige bedrag gaat terug naar de klant.'),
            'split' => __('Fachora besliste op een deling: :amount gaat naar het bedrijf, de rest terug naar de klant.', ['amount' => Fachora::money(($job?->payout_amount ?? 0) + ($job?->fee_amount ?? 0))]),
            default => null,
        };
    }

    /** Wat reageren op deze aanvraag kost (1.87): de welkomstlead gratis, daarna de prijs van de lead. */
    private function leadLine(): ?string
    {
        $leads = app(\App\Services\Fachora\LeadService::class);
        $fee = $leads->feeFor($this->offer);
        if ($fee <= 0 && $leads->isFree($this->offer)) {
            return __('Deze aanvraag is voor u gratis (welkomstlead). Bekijken kost nooit iets; u betaalt pas als u een aanvraag ontgrendelt om te reageren.');
        }

        return $fee > 0
            ? __('Bekijken is gratis. Wilt u reageren, dan ontgrendelt u de aanvraag voor :amount. Geen abonnement, geen provisie over uw rekening aan de klant.', ['amount' => Fachora::money($fee)])
            : null;
    }
}
