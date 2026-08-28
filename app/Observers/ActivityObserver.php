<?php

namespace App\Observers;

use App\Support\Audit;
use Illuminate\Database\Eloquent\Model;

/**
 * Schrijft aanmaken/wijzigen/verwijderen van de belangrijkste modellen naar het
 * logboek. Geregistreerd in AppServiceProvider; expliciete acties (verstuurd,
 * herinnerd, geaccepteerd, ingelogd) worden op de plek zelf gelogd via Audit::log.
 */
class ActivityObserver
{
    public function created(Model $model): void
    {
        if ($this->skip($model)) {
            return;
        }
        Audit::log('created', $model, Audit::label($model) . ' aangemaakt');
    }

    public function updated(Model $model): void
    {
        if ($this->skip($model)) {
            return;
        }
        $changes = Audit::changes($model);
        if (! $changes) {
            return; // alleen ruis (timestamps, tokens) gewijzigd
        }
        $fields = implode(', ', array_slice(array_keys($changes), 0, 6)) . (count($changes) > 6 ? ', …' : '');
        Audit::log('updated', $model, Audit::label($model) . ' gewijzigd (' . $fields . ')', $changes);
    }

    public function deleted(Model $model): void
    {
        if ($this->skip($model)) {
            return;
        }
        Audit::log('deleted', $model, Audit::label($model) . ' verwijderd');
    }

    /** Demo-omgevingen en de demo-opbouw loggen we niet — dat is geen gebruikersactiviteit. */
    private function skip(Model $model): bool
    {
        if (app()->runningUnitTests() && config('app.disable_activity_log')) {
            return true;
        }
        $company = $model instanceof \App\Models\Company ? $model : $model->getRelationValue('company');

        return (bool) ($company?->is_demo);
    }
}
