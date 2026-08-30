<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Registration Email Domains
    |--------------------------------------------------------------------------
    |
    | Self-registration is restricted to these email domains. Set to an empty
    | list to allow any domain. Comma-separated in the environment, e.g.
    | HESTIA_ALLOWED_EMAIL_DOMAINS="eight8.gr,example.com"
    |
    */

    'allowed_email_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('HESTIA_ALLOWED_EMAIL_DOMAINS', 'eight8.gr'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Deploy Webhook Header Token
    |--------------------------------------------------------------------------
    |
    | Shared secret expected in the X-Gitlab-Token header on deploy webhooks.
    | The per-site token in the URL is the primary secret; this is a second
    | factor shared by every site.
    |
    | ponytail: shared across all sites for back-compat with webhooks already
    | configured with the old hardcoded value. Move to the site's own
    | webhook_token if a single leaked header should not affect every site.
    |
    */

    'webhook_header_token' => env('HESTIA_WEBHOOK_HEADER_TOKEN', 'HESTIACP'),

];
