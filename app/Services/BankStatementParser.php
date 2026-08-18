<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Leest bankafschriften in de twee gangbare Nederlandse exportformaten:
 *
 *  - CAMT.053 (ISO 20022 XML) — alle grote banken (ING, Rabobank, ABN AMRO,
 *    bunq, Knab, Triodos, SNS) bieden dit aan als download.
 *  - MT940 (structured) — het oudere SWIFT-formaat, ook overal beschikbaar.
 *
 * Levert een uniforme lijst transacties op:
 *   ['booking_date', 'amount', 'currency', 'counterparty_name',
 *    'counterparty_iban', 'description', 'source']
 */
class BankStatementParser
{
    /** @return array<int, array<string, mixed>> */
    public function parse(string $contents): array
    {
        $contents = trim($contents);

        if ($contents === '') {
            throw new \DomainException('Het bestand is leeg.');
        }

        if (str_starts_with(ltrim($contents), '<')) {
            return $this->parseCamt053($contents);
        }

        if (str_contains($contents, ':61:')) {
            return $this->parseMt940($contents);
        }

        throw new \DomainException('Onbekend formaat. Ondersteund: CAMT.053 (XML) of MT940 (structured).');
    }

    /* ===================== CAMT.053 ===================== */

    /** @return array<int, array<string, mixed>> */
    protected function parseCamt053(string $xmlString): array
    {
        $previous = libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $loaded = $doc->loadXML($xmlString, LIBXML_NONET | LIBXML_NOBLANKS);
        $xmlError = $loaded ? null : (libxml_get_errors()[0]->message ?? null);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new \DomainException(
                'Het XML-bestand kon niet worden gelezen. Is dit een geldig CAMT.053-bestand?'
                . ($xmlError ? ' (' . trim($xmlError) . ')' : '')
            );
        }

        $entries = $doc->getElementsByTagNameNS('*', 'Ntry');
        if ($entries->length === 0) {
            throw new \DomainException('Geen transacties gevonden in dit CAMT.053-bestand.');
        }

        $first = fn (\DOMElement $scope, string $name) => $scope->getElementsByTagNameNS('*', $name)->item(0);

        $rows = [];
        foreach ($entries as $entry) {
            /** @var \DOMElement $entry */
            $amountNode = $first($entry, 'Amt');
            if (! $amountNode) {
                continue;
            }

            $amount = (float) str_replace(',', '.', $amountNode->textContent);
            $currency = $amountNode->getAttribute('Ccy') ?: 'EUR';

            $indicator = $first($entry, 'CdtDbtInd')?->textContent ?? 'CRDT';
            if (strtoupper(trim($indicator)) === 'DBIT') {
                $amount = -$amount;
            }

            // Boekingsdatum: BookgDt/Dt of BookgDt/DtTm, anders ValDt.
            $date = null;
            foreach (['BookgDt', 'ValDt'] as $dateTag) {
                $dateEl = $first($entry, $dateTag);
                if ($dateEl) {
                    $raw = trim($dateEl->textContent);
                    $date = Carbon::parse(substr($raw, 0, 10));
                    break;
                }
            }
            if (! $date) {
                continue;
            }

            // Omschrijving: alle Ustrd-regels + eventueel AddtlNtryInf.
            $descriptionParts = [];
            foreach ($entry->getElementsByTagNameNS('*', 'Ustrd') as $ustrd) {
                $descriptionParts[] = trim($ustrd->textContent);
            }
            if (empty($descriptionParts)) {
                $extra = $first($entry, 'AddtlNtryInf');
                if ($extra) {
                    $descriptionParts[] = trim($extra->textContent);
                }
            }

            // Tegenpartij: bij ontvangsten (CRDT) de Dbtr, bij afschrijvingen de Cdtr.
            $partyTag = $amount >= 0 ? 'Dbtr' : 'Cdtr';
            $accountTag = $amount >= 0 ? 'DbtrAcct' : 'CdtrAcct';

            $name = null;
            $party = $first($entry, $partyTag);
            if ($party) {
                $name = trim($first($party, 'Nm')?->textContent ?? '') ?: null;
            }

            $iban = null;
            $account = $first($entry, $accountTag);
            if ($account) {
                $iban = trim($account->getElementsByTagNameNS('*', 'IBAN')->item(0)?->textContent ?? '') ?: null;
            }

            $rows[] = [
                'booking_date' => $date->format('Y-m-d'),
                'amount' => round($amount, 2),
                'currency' => strtoupper($currency),
                'counterparty_name' => $name ? mb_substr($name, 0, 190) : null,
                'counterparty_iban' => $iban ? strtoupper(str_replace(' ', '', $iban)) : null,
                'description' => mb_substr(implode(' ', array_filter($descriptionParts)), 0, 1000) ?: null,
                'source' => 'camt053',
            ];
        }

