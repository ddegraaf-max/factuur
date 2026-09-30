<?php

namespace App\Support;

/**
 * Het Referentie GrootboekSchema (RGS), zoals het echt is.
 *
 * ── Waarom een aparte klasse om een lijstje te lezen ──────────────────────
 *
 * Omdat het niet één lijstje is maar een boom, en omdat de rest van het pakket
 * op een handvol rekeningen moet kunnen rekenen zonder dat er ergens een
 * rekeningnummer hardgecodeerd staat. Een factuur moet op "handelsdebiteuren"
 * boeken; welk nummer dat bij deze administratie is, mag de factuur niet weten.
 * Daarom verwijst alles naar een RGS-code, en zet deze klasse die om.
 *
 * De lijst zelf staat in resources/data/rgs33.php en is gegenereerd uit de
 * officiële werkmap; zie de kop van dat bestand. Hier staat niets verzonnen.
 */
class Rgs
{
    /**
     * De rekeningen die het pakket zelf gebruikt. Alles wat automatisch boekt
     * verwijst hiernaar, nooit naar een nummer.
     *
     * Ze staan hier bij elkaar zodat in één oogopslag te zien is waar een
     * boeking terechtkomt, en zodat een wijziging op één plek gebeurt.
     */
    public const DEBITEUREN = 'BVorDebHad';        // 1101010
    public const CREDITEUREN = 'BSchCreHac';       // 1203010
    public const BANK = 'BLimBanRba';              // 1002010
    public const KAS = 'BLimKasKas';               // 1001010
    public const KRUISPOST = 'BLimKruSto';         // 1003010 stortingen onderweg

    /*
     * Btw staat in RGS op één rekening (1205010) met een subrekening per
     * rubriek van de aangifte. Dat is precies wat we willen: de aangifte is dan
     * een som per subrekening en niet een herberekening uit de facturen.
     */
    public const BTW_1A_HOOG = 'BSchBepBtwOla';    // 1205010.02
    public const BTW_1B_LAAG = 'BSchBepBtwOlv';    // 1205010.03
    public const BTW_1C_OVERIG = 'BSchBepBtwOlo';  // 1205010.04
    public const BTW_1D_PRIVE = 'BSchBepBtwOop';   // 1205010.05
    public const BTW_2A_VERLEGD = 'BSchBepBtwOlw'; // 1205010.06
    public const BTW_4A_BUITEN_EU = 'BSchBepBtwOlb'; // 1205010.07
    public const BTW_4B_BINNEN_EU = 'BSchBepBtwOlu'; // 1205010.08
    public const BTW_5B_VOORBELASTING = 'BSchBepBtwVoo'; // 1205010.09
    public const BTW_AFGEDRAGEN = 'BSchBepBtwAfo'; // 1205010.13
    public const BTW_BEGINBALANS = 'BSchBepBtwBeg'; // 1205010.01

    /*
     * Omzet. RGS splitst naar goederen en diensten, en binnen elk naar de
     * rubriek van de btw-aangifte. Een factuurregel weet zijn btw-tarief en of
     * er verlegd of geleverd binnen de EU is; daarmee is de rekening bepaald.
     */
    public const OMZET_DIENST_HOOG = 'WOmzNodOdh';   // 8003010  1a
    public const OMZET_DIENST_LAAG = 'WOmzNodOdl';   // 8003020  1b
    public const OMZET_DIENST_NUL = 'WOmzNodOdg';    // 8003050  1e
    public const OMZET_DIENST_VERLEGD = 'WOmzNodOdv'; // 8003060  2a
    public const OMZET_DIENST_EU = 'WOmzNodOdi';     // 8003080  3b
    public const OMZET_GOED_HOOG = 'WOmzNohOlh';     // 8002010  1a
    public const OMZET_GOED_LAAG = 'WOmzNohOlv';     // 8002020  1b
    public const OMZET_GOED_EU = 'WOmzNohOli';       // 8002090  3b
    public const OMZET_OVERIG = 'WOvbOvoOvo';        // 8213010

    public const INKOOP_HANDELSGOEDEREN = 'WKprInhInh'; // 7005010
    public const INKOOP_UITBESTEED = 'WKprKuwKuw';      // 7003010
    public const KOSTEN_ALGEMEEN = 'WBedAlkOal';        // 4215010
    public const AFBOEKING_DEBITEUREN = 'WBedVkkAdd';   // 4203170
    public const BETAALVERSCHIL = 'WBedAdlBet';         // 4210070
    public const KOR = 'WBedAdlBtk';                    // 4210110

    public const KAPITAAL = 'BEivKapOnd';               // 0509010
    public const KAPITAAL_BEGINBALANS = 'BEivKapOndBeg'; // 0509010.01
    public const PRIVE_STORTING = 'BEivKapPrs';         // 0509030
    public const PRIVE_OPNAME = 'BEivKapPro';           // 0509040

    public const NOG_TE_VERDELEN = 'BSchOpaOop';        // 1210050
    public const OVERIGE_SCHULDEN = 'BSchOvsOvs';       // 1209150
    public const OVERIGE_VORDERINGEN = 'BVorOvrOvk';    // 1103190
    public const DUBIEUZE_DEBITEUREN = 'BVorDebVdd';    // 1101030

