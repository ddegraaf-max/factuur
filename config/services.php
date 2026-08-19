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
        'price_id' => env('STRIPE_PRICE_ID'),
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
    'peppol' => [
        'storecove_token' => env('STORECOVE_API_TOKEN'),
        'legal_entity_id' => env('STORECOVE_LEGAL_ENTITY_ID'),
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
    ],
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
];
