<?php

/*
 * „Przenieś się z…” — Poolse overstappagina's van Lopra Polska. Per pakket
 * de naam, een intro, de exportstappen in het oude programma (kontrahenci,
 * produkty, faktury) en een opmerking over de export. Gelezen door de route
 * pl.przenies (routes/web.php) en de view lopra-pl/przenies.blade.php.
 *
 * Menupaden zijn bewust generiek beschreven waar de exacte plaats van de
 * exportknop per versie van het programma kan verschillen.
 */
return [

    'packages' => [

        'fakturownia' => [
            'name' => 'Fakturownia',
            'intro' => 'Fakturownia to jeden z najpopularniejszych programów do fakturowania w Polsce. Jeśli oprócz faktur chcesz mieć w jednym miejscu także markę, stronę www i windykację, przeniesienie zajmie Ci mniej więcej kwadrans.',
            'export' => [
                'Kontrahenci: w module Klienci otwórz listę wszystkich klientów i wybierz Eksport → CSV lub XLSX. Zapisz plik na komputerze.',
                'Produkty: w module Produkty wybierz Eksport → CSV lub XLSX. Jeśli nie korzystasz z listy produktów, pomiń ten krok.',
                'Faktury: w module Przychody (Faktury) ustaw filtr na faktury nieopłacone lub częściowo opłacone i wybierz Eksport → CSV lub XLSX. Opłaconych faktur z poprzednich lat nie musisz przenosić.',
            ],
            'notes' => 'Fakturownia eksportuje kwoty netto, VAT i brutto w osobnych kolumnach oraz NIP kontrahenta — Lopra rozpoznaje je automatycznie. Jeśli używasz kilku serii numeracji, ustaw w Lopra numerację tak, żeby kontynuowała Twój ostatni numer.',
        ],

        'ifirma' => [
            'name' => 'iFirma',
            'intro' => 'iFirma łączy fakturowanie z księgowością online. Jeśli księgowość prowadzi Twoja księgowa, a Ty potrzebujesz prostego narzędzia do faktur, marki i pilnowania płatności, Lopra przejmie klientów, produkty i otwarte faktury bez przepisywania.',
            'export' => [
                'Kontrahenci: w module Kontrahenci wybierz opcję eksportu listy do pliku CSV lub XLSX i zapisz plik.',
                'Produkty (towary i usługi): w module Produkty lub Towary i usługi wybierz eksport do CSV lub XLSX.',
                'Faktury: w module Faktury (Sprzedaż) zawęź listę do faktur nieopłaconych i wyeksportuj ją do CSV lub XLSX. Historia opłaconych faktur zostaje w iFirmie.',
            ],
            'notes' => 'W eksporcie z iFirmy nazwa kontrahenta i NIP są w osobnych kolumnach, a termin płatności jest podany w formacie dd.mm.rrrr — Lopra odczytuje oba warianty. Sprawdź, czy w pliku faktur jest kolumna z kwotą brutto; jeśli jest tylko netto i VAT, Lopra policzy brutto sama.',
        ],

        'wfirma' => [
            'name' => 'wFirma',
            'intro' => 'wFirma to rozbudowany system do faktur, księgowości i kadr. Wielu użytkowników korzysta z niego wyłącznie do wystawiania faktur — i płaci za funkcje, których nie używa. W Lopra dostajesz faktury, markę, stronę www i windykację w jednym abonamencie.',
            'export' => [
                'Kontrahenci: w module CRM → Kontrahenci zaznacz wszystkich kontrahentów i wybierz Eksport → CSV lub XLSX.',
                'Produkty: w module Magazyn (Produkty) wybierz eksport listy produktów do CSV lub XLSX.',
                'Faktury: w module Przychody → Sprzedaż przefiltruj listę po statusie „nieopłacone” i wyeksportuj ją do CSV lub XLSX.',
            ],
            'notes' => 'wFirma może wyeksportować także dane, których nie potrzebujesz (np. kolumny magazynowe) — Lopra je zignoruje. Jeśli w pliku faktur jest kolumna „Zapłacono” z kwotą częściowej wpłaty, Lopra zapisze ją przy fakturze.',
        ],

        'infakt' => [
            'name' => 'inFakt',
            'intro' => 'inFakt to program do faktur połączony z usługami księgowymi. Jeśli chcesz mieć narzędzie, które poza fakturami zbuduje Twoją markę, postawi stronę www i pomoże odzyskać niezapłacone należności, przeniesienie z inFaktu zajmie Ci kwadrans.',
            'export' => [
                'Klienci: w module Klienci wybierz opcję eksportu listy do pliku CSV lub XLS i zapisz plik.',
                'Produkty: w module Produkty wybierz eksport do CSV lub XLS. Jeśli nie prowadzisz listy produktów, pomiń ten krok.',
                'Faktury: w module Faktury (Przychody) zawęź listę do faktur nieopłaconych i wyeksportuj ją do CSV lub XLS.',
            ],
            'notes' => 'Pliki XLS (starszy format Excela) najlepiej zapisać w Excelu lub LibreOffice jako XLSX albo CSV przed wgraniem do Lopra. NIP, kwoty i terminy płatności są w eksporcie inFaktu w osobnych kolumnach, więc rozpoznanie przebiega automatycznie.',
        ],

    ],

];
