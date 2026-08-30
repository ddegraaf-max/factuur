<?php

namespace App\Http\Controllers;

use App\Services\MailDomainService;
use App\Support\Audit;
use App\Support\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Instellingen → Koppelingen → Eigen afzenderadres (mail vanaf eigen domein). */
class MailDomainController extends Controller
{
    public function __construct(protected MailDomainService $domains) {}

    public function connect(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'domain' => ['required', 'string', 'max:190', 'regex:/^(?!-)[a-z0-9-]+(\.[a-z0-9-]+)+$/i'],
            'local_part' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9._+-]+$/i'],
        ], [
            'domain.regex' => __('Vul een domeinnaam in zoals "jouwbedrijf.nl" (zonder https:// of www).'),
            'local_part.regex' => __('Het deel voor de @ mag alleen letters, cijfers, punten, streepjes en + bevatten.'),
        ]);

        $domain = mb_strtolower($data['domain']);
        if (in_array($domain, ['gmail.com', 'hotmail.com', 'outlook.com', 'live.nl', 'ziggo.nl', 'kpnmail.nl', 'icloud.com', 'yahoo.com', 'wp.pl', 'o2.pl', 'onet.pl', 'interia.pl', Brand::domain()], true)) {
            return back()->with('error', __('Dit werkt alleen met een eigen domeinnaam — niet met een gratis maildienst.'));
        }

        try {
            $this->domains->connect($request->user()->company, $domain, $data['local_part']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Eigen maildomein aanmelden mislukt', ['error' => $e->getMessage()]);

            return back()->with('error', __('Aanmelden is niet gelukt. Probeer het later opnieuw.'));
        }

        Audit::log('updated', $request->user()->company, __('Eigen afzenderdomein :domain aangemeld', ['domain' => $domain]));

        return back()->with('flash', __('Domein aangemeld. Zet nu de DNS-records bij je domeinbeheerder en klik daarna op "Controleer DNS".'));
    }

    public function refresh(Request $request): RedirectResponse
    {
        try {
            $status = $this->domains->refresh($request->user()->company);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('flash', match ($status) {
            'verified' => __('Geverifieerd! Je facturen en offertes gaan vanaf nu uit je eigen domein.'),
            'failed' => __('De DNS-records zijn nog niet (goed) gevonden. Controleer ze bij je domeinbeheerder; wijzigingen kunnen tot een uur duren.'),
            default => __('Nog niet geverifieerd — DNS-wijzigingen kunnen tot een uur duren. Probeer het straks opnieuw.'),
        });
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        $domain = $company->mail_domain;
        $this->domains->disconnect($company);
        Audit::log('updated', $company, __('Eigen afzenderdomein :domain losgekoppeld', ['domain' => $domain]));

        return back()->with('flash', __('Losgekoppeld. Mail gaat weer uit via :domain, met jouw bedrijfsnaam als afzender.', ['domain' => Brand::domain()]));
    }
}
