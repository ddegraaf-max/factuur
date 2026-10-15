<?php

namespace App\Services\Fachora;

use App\Models\Company;
use App\Models\Provider;
use App\Models\ProviderCheck;
use App\Models\ServiceJob;
use App\Models\ServiceJobEvent;
use App\Models\ServiceJobExtra;
use App\Models\ServiceJobOffer;
use App\Models\ServiceReview;
use App\Models\User;
use App\Services\DemoDataBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demo van het Fachora-bedrijvenpanel: een fictief, geverifieerd bedrijf
 * (Hydro-Serwis Nowak, Warschau) met een eigen inlog en zaken in elke fase:
 * twee nieuwe aanvragen om te prijzen, een uitgebrachte prijs, een geplande
 * en een lopende klus met voorgesteld meerwerk, een klus die op akkoord
 * wacht, afgeronde klussen met beoordelingen en één afzegging.
 *
 * De administratie is een gewone demo-administratie (is_demo, 24 uur), dus
 * DemoMode en DemoCleaner doen hun werk; de klanten bestaan niet en hebben
 * een adres op ServiceJob::DEMO_DOMAIN, zodat de Notifier ze overslaat.
 */
class DemoBuilder extends DemoDataBuilder
{
    public const COMPANY_NAME = 'Hydro-Serwis Nowak';

    private Provider $provider;

    public function build(): User
    {
        $this->d = $this->datasetPl();
        $this->d['company'] = array_merge($this->d['company'], [
            'name' => 'Hydro-Serwis Marek Nowak',
            'trading_name' => self::COMPANY_NAME,
            'email' => 'kontakt@hydro-serwis-nowak.pl',
            'phone' => '+48 500 000 001',
            'website' => 'www.hydro-serwis-nowak.pl',
            'address_line' => 'ul. Puławska 12/3',
            'postal_code' => '02-566',
            'city' => 'Warszawa',
            'brand_color' => '#0B2545',
            'accent_color' => '#F4781F',
        ]);
        $this->d['user_name'] = 'Marek Nowak';

        return DB::transaction(function () {
            $company = $this->createCompany();
            $user = $this->createUser($company);
            $user->forceFill(['locale' => app()->getLocale() === 'en' ? 'en' : 'pl'])->save();

            $this->provider = $this->createProvider($company, $user);
            $this->createJobs();

            return $user;
        });
    }

    /**
     * Ruimt het demo-bedrijf en zijn zaken op (platformtabellen zonder
     * company-scope, dus buiten CompanyPurger om). Alleen demo-zaken: een
     * echte zaak die ooit aan dit profiel hing blijft staan (provider_id → null).
     */
    public static function cleanup(Company $company): void
    {
        if (! $company->is_demo) {
            return;
        }

        $providerIds = Provider::query()->where('company_id', $company->id)->pluck('id');
        if ($providerIds->isEmpty()) {
            return;
        }

        $offerJobIds = ServiceJobOffer::query()->whereIn('provider_id', $providerIds)->pluck('service_job_id');
        $jobIds = ServiceJob::query()
            ->where(fn ($q) => $q->whereIn('provider_id', $providerIds)->orWhereIn('id', $offerJobIds))
            ->where('customer_email', 'like', '%@' . ServiceJob::DEMO_DOMAIN)
            ->pluck('id');

        DB::transaction(function () use ($providerIds, $jobIds) {
            if ($jobIds->isNotEmpty()) {
                DB::table('fachora_files')->where('owner_type', 'job')->whereIn('owner_id', $jobIds)->delete();
                ServiceJob::query()->whereIn('id', $jobIds)->delete(); // offers, events, extras en reviews cascaden
            }
            DB::table('fachora_files')->where('owner_type', 'provider')->whereIn('owner_id', $providerIds)->delete();
            Provider::query()->whereIn('id', $providerIds)->delete(); // categorieën en checks cascaden
        });
    }