        return $rows;
    }

    /* ===================== MT940 ===================== */

    /** @return array<int, array<string, mixed>> */
    protected function parseMt940(string $contents): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];

        $rows = [];
        $current = null;
        $inDescription = false;

        $flush = function () use (&$current, &$rows) {
            if ($current !== null) {
                // Eerst tegenpartij uit de RUWE tekst halen (daar staan de
                // /NAME/- en /IBAN/-markeringen nog in), daarna opschonen.
                [$current['counterparty_name'], $current['counterparty_iban']] =
                    $this->extractCounterparty($current['description']);
                $current['description'] = mb_substr($this->cleanMt940Description($current['description']), 0, 1000) ?: null;
                $rows[] = $current;
                $current = null;
            }
        };

        foreach ($lines as $line) {
            if (str_starts_with($line, ':61:')) {
                $flush();
                $inDescription = false;

                // :61:JJMMDD[MMDD]D/C[funds]bedrag,decNTYPE...
                if (! preg_match('/^:61:(\d{6})(?:\d{4})?(R?[DC])([A-Z]?)([\d,\.]+)/', $line, $m)) {
                    continue;
                }

                $date = Carbon::createFromFormat('ymd', $m[1])->startOfDay();
                $amount = (float) str_replace(',', '.', rtrim($m[4], ','));
                if (str_contains($m[2], 'D')) {
                    $amount = -$amount;
                }

                $current = [
                    'booking_date' => $date->format('Y-m-d'),
                    'amount' => round($amount, 2),
                    'currency' => 'EUR',
                    'counterparty_name' => null,
                    'counterparty_iban' => null,
                    'description' => '',
                    'source' => 'mt940',
                ];
            } elseif (str_starts_with($line, ':86:')) {
                if ($current !== null) {
                    $current['description'] .= substr($line, 4) . ' ';
                    $inDescription = true;
                }
            } elseif (str_starts_with($line, ':')) {
                $inDescription = false; // andere tag (:62:, :64:, volgende blok…)
            } elseif ($inDescription && $current !== null) {
                $current['description'] .= $line . ' ';
            }
        }
        $flush();

        if (empty($rows)) {
            throw new \DomainException('Geen transacties gevonden in dit MT940-bestand.');
        }

        return $rows;
    }

    /** Structured MT940-velden (/NAME/, /REMI/ enz.) omzetten naar leesbare tekst. */
    protected function cleanMt940Description(string $description): string
    {
        $description = trim($description);
        // Veldscheidingen zoals /TRTP/../IBAN/..: vervang door spaties met label eruit.
        $description = preg_replace('#/(TRTP|BENM|ORDP|CSID|MARF|EREF|PREF|REMI|PURP|ULTB|ULTD|ID|NRTX|SWOC|SWOD)/#', ' ', $description) ?? $description;
        $description = preg_replace('/\s+/', ' ', $description) ?? $description;

        return trim($description);
    }

    /** Probeer naam + IBAN van de tegenpartij uit de omschrijving te halen. */
    protected function extractCounterparty(string $description): array
    {
        $name = null;
        $iban = null;

        if (preg_match('#/NAME/([^/]+)#i', $description, $m)) {
            $name = trim($m[1]);
        } elseif (preg_match('/\bNaam:\s*([^\s].*?)(?:\s{2,}|Omschrijving:|IBAN:|$)/i', $description, $m)) {
            $name = trim($m[1]);
        }

        if (preg_match('/\b([A-Z]{2}\d{2}[A-Z]{4}\d{10})\b/', str_replace(' ', '', strtoupper($description)), $m)) {
            $iban = $m[1];
        }

        return [
            $name ? mb_substr($name, 0, 190) : null,
            $iban,
        ];
    }
}
