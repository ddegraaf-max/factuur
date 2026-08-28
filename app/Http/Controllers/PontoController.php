<?php

namespace App\Http\Controllers;

use App\Models\PontoAccount;
use App\Models\User;
use App\Services\Ponto\PontoException;
use App\Services\Ponto\PontoService;
use App\Services\Ponto\PontoSyncer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Bankkoppeling via Ponto: de eigenaar geeft bij zijn bank toestemming
 * (OAuth2), daarna halen we transacties automatisch op.
 */
class PontoController extends Controller
{
    public function connect(PontoService $ponto): RedirectResponse
    {
        if (! $ponto->available()) {
            return redirect()->route('bank.index')->with('error', 'De bankkoppeling is op dit moment niet beschikbaar.');
        }

        return redirect()->away($ponto->authorizationUrl(route('bank.ponto.callback')));
    }

    public function callback(Request $request, PontoService $ponto, PontoSyncer $syncer): RedirectResponse
    {
        if ($request->filled('error')) {
            $reason = $request->input('error_description') ?: $request->input('error');

            return redirect()->route('bank.index')->with('error', "Koppelen afgebroken: {$reason}");
        }

        /** @var User $user */
        $user = $request->user();
        try {
            $connection = $ponto->completeAuthorization($user->company, (string) $request->input('code'), (string) $request->input('state'));
        } catch (PontoException $e) {
            return redirect()->route('bank.index')->with('error', $e->getMessage());
        }

        $accounts = $connection->accounts()->count();
        try {
            $imported = $syncer->sync($connection, false);
        } catch (PontoException) {
            $imported = 0;
        }

        return redirect()->route('bank.index')
            ->with('flash', "Bank gekoppeld: {$accounts} rekening(en), {$imported} transactie(s) opgehaald.");
    }

    public function sync(Request $request, PontoSyncer $syncer): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $connection = $user->company->pontoConnection;
        abort_unless($connection, 404);

        try {
            $imported = $syncer->sync($connection);
        } catch (PontoException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('flash', $imported > 0 ? "{$imported} nieuwe transactie(s) opgehaald." : 'Bijgewerkt — geen nieuwe transacties.');
    }

    public function toggleAccount(Request $request, PontoAccount $account): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($account->company_id === $user->company_id, 404);

        $account->update(['sync_enabled' => ! $account->sync_enabled]);

        return back()->with('flash', $account->sync_enabled
            ? "Rekening {$account->label()} wordt weer gesynchroniseerd."
            : "Rekening {$account->label()} wordt overgeslagen.");
    }

    public function disconnect(Request $request, PontoService $ponto): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $connection = $user->company->pontoConnection;
        abort_unless($connection, 404);

        $ponto->disconnect($connection);

        return back()->with('flash', 'Bankkoppeling verbroken. Al opgehaalde transacties blijven staan.');
    }
}
