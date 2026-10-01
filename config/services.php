<?php

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'resend' => [
        'key' => env('RESEND_KEY'),
    ],
    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        // Basis-abonnement (€10/maand excl. btw) — bestaande variabele.
        'price_id' => env('STRIPE_PRICE_ID'),
        // Slim-abonnement (€17,50/maand excl. btw, met AI-functies). Zonder
        // deze variabele is alleen Basis af te sluiten.
        'price_id_slim' => env('STRIPE_PRICE_ID_SLIM'),
        // Toeslag bankkoppeling: price per gekoppelde rekening per maand (hoeveelheid = aantal rekeningen).
        'price_id_bank' => env('STRIPE_PRICE_ID_BANK'),
    ],
    'turnstile' => [
        'sitekey' => env('TURNSTILE_SITEKEY'),
        'secret' => env('TURNSTILE_SECRET'),
    ],
    // KvK API (developers.kvk.nl). Zonder key blijft de KvK-zoeker verborgen.
    // Testomgeving: KVK_API_BASE=https://api.kvk.nl/test met de publieke testkey.
    'kvk' => [
        'key' => env('KVK_API_KEY'),
        'base' => rtrim(env('KVK_API_BASE', 'https://api.kvk.nl'), '/'),
    ],
    // Peppol-verzending via Storecove (storecove.com). De bereikbaarheids-
    // check via de openbare Peppol Directory werkt altijd; daadwerkelijk
    // afleveren kan pas met een token + legal entity id.
    // Peppol via Recommand (recommand.eu): één teamkey voor EasyInvoice; elke
    // administratie wordt daaronder als eigen deelnemer geregistreerd.
    'peppol' => [
        'recommand_base' => env('RECOMMAND_API_BASE', 'https://app.recommand.eu/api/v1'),
        'recommand_key' => env('RECOMMAND_API_KEY'),
        'recommand_secret' => env('RECOMMAND_API_SECRET'),
        // Geheim waarmee Recommand webhooks ondertekent (X-Signature).
        'recommand_webhook_secret' => env('RECOMMAND_WEBHOOK_SECRET'),
    ],
    // Dagelijkse database-back-up (backup:run) naar S3-compatibele opslag
    // (Cloudflare R2, Backblaze B2, Hetzner, Scaleway …). Zonder bucket blijft
    // de taak uit en meldt /health geen back-upstatus.
    'backup' => [
        'endpoint' => env('BACKUP_S3_ENDPOINT'),          // bijv. https://<account>.r2.cloudflarestorage.com
        'region' => env('BACKUP_S3_REGION', 'auto'),
        'bucket' => env('BACKUP_S3_BUCKET'),
        'key' => env('BACKUP_S3_KEY'),
        'secret' => env('BACKUP_S3_SECRET'),
        'prefix' => env('BACKUP_S3_PREFIX', 'easyinvoice'),
        'keep_days' => (int) env('BACKUP_KEEP_DAYS', 30),
        'dump_command' => env('BACKUP_DUMP_COMMAND'),     // alleen voor tests/afwijkende omgevingen
    ],
    // Inkoopfacturen per e-mail aanleveren: een inbound-maildomein (bijv.
    // Postmark inbound) POST binnenkomende mail naar onze webhook. Zonder
    // beide variabelen blijft het Postvak IN in de "nog activeren"-stand.
    'inbound' => [
        'secret' => env('INBOUND_MAIL_SECRET'),   // geheim deel van de webhook-URL
        'domain' => env('INBOUND_MAIL_DOMAIN'),   // bijv. inbox.easyinvoice.nl
    ],
    // Bonnetjes automatisch herkennen via Claude (Anthropic). Zonder key
    // blijft de scanknop op het inkoopformulier verborgen.
    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-5'),
        // Fair use: maximaal aantal AI-acties (scans + offerteherkenningen)
        // per administratie per maand. 0 = geen limiet. Vrijgestelde accounts
        // hebben nooit een limiet.
        'monthly_limit' => (int) env('AI_MONTHLY_LIMIT', 250),
    ],
    // Wie mag het interne marketingdashboard (/marketing-inzichten) zien?
    // Komma-gescheiden e-mailadressen; leeg = alleen gebruiker met id 1
    // (de eerste registratie, oftewel de eigenaar).
    'marketing_stats' => [
        'emails' => env('MARKETING_STATS_EMAILS', ''),
    ],
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    // Bankkoppeling via Ponto Connect (Ibanity). Certificaat en sleutels als PEM
    // (letterlijk, met regelovergangen, of base64). Zonder client-id/certificaat blijft de
    // koppeling onzichtbaar. PONTO_SANDBOX=true gebruikt de Ponto-testomgeving.
    'ponto' => [
        'client_id' => env('PONTO_CLIENT_ID'),
        'client_secret' => env('PONTO_CLIENT_SECRET'),
        'certificate' => env('PONTO_CERTIFICATE'),
        'private_key' => env('PONTO_PRIVATE_KEY'),
        'key_passphrase' => env('PONTO_KEY_PASSPHRASE'),
        'signature_certificate_id' => env('PONTO_SIGNATURE_CERTIFICATE_ID'),
        'signature_private_key' => env('PONTO_SIGNATURE_PRIVATE_KEY'),
        'signature_key_passphrase' => env('PONTO_SIGNATURE_KEY_PASSPHRASE'),
        'sandbox' => (bool) env('PONTO_SANDBOX', false),
        'api_base' => env('PONTO_API_BASE', 'https://api.ibanity.com/ponto-connect'),
        // Weergaveprijs per rekening per maand (excl. btw); het echte bedrag zit in de Stripe-price.
        'account_price' => (float) env('PONTO_ACCOUNT_PRICE', 5),
    ],
    // Centraal Insolventieregister (Rechtspraak): gratis webservice na aanmelden op
    // insolventies.rechtspraak.nl. Zonder gebruikersnaam en wachtwoord slaat de klantscore deze bron over.
    'cir' => [
        'username' => env('CIR_USERNAME'),
        'password' => env('CIR_PASSWORD'),
        'url' => env('CIR_URL', 'https://webservice.rechtspraak.nl/cir.asmx'),
    ],
    /*
     * Centraal Curatele- en Bewindregister (Rechtspraak). Gratis webservice na
     * aanmelden via het abonnementenportaal; zonder gebruikersnaam en wachtwoord
     * bestaat de controle niet.
     *
     * ── Twee dingen die hier niet vanzelf gaan ────────────────────────────
     *
     * 1. De dataservice draait op een PKIoverheid-certificaat waarvan de stam
     *    ("Staat der Nederlanden Private Root CA - G1") in géén enkele
     *    truststore zit. Vandaar `cacert`: die stam staat in de repo, want een
     *    certificaat is openbaar. Verificatie uitzetten is hier geen optie —
     *    er gaan persoonsgegevens over deze verbinding.
     * 2. Het token komt van een aparte secure token service (ADFS, WS-Trust
     *    1.3) en is een uur geldig. `realm` is de relying party waarvoor het
     *    token wordt uitgegeven en moet exact overeenkomen met wat de
     *    Rechtspraak heeft geconfigureerd.
     *
     * Wat je met de uitkomsten mág doen staat in de gebruiksvoorwaarden:
     * alleen handelspartijen informeren over de curatele of het bewind. Een
     * ander doel — bijvoorbeeld een risicoscore — is volgens artikel 2
     * onrechtmatig. Daarom voedt dit register bewust níet de klantscore, waar
     * het insolventieregister ('cir') hierboven wél in meegaat.
     */
    'ccbr' => [
        'username' => env('CCBR_USERNAME'),
        'password' => env('CCBR_PASSWORD'),
        'sts' => env('CCBR_STS', 'https://sts.rechtspraak.nl/adfs/services/trust/13/usernamemixed'),
        'url' => env('CCBR_URL', 'https://ccbrservice.rechtspraak.nl/ccbrdataservice.svc'),
        'realm' => env('CCBR_REALM', 'https://ccbrservice.rechtspraak.nl/'),
        'cacert' => env('CCBR_CACERT', resource_path('certs/pkioverheid-private-root-g1.pem')),
    ],
    // Sms via Smstools (api.smsgatewayapi.com). Zonder beide sleutels bestaat de functie niet.
    // De korte namen (client_id, client_secret) lezen we ook, voor wie ze zo in Railway heeft gezet.
    // SMSTOOLS_COMPANIES: ids van administraties die mogen sms'en, of * voor iedereen;
    // leeg = alleen de administraties van de eigenaar van het platform.
    'smstools' => [
        'client_id' => env('SMSTOOLS_CLIENT_ID', env('CLIENT_ID', env('client_id'))),
        'client_secret' => env('SMSTOOLS_CLIENT_SECRET', env('CLIENT_SECRET', env('client_secret'))),
        'sender' => env('SMSTOOLS_SENDER'),
        'url' => env('SMSTOOLS_URL', 'https://api.smsgatewayapi.com/v1'),
        'companies' => env('SMSTOOLS_COMPANIES', ''),
        'monthly_limit' => (int) env('SMSTOOLS_MONTHLY_LIMIT', 300),
        // Secret van de delivery_report-webhook in Smstools; zonder secret geldt alleen een bekend messageid als controle.
        'webhook_secret' => env('SMSTOOLS_WEBHOOK_SECRET'),
    ],
];