    protected function createProvider(Company $company, User $user): Provider
    {
        $claimed = now()->subMonths(4);
        $verified = $claimed->copy()->addDays(3);

        $provider = Provider::query()->forceCreate([
            'nip' => $this->demoNip(),
            'legal_name' => 'Hydro-Serwis Marek Nowak',
            'display_name' => self::COMPANY_NAME,
            'slug' => Provider::makeSlug(self::COMPANY_NAME . ' demo ' . Str::lower(Str::random(4)), 'Warszawa'),
            'legal_form' => 'jdg',
            'address_line' => 'ul. Puławska 12/3',
            'postal_code' => '02-566',
            'city' => 'Warszawa',
            'voivodeship' => 'mazowieckie',
            'lat' => 52.2000,
            'lng' => 21.0230,
            'phone' => '+48 500 000 001',
            'email' => $user->email,
            'source' => 'claimed',
            'source_fetched_at' => $claimed,
            'registry_status' => 'active',
            'vat_status' => 'active',
            'vat_checked_at' => now()->subDays(3),
            'status' => 'verified',
            'languages' => ['pl' => 'native', 'en' => 'b2', 'ua' => 'b1'],
            'radius_km' => 30,
            'rates' => ['callout' => 90, 'first_hour' => 160, 'extra_hour' => 120],
            'warranty_months' => 12,
            'invoice_promise' => true,
            'protect_opt_in' => true,
            'description' => 'Hydraulika, ogrzewanie i klimatyzacja w Warszawie i okolicach od 2012 roku. Awarie tego samego dnia, przeglądy kotłów, montaż klimatyzacji. Rozmawiam po polsku, angielsku i trochę po ukraińsku; faktura do każdej pracy.',
            'company_id' => $company->id,
            'user_id' => $user->id,
            'claim_code' => Provider::makeClaimCode(),
            'claim_email' => $user->email,
            'claim_name' => 'Marek Nowak',
            'claim_requested_at' => $claimed->copy()->subHours(2),
            'claimed_at' => $claimed,
            'consent_at' => $claimed,
            'consent_source' => 'claim',
            'optout_token' => bin2hex(random_bytes(32)),
            'email_verified_at' => $claimed,
            'phone_verified_at' => $claimed->copy()->addMinutes(5),
            'terms_accepted_at' => $claimed->copy()->addMinutes(8),
            'terms_version' => (string) config('fachora.terms_version'),
            'stripe_account_id' => 'acct_demo_' . Str::lower(Str::random(12)),
            'stripe_charges_enabled' => true,
            'stripe_payouts_enabled' => true,
            'stripe_checked_at' => now()->subDay(),
            'insurance_until' => now()->addMonths(8)->toDateString(),
            'insurance_note' => 'OC działalności, PZU, suma 1 000 000 zł',
            'verification_requested_at' => $claimed->copy()->addMinutes(10),
            'verified_at' => $verified,
            'rating_avg' => 4.75,
            'rating_count' => 4,
            'jobs_done' => 6,
            'response_minutes' => 35,
            'created_at' => $claimed->copy()->subDays(20),
            'updated_at' => now(),
        ]);
        $provider->syncCategories(['hydraulik', 'ogrzewanie', 'klimatyzacja']);

        $notes = [
            'registry' => 'CEIDG: wpis aktywny (demo)',
            'vat' => 'Biała lista: podatnik VAT czynny (demo)',
            'email' => 'Potwierdzony linkiem',
            'phone' => 'Potwierdzony kodem SMS',
            'terms' => 'Standard Fachora ' . config('fachora.terms_version'),
            'bank' => 'Stripe: wypłaty włączone (demo)',
            'insurance' => 'OC działalności ważne do ' . now()->addMonths(8)->format('d.m.Y'),
        ];
        foreach (Provider::CHECKS as $kind) {
            ProviderCheck::create([
                'provider_id' => $provider->id, 'kind' => $kind, 'status' => 'passed',
                'evidence' => ['demo' => true], 'note' => $notes[$kind] ?? null, 'checked_at' => $verified,
            ]);
        }

        return $provider->refresh();
    }