    /**
     * Rekeningen die niet in het RGS-startschema zitten maar die het pakket
     * wél nodig heeft, en waarom.
     *
     * Het startschema van RGS is bewust klein. Drie rekeningen ontbreken erin
     * terwijl er automatisch op geboekt wordt; zonder deze zou de eerste
     * ongeplaatste betaling of de eerste afboeking nergens heen kunnen.
     */
    public const EXTRA_IN_STARTSCHEMA = [
        // Een ontvangst die nog niet aan een factuur hangt. Dit is met opzet
        // een schuld en geen opbrengst: de omzet is al genomen toen de factuur
        // werd gemaakt. Zet je het ook als opbrengst weg, dan telt dezelfde
        // euro twee keer mee en blijft de vordering openstaan alsof er niet is
        // betaald.
        self::NOG_TE_VERDELEN,
        // Nog te betalen kosten: nodig zodra iemand een beginbalans invoert.
        'BSchOpaNtb',
        // De voorziening voor debiteuren die vermoedelijk niet meer betalen.
        self::DUBIEUZE_DEBITEUREN,
    ];

    /**
     * De omschrijving van RGS is de officiële, maar er staan een paar
     * tikfouten in de bron. Die corrigeren we alleen in wat de gebruiker
     * ziet — de code en het nummer blijven onaangeroerd, want daarop rust de
     * afspraak met de accountant.
     */
    public const NAAM_CORRECTIES = [
        'BLasSakLvl' => 'Hoofdsom leningen',                         // bron: "Hoodsom"
        'BSchBepBtwOlb' => '4a. Omzetbelasting leveringen/diensten uit landen buiten de EU', // bron: "leveringe"
        'BSchOpaOop' => 'Overige overlopende passiva',                // bron zegt "activa", staat onder de schulden
        'WOmzNopOlv' => '1b. Netto-omzet uit leveringen geproduceerde goederen belast met laag tarief', // bron: "laagd"
    ];

    /** @var array<int, array{0:string,1:string,2:string,3:string,4:int,5:bool}>|null */
    private static ?array $regels = null;

    /** @var array<string, array{code:string,nummer:string,naam:string,dc:string,nivo:int,basis:bool}>|null */
    private static ?array $opCode = null;

    /**
     * Alle regels, in de volgorde van het schema.
     *
     * @return array<int, array{code:string,nummer:string,naam:string,dc:string,nivo:int,basis:bool}>
     */
    public static function alles(): array
    {
        if (self::$regels === null) {
            self::laad();
        }

        return self::$regels;
    }

    /**
     * Alleen het startschema: wat een nieuwe administratie meekrijgt.
     *
     * De rubrieken erboven gaan altijd mee, ook als RGS ze zelf niet in de
     * basis heeft gezet. Zonder de rubriek "overlopende passiva" is er geen
     * plek op de balans voor de rekening eronder, en dan staat er een post in
     * het niets.
     */
    public static function startschema(): array
    {
        $gekozen = [];
        foreach (self::alles() as $r) {
            if (! $r['basis'] && ! in_array($r['code'], self::EXTRA_IN_STARTSCHEMA, true)) {
                continue;
            }
            $gekozen[$r['code']] = true;
            foreach (self::voorouders($r['code']) as $v) {
                if (strlen($v) >= 4) {
                    $gekozen[$v] = true;
                }
            }
        }

        return array_values(array_filter(
            self::alles(),
            fn (array $r) => isset($gekozen[$r['code']])
        ));
    }

    /** Eén regel op RGS-code, of null. */
    public static function vind(string $code): ?array
    {
        if (self::$opCode === null) {
            self::laad();
        }

        return self::$opCode[$code] ?? null;
    }

    /**
     * De voorouders van een RGS-code, van hoog naar laag.
     *
     * Een code is opgebouwd uit blokken: één letter voor balans (B) of
     * winst-en-verlies (W), daarna per niveau drie letters. BVorDebHad is dus
     * B › BVor › BVorDeb › BVorDebHad. Daarmee is de boom af te leiden uit de
     * code zelf en hoeft er geen aparte kolom bij.
     *
     * @return array<int, string>
     */
    public static function voorouders(string $code): array
    {
        $uit = [];
        for ($len = 1; $len < strlen($code); $len = $len === 1 ? 4 : $len + 3) {
            $uit[] = substr($code, 0, $len);
        }

        return $uit;
    }

    /** De rechtstreekse ouder van een code, of null bij een hoofdrubriek. */
    public static function ouder(string $code): ?string
    {
        $pad = self::voorouders($code);
        $laatste = end($pad);

        // "B" en "W" zijn de tweedeling balans / resultaat, geen rekening.
        return ($laatste === false || strlen($laatste) < 4) ? null : $laatste;
    }

    /** balans of resultaat, uit de eerste letter van de code. */
    public static function staat(string $code): string
    {
        return str_starts_with($code, 'W') ? 'resultaat' : 'balans';
    }

    /**
     * De naam zoals de gebruiker hem te zien krijgt: die van RGS, met de
     * tikfouten uit de bron eruit.
     */
    public static function naam(string $code, string $bron): string
    {
        return self::NAAM_CORRECTIES[$code] ?? $bron;
    }

    private static function laad(): void
    {
        /** @var array<int, array{0:string,1:string,2:string,3:string,4:int,5:bool}> $ruw */
        $ruw = require resource_path('data/rgs33.php');

        self::$regels = [];
        self::$opCode = [];

        foreach ($ruw as [$code, $nummer, $naam, $dc, $nivo, $basis]) {
            $regel = [
                'code' => $code,
                'nummer' => $nummer,
                'naam' => self::naam($code, $naam),
                'dc' => $dc ?: 'D',
                'nivo' => $nivo,
                'basis' => $basis,
            ];
            self::$regels[] = $regel;
            self::$opCode[$code] = $regel;
        }
    }
}
