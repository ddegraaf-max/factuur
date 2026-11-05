<?php

namespace App\Console\Commands;

use App\Services\Fachora\GeocodeService;
use App\Services\Fachora\GooglePlacesClient;
use App\Support\Brand;
use App\Support\Fachora;
use Illuminate\Console\Command;

/**
 * Fachora 1.103: bedrijven met goede Google-beoordelingen ophalen voor een categorie en plaats
 * (of met --all voor alle advertentiesteden × categorieën). Op aanvraag rond een klant gebeurt
 * dit vanzelf (fachora:local-search); dit commando is voor de eigenaar die vooraf wil vullen.
 */
class FachoraGoogleImport extends Command
{
    protected $signature = 'fachora:google-import {category? : categoriesleutel, bijv. hydraulik} {city? : plaatsnaam} {--all : alle advertentiesteden (fachora.ads_cities) × alle categorieën} {--dry-run : alleen tellen}';

    protected $description = 'Fachora: bedrijven met goede Google-beoordelingen ophalen (Places API)';

    public function handle(GooglePlacesClient $google, GeocodeService $geo): int
    {
        if (! Brand::is('fachora')) {
            $this->line('Niet het merk fachora; niets te doen.');

            return self::SUCCESS;
        }
        if (! $google->configured()) {
            $this->error('Geen FACHORA_GOOGLE_PLACES_KEY ingesteld.');

            return self::FAILURE;
        }

        $pairs = [];
        if ($this->option('all')) {
            $cities = (array) config('fachora.ads_cities', []);
            foreach ($cities as $city) {
                foreach (Fachora::categories() as $category) {
                    $pairs[] = [$category, $city];
                }
            }
        } else {
            $category = (string) $this->argument('category');
            $city = (string) $this->argument('city');
            if (! Fachora::hasCategory($category) || $city === '') {
                $this->error('Geef een categorie (bijv. hydraulik) en een plaats, of --all.');

                return self::FAILURE;
            }
            $pairs[] = [$category, $city];
        }

        if ($this->option('dry-run')) {
            $this->line(count($pairs) . ' zoekopdracht(en) × maximaal ' . (int) config('fachora.google_pages', 3) . ' pagina\'s; niets opgehaald.');

            return self::SUCCESS;
        }

        $totals = ['created' => 0, 'updated' => 0, 'found' => 0];
        foreach ($pairs as [$category, $city]) {
            $center = $geo->lookup(null, $city);
            $result = $google->importArea($category, $city, $center ? [(float) $center['lat'], (float) $center['lng']] : null);
            $this->line(sprintf('%-14s %-22s gevonden %3d, nieuw %3d, bijgewerkt %3d', $category, $city, $result['found'], $result['created'], $result['updated']));
            foreach ($totals as $k => $v) {
                $totals[$k] += $result[$k];
            }
        }
        $this->info("Klaar: {$totals['found']} gevonden, {$totals['created']} nieuw, {$totals['updated']} bijgewerkt.");

        return self::SUCCESS;
    }
}