    /** Dertien zaken, van nieuw tot afgezegd, met tijdstippen rond "nu". */
    protected function createJobs(): void
    {
        $now = now();
        $pid = $this->provider->id;

        // 1. Nieuw aangeboden (3 uur geleden), nog zonder prijs.
        $j = $this->job([
            'category' => 'hydraulik', 'title' => 'Cieknąca bateria w kuchni',
            'description' => 'Bateria w kuchni kapie nawet po dokręceniu, pod szafką zbiera się woda. Mieszkanie w bloku na Mokotowie, 3. piętro, winda jest.',
            'urgency' => 'week', 'customer_name' => 'Anna Wiśniewska', 'postal_code' => '02-591',
            'estimate_low' => 150, 'estimate_high' => 300,
            'ai' => ['title_pl' => 'Cieknąca bateria w kuchni', 'summary_en' => 'Dripping kitchen tap, water collecting under the cabinet. Block of flats in Mokotów, 3rd floor, lift available.',
                'questions_pl' => ['Czy bateria jest jednouchwytowa czy dwuzaworowa?', 'Czy woda zbiera się stale, czy tylko przy odkręconej wodzie?']],
            'offered_at' => $now->copy()->subHours(3), 'offers_deadline_at' => $now->copy()->subHours(3)->addHours(config('fachora.offer_hours', 48)), 'waves' => 1,
        ], $now->copy()->subHours(3)->subMinutes(10), [
            ['created', 'customer', 'Zlecenie złożone'], ['confirmed', 'customer', 'Adres e-mail potwierdzony'],
        ]);
        $this->offer($j, ['sent_at' => $now->copy()->subHours(3)]);

        // 2. Nieuw, pilne, klant pisze po angielsku (40 minut temu).
        $j = $this->job([
            'category' => 'ogrzewanie', 'title' => 'No hot water, boiler shows error E03',
            'description' => 'Our gas boiler (Vaillant, about 8 years old) stopped giving hot water this morning and shows error E03. Heating seems to work. We are a family with a small child, so fairly urgent. Flat in Wola, 5th floor.',
            'description_pl' => 'Kocioł gazowy (Vaillant, ok. 8 lat) od rana nie daje ciepłej wody i pokazuje błąd E03. Ogrzewanie chyba działa. Rodzina z małym dzieckiem, sprawa dość pilna. Mieszkanie na Woli, 5. piętro.',
            'urgency' => 'now', 'language' => 'en', 'customer_locale' => 'en', 'customer_name' => 'James Carter', 'postal_code' => '01-171',
            'estimate_low' => 200, 'estimate_high' => 450,
            'ai' => ['title_pl' => 'Brak ciepłej wody, kocioł pokazuje błąd E03', 'summary_en' => 'Gas boiler (Vaillant, ~8 years) gives no hot water and shows E03; heating works. Family with a small child, urgent. Wola, 5th floor.',
                'questions_pl' => ['Czy kocioł jest dwufunkcyjny, czy z zasobnikiem?', 'Kiedy był ostatni przegląd?']],
            'offered_at' => $now->copy()->subMinutes(35), 'offers_deadline_at' => $now->copy()->subMinutes(35)->addHours(config('fachora.offer_hours', 48)), 'waves' => 1,
        ], $now->copy()->subMinutes(40), [
            ['created', 'customer', 'Zlecenie złożone (po angielsku, przetłumaczone)'], ['confirmed', 'customer', 'Adres e-mail potwierdzony'],
        ]);
        $this->offer($j, ['sent_at' => $now->copy()->subMinutes(35)]);

        // 3. Prijs uitgebracht, klant moet nog kiezen.
        $j = $this->job([
            'category' => 'klimatyzacja', 'title' => 'Montaż klimatyzacji w sypialni', 'status' => 'quoted',
            'description' => 'Chcę zamontować klimatyzator split (ok. 3,5 kW) w sypialni ok. 16 m². Jednostka zewnętrzna na balkonie, odległość ok. 4 m. Blok z 2010 r., Ursynów. Proszę o cenę z urządzeniem i bez.',
            'urgency' => 'month', 'customer_name' => 'Tomasz Kamiński', 'postal_code' => '02-776',
            'estimate_low' => 2500, 'estimate_high' => 4500,
            'ai' => ['title_pl' => 'Montaż klimatyzacji w sypialni', 'summary_en' => 'Install a 3.5 kW split air conditioner in a 16 m² bedroom; outdoor unit on the balcony, about 4 m away. Ursynów, building from 2010.',
                'questions_pl' => ['Czy jest zgoda wspólnoty na jednostkę zewnętrzną?']],
            'offered_at' => $now->copy()->subDays(2), 'offers_deadline_at' => $now->copy()->subDays(2)->addHours(config('fachora.offer_hours', 48)), 'waves' => 1,
        ], $now->copy()->subDays(2)->subMinutes(10), [
            ['created', 'customer', 'Zlecenie złożone'], ['confirmed', 'customer', 'Adres e-mail potwierdzony'],
            ['responded', 'provider', 'Wycena wysłana: 3 200 zł (cena stała)', $now->copy()->subDay()],
        ]);
        $this->offer($j, [
            'status' => 'responded', 'sent_at' => $now->copy()->subDays(2), 'opened_at' => $now->copy()->subDay()->subHour(), 'responded_at' => $now->copy()->subDay(),
            'price' => 3200, 'price_type' => 'fixed', 'available_at' => $now->copy()->addDays(4)->setTime(9, 0), 'available_note' => 'przedpołudniem',
            'remarks' => 'Cena za montaż z materiałami (trasa do 5 m, wspornik, przebicie, próba szczelności). Urządzenie klasy A++ (np. Gree lub Haier 3,5 kW) dodatkowo 2 600 zł. Montaż w jeden dzień, gwarancja 24 miesiące na montaż.',
        ]);

        // 4. Gepland voor morgen 10:00, betaald via Fachora Protect.
        $created = $now->copy()->subDays(5);
        $j = $this->withProvider([
            'category' => 'ogrzewanie', 'title' => 'Wymiana grzejnika w salonie', 'status' => 'scheduled',
            'description' => 'Stary żeliwny grzejnik w salonie przecieka przy zaworze. Chcę wymienić na nowy panelowy (mam już kupiony, 600x1200). Instalacja w bloku, zawory odcinające są. Praga-Północ.',
            'urgency' => 'week', 'language' => 'ua', 'customer_locale' => 'en', 'customer_name' => 'Olena Kovalenko', 'postal_code' => '03-450', 'address_line' => 'ul. Targowa 15/8',
            'ai' => ['title_pl' => 'Wymiana grzejnika w salonie', 'summary_en' => 'Replace a leaking cast-iron radiator with a new 600x1200 panel radiator (already bought). Shut-off valves present. Praga-Północ.', 'questions_pl' => []],
            'agreed_at' => $created->copy()->addDays(2), 'scheduled_at' => $now->copy()->addDay()->setTime(10, 0), 'scheduled_window' => '10:00–12:00',
            'protect' => true, 'paid_at' => $created->copy()->addDays(2)->addMinutes(12), 'stripe_session_id' => 'cs_demo_' . Str::lower(Str::random(16)), 'stripe_payment_intent' => 'pi_demo_' . Str::lower(Str::random(16)),
            'fee_percent' => 10, 'fee_amount' => 85, 'payout_amount' => 765,
        ], $created, 850, 'fixed', 'Demontaż starego grzejnika, montaż nowego panelowego 600x1200 z zaworami termostatycznymi, napełnienie i odpowietrzenie. Materiały: zestaw przyłączeniowy.', [
            ['responded', 'provider', 'Wycena wysłana: 850 zł (cena stała)', $created->copy()->addDay()],
            ['chosen', 'customer', 'Klient wybrał Twoją ofertę', $created->copy()->addDays(2)],
            ['reserved', 'customer', 'Klient zapłacił 850 zł przez Fachora Protect; kwota zarezerwowana do odbioru pracy', $created->copy()->addDays(2)->addMinutes(12)],
        ]);

        // 5. In uitvoering (vandaag), zonder Protect, met voorgesteld meerwerk.
        $created = $now->copy()->subHours(4);
        $j = $this->withProvider([
            'category' => 'hydraulik', 'title' => 'Awaria pionu wodnego, zalewa łazienkę', 'status' => 'in_progress',
            'description' => 'Przy zaworze głównym w łazience tryska woda, zakręciłem zawór na klatce. Potrzebny ktoś dziś. Śródmieście, kamienica, 2. piętro.',
            'urgency' => 'now', 'customer_name' => 'Piotr Zieliński', 'postal_code' => '00-682', 'address_line' => 'ul. Hoża 42/5',
            'ai' => ['title_pl' => 'Awaria pionu wodnego, zalewa łazienkę', 'summary_en' => 'Water spraying at the main bathroom valve; riser shut off on the landing. Needed today. Śródmieście, tenement, 2nd floor.', 'questions_pl' => []],
            'agreed_at' => $created->copy()->addHour(), 'scheduled_at' => $now->copy()->subHours(2), 'scheduled_window' => 'dziś, ok. 12:00',
            'protect' => false, 'started_at' => $now->copy()->subHours(2),
        ], $created, 1200, 'estimate', 'Wymiana pękniętego zaworu i odcinka pionu (ok. 1 m), uszczelnienie. Robocizna 160 zł/h plus materiały; szacunek 900–1 200 zł.', [
            ['responded', 'provider', 'Wycena wysłana: szacunek 900–1 200 zł, dojazd w 1,5 h', $created->copy()->addMinutes(30)],
            ['chosen', 'customer', 'Klient wybrał Twoją ofertę (płatność bezpośrednio po pracy)', $created->copy()->addHour()],
            ['started', 'provider', 'Prace rozpoczęte', $now->copy()->subHours(2)],
            ['extra_proposed', 'provider', 'Zaproponowano pracę dodatkową: wymiana zaworu odcinającego pod umywalką, 180 zł', $now->copy()->subMinutes(25)],
        ], ['hourly' => 160, 'callout' => 90, 'available_note' => 'mogę być za 1,5 h']);
        ServiceJobExtra::query()->forceCreate([
            'service_job_id' => $j->id, 'description' => 'Wymiana zaworu odcinającego pod umywalką (skorodowany)', 'amount' => 180, 'status' => 'proposed',
            'created_at' => $now->copy()->subMinutes(25), 'updated_at' => $now->copy()->subMinutes(25),
        ]);

        // 6. Gereed gemeld, wacht op akkoord van de klant (Protect).
        $created = $now->copy()->subDays(9);
        $this->withProvider([
            'category' => 'ogrzewanie', 'title' => 'Przegląd kotła gazowego przed sezonem', 'status' => 'done',
            'description' => 'Coroczny przegląd kotła Junkers Cerapur, dwufunkcyjny. Mieszkanie na Bielanach, dostęp w tygodniu po 16:00.',
            'urgency' => 'month', 'customer_name' => 'Katarzyna Mazur', 'postal_code' => '01-918', 'address_line' => 'ul. Kasprowicza 60/12',
            'ai' => ['title_pl' => 'Przegląd kotła gazowego przed sezonem', 'summary_en' => 'Annual service of a Junkers Cerapur combi boiler. Bielany, weekdays after 16:00.', 'questions_pl' => []],
            'agreed_at' => $created->copy()->addDays(2), 'scheduled_at' => $now->copy()->subDay()->setTime(9, 0), 'scheduled_window' => '9:00–11:00',
            'protect' => true, 'paid_at' => $created->copy()->addDays(2)->addMinutes(7), 'stripe_session_id' => 'cs_demo_' . Str::lower(Str::random(16)), 'stripe_payment_intent' => 'pi_demo_' . Str::lower(Str::random(16)),
            'fee_percent' => 10, 'fee_amount' => 35, 'payout_amount' => 315,
            'started_at' => $now->copy()->subDay()->setTime(9, 5), 'done_at' => $now->copy()->subDay()->setTime(10, 40),
            'done_note' => 'Przegląd wykonany, spaliny w normie, wymieniona uszczelka syfonu kondensatu (w cenie). Wpis w książce serwisowej.',
        ], $created, 350, 'fixed', 'Przegląd roczny: czyszczenie wymiennika i palnika, kontrola szczelności, analiza spalin, wpis do książki serwisowej.', [
            ['chosen', 'customer', 'Klient wybrał Twoją ofertę', $created->copy()->addDays(2)],
            ['reserved', 'customer', 'Klient zapłacił 350 zł przez Fachora Protect', $created->copy()->addDays(2)->addMinutes(7)],
            ['started', 'provider', 'Prace rozpoczęte', $now->copy()->subDay()->setTime(9, 5)],
            ['done', 'provider', 'Praca oznaczona jako wykonana; klient ma ' . config('fachora.auto_accept_days', 5) . ' dni na odbiór', $now->copy()->subDay()->setTime(10, 40)],
        ]);

        // 7–10. Afgerond en beoordeeld.
        $this->accepted(['category' => 'hydraulik', 'title' => 'Zatkany odpływ w prysznicu', 'customer_name' => 'Marta Nowicka', 'postal_code' => '02-495',
            'description' => 'Woda w brodziku odpływa bardzo wolno, domowe sposoby nie pomagają. Włochy, dom jednorodzinny.'],
            $now->copy()->subDays(12), 220, 'Udrożnienie odpływu sprężyną, czyszczenie syfonu.', 5, 'Szybko, czysto i w uzgodnionej cenie. Polecam!', 'Dziękuję, w razie czego zapraszam ponownie.');
        $this->accepted(['category' => 'ogrzewanie', 'title' => 'Two radiators stay cold', 'language' => 'en', 'customer_locale' => 'en', 'customer_name' => 'Daniel Brooks', 'postal_code' => '00-131',
            'description' => 'Two radiators in the living room stay cold while the others are hot. Probably need bleeding or balancing. Śródmieście, 4th floor.',
            'description_pl' => 'Dwa grzejniki w salonie pozostają zimne, pozostałe grzeją. Prawdopodobnie odpowietrzenie lub regulacja. Śródmieście, 4. piętro.'],
            $now->copy()->subDays(20), 280, 'Odpowietrzenie instalacji, regulacja zaworów, kontrola ciśnienia.', 5, 'Came on time, explained everything in English and fixed both radiators within an hour. Great service.', null);
        $this->accepted(['category' => 'hydraulik', 'title' => 'Montaż zmywarki i podłączenie wody', 'customer_name' => 'Agnieszka Lis', 'postal_code' => '03-287',
            'description' => 'Nowa zmywarka do zabudowy, trzeba podłączyć wodę i odpływ, w kuchni jest już trójnik. Białołęka.'],
            $now->copy()->subDays(27), 300, 'Podłączenie zmywarki do wody i kanalizacji, próba szczelności, montaż frontu.', 4, 'Wszystko działa. Lekkie spóźnienie (20 minut), ale uprzedził telefonicznie.', 'Przepraszam za spóźnienie, korek na Moście Grota. Dziękuję za wyrozumiałość.');
        $this->accepted(['category' => 'klimatyzacja', 'title' => 'Serwis i czyszczenie klimatyzacji', 'customer_name' => 'Rafał Wójcik', 'postal_code' => '02-222',
            'description' => 'Klimatyzator słabo chłodzi i brzydko pachnie, dwa lata bez serwisu. Ochota.'],
            $now->copy()->subDays(40), 260, 'Czyszczenie i dezynfekcja parownika, filtrów i jednostki zewnętrznej, kontrola czynnika.', 5, 'Profesjonalnie, klimatyzacja znów chłodzi jak nowa.', null, 'auto');

        // 11–12. Afgerond zonder beoordeling.
        $this->accepted(['category' => 'hydraulik', 'title' => 'Wymiana spłuczki podtynkowej', 'customer_name' => 'Michał Krawczyk', 'postal_code' => '01-460',
            'description' => 'Spłuczka podtynkowa Geberit przecieka do muszli, przycisk się zacina. Bemowo.'],
            $now->copy()->subDays(50), 420, 'Wymiana mechanizmu spłuczki i zaworu napełniającego, regulacja.');
        $this->accepted(['category' => 'ogrzewanie', 'title' => 'Naprawa kotła: wymiana czujnika', 'customer_name' => 'Joanna Pawlak', 'postal_code' => '04-128',
            'description' => 'Kocioł Viessmann gaśnie i pokazuje błąd F.04. Grochów.'],
            $now->copy()->subDays(62), 390, 'Diagnostyka, wymiana czujnika NTC, test pracy kotła.');

        // 13. Afgezegd door de klant.
        $created = $now->copy()->subDays(15);
        $this->withProvider([
            'category' => 'hydraulik', 'title' => 'Wymiana baterii prysznicowej', 'status' => 'cancelled',
            'description' => 'Bateria termostatyczna w kabinie nie trzyma temperatury. Mam nową. Wawer.',
            'customer_name' => 'Ewa Kaczmarek', 'postal_code' => '04-667', 'address_line' => 'ul. Trakt Lubelski 120',
            'ai' => ['title_pl' => 'Wymiana baterii prysznicowej', 'summary_en' => 'Replace a thermostatic shower mixer (customer has the new one). Wawer.', 'questions_pl' => []],
            'agreed_at' => $created->copy()->addDay(), 'scheduled_at' => $created->copy()->addDays(3)->setTime(16, 0), 'scheduled_window' => '16:00–18:00',
            'protect' => false, 'cancelled_at' => $created->copy()->addDay()->addHours(6), 'cancelled_by' => 'customer', 'cancel_reason' => 'Sąsiad naprawił, przepraszam za kłopot.',
        ], $created, 260, 'fixed', 'Wymiana baterii prysznicowej na dostarczoną przez klienta, uszczelnienie.', [
            ['chosen', 'customer', 'Klient wybrał Twoją ofertę', $created->copy()->addDay()],
            ['cancelled', 'customer', 'Klient odwołał zlecenie: „Sąsiad naprawił, przepraszam za kłopot.”', $created->copy()->addDay()->addHours(6)],
        ]);
    }

