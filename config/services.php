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
    ],
];