    /** Afgeronde en geaccepteerde klus, optioneel met beoordeling en antwoord. */
    private function accepted(array $attrs, Carbon $created, float $amount, string $scope, ?int $rating = null, ?string $text = null, ?string $reply = null, string $acceptedBy = 'customer'): ServiceJob
    {
        $agreed = $created->copy()->addDay();
        $visit = $created->copy()->addDays(2)->setTime(10, 0);
        $accepted = $visit->copy()->addHours($acceptedBy === 'auto' ? 24 * (int) config('fachora.auto_accept_days', 5) : 5);

        $job = $this->withProvider($attrs + [
            'status' => 'accepted', 'urgency' => 'week',
            'ai' => ['title_pl' => $attrs['title'], 'summary_en' => $attrs['description_pl'] ?? $attrs['description'], 'questions_pl' => []],
            'agreed_at' => $agreed, 'scheduled_at' => $visit, 'scheduled_window' => '10:00–12:00',
            'protect' => true, 'paid_at' => $agreed->copy()->addMinutes(9), 'stripe_session_id' => 'cs_demo_' . Str::lower(Str::random(16)), 'stripe_payment_intent' => 'pi_demo_' . Str::lower(Str::random(16)),
            'stripe_transfer_id' => 'tr_demo_' . Str::lower(Str::random(16)),
            'fee_percent' => 10, 'fee_amount' => round($amount * 0.10, 2), 'payout_amount' => round($amount * 0.90, 2),
            'started_at' => $visit->copy()->addMinutes(5), 'done_at' => $visit->copy()->addHour(), 'done_note' => 'Praca wykonana zgodnie z uzgodnionym zakresem.',
            'accepted_at' => $accepted, 'accepted_by' => $acceptedBy, 'payout_at' => $accepted->copy()->addMinutes(2),
            'reviewed_at' => $rating ? $accepted->copy()->addHours(3) : null,
        ], $created, $amount, 'fixed', $scope, [
            ['chosen', 'customer', 'Klient wybrał Twoją ofertę', $agreed],
            ['reserved', 'customer', 'Klient zapłacił ' . number_format($amount, 0, ',', ' ') . ' zł przez Fachora Protect', $agreed->copy()->addMinutes(9)],
            ['done', 'provider', 'Praca oznaczona jako wykonana', $visit->copy()->addHour()],
            [$acceptedBy === 'auto' ? 'accepted_auto' : 'accepted', $acceptedBy === 'auto' ? 'system' : 'customer', $acceptedBy === 'auto' ? 'Odbiór automatyczny po upływie terminu' : 'Klient odebrał pracę', $accepted],
            ['payout', 'system', 'Wypłata ' . number_format($amount * 0.90, 0, ',', ' ') . ' zł zlecona na Twoje konto', $accepted->copy()->addMinutes(2)],
        ]);

        if ($rating) {
            ServiceReview::query()->forceCreate([
                'service_job_id' => $job->id, 'provider_id' => $this->provider->id, 'rating' => $rating, 'text' => $text,
                'reply' => $reply, 'replied_at' => $reply ? $accepted->copy()->addHours(5) : null, 'published' => true,
                'created_at' => $accepted->copy()->addHours(3), 'updated_at' => $accepted->copy()->addHours(3),
            ]);
        }

        return $job;
    }

    /** Zaak die aan het demo-bedrijf is gegund, met de gekozen offerte en de gebeurtenissen. */
    private function withProvider(array $attrs, Carbon $created, float $amount, string $type, string $scope, array $events = [], array $offer = []): ServiceJob
    {
        $agreedAt = $attrs['agreed_at'] ?? $created->copy()->addDay();
        $job = $this->job($attrs + [
            'provider_id' => $this->provider->id,
            'offered_at' => $created->copy()->addMinutes(6),
            'offers_deadline_at' => $created->copy()->addMinutes(6)->addHours(config('fachora.offer_hours', 48)),
            'waves' => 1,
            'agreed_amount' => $amount, 'agreed_type' => $type, 'agreed_scope' => $scope, 'warranty_months' => 12,
        ], $created, array_merge([
            ['created', 'customer', 'Zlecenie złożone'], ['confirmed', 'customer', 'Adres e-mail potwierdzony'],
        ], $events));

        $chosen = $this->offer($job, $offer + [
            'status' => 'chosen', 'sent_at' => $created->copy()->addMinutes(6), 'opened_at' => $created->copy()->addMinutes(40),
            'responded_at' => $agreedAt->copy()->subHours(3), 'price' => $amount, 'price_type' => $type,
            'available_at' => $attrs['scheduled_at'] ?? null, 'remarks' => $scope,
        ]);
        $job->forceFill(['chosen_offer_id' => $chosen->id])->save();

        return $job;
    }

    /** @param array<int, array{0: string, 1: string, 2: string, 3?: Carbon}> $events */
    private function job(array $attrs, Carbon $created, array $events = []): ServiceJob
    {
        $job = ServiceJob::query()->forceCreate($attrs + [
            'reference' => ServiceJob::nextReference(),
            'token' => bin2hex(random_bytes(32)),
            'status' => 'open',
            'urgency' => 'week',
            'language' => 'pl',
            'customer_locale' => 'pl',
            'customer_email' => Str::slug($attrs['customer_name']) . '@' . ServiceJob::DEMO_DOMAIN,
            'customer_phone' => '+48 500 000 0' . random_int(10, 99),
            'city' => 'Warszawa',
            'lat' => 52.2297,
            'lng' => 21.0122,
            'confirmed_at' => $created->copy()->addMinutes(4),
            'confirm_mails' => 1,
            'created_at' => $created,
            'updated_at' => $created,
        ]);

        $minute = 0;
        foreach ($events as $event) {
            [$kind, $actor, $description] = $event;
            $at = $event[3] ?? $created->copy()->addMinutes($minute += 2);
            ServiceJobEvent::create(['service_job_id' => $job->id, 'kind' => $kind, 'actor' => $actor, 'description' => $description, 'created_at' => $at]);
        }

        return $job;
    }

    private function offer(ServiceJob $job, array $attrs): ServiceJobOffer
    {
        return ServiceJobOffer::query()->forceCreate($attrs + [
            'service_job_id' => $job->id,
            'provider_id' => $this->provider->id,
            'token' => bin2hex(random_bytes(32)),
            'status' => 'sent',
            'wave' => 1,
            'sent_at' => $job->offered_at ?? $job->created_at,
            'created_at' => $attrs['sent_at'] ?? $job->created_at,
            'updated_at' => $attrs['responded_at'] ?? $attrs['sent_at'] ?? $job->created_at,
        ]);
    }

    /** Fictief NIP met geldig controlecijfer en het niet-bestaande kantoorvoorvoegsel 000. */
    private function demoNip(): string
    {
        do {
            $digits = '000' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $sum = 0;
            foreach ([6, 5, 7, 2, 3, 4, 5, 6, 7] as $i => $weight) {
                $sum += $weight * (int) $digits[$i];
            }
            $check = $sum % 11;
        } while ($check === 10 || Provider::query()->where('nip', $digits . $check)->exists());

        return $digits . $check;
    }
}
