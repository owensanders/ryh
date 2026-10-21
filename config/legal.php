<?php

declare(strict_types=1);

// Who stands behind RYH, as named in the Terms and Privacy Policy and on
// the Support page. Anything left null renders as a visible "[to be confirmed]"
// marker on those pages, so a missing detail is obvious before launch rather
// than silently blank.

return [
    // The trading name customers see.
    'trading_name' => env('LEGAL_TRADING_NAME', 'RYH'),

    // The person or company that is the trader and the data controller, e.g.
    // "Owen Sanders" (sole trader) or "RYH Ltd" (limited company).
    'entity_name' => env('LEGAL_ENTITY_NAME'),

    // 'sole_trader' or 'limited_company'.
    'entity_type' => env('LEGAL_ENTITY_TYPE'),

    // Companies House number — limited companies only.
    'company_number' => env('LEGAL_COMPANY_NUMBER'),

    // Registered office (company) or business address (sole trader). Consumer
    // law requires a geographic address in the pre-contract information.
    'address' => env('LEGAL_ADDRESS'),

    // Data protection fee registration with the ICO, e.g. "ZB123456".
    'ico_registration' => env('LEGAL_ICO_REGISTRATION'),

    'support_email' => env('SUPPORT_EMAIL'),

    // Data protection requests. Falls back to the support address.
    'privacy_email' => env('PRIVACY_EMAIL', env('SUPPORT_EMAIL')),

    // Processors not otherwise configured in code — named in the Privacy Policy.
    'hosting_provider' => env('LEGAL_HOSTING_PROVIDER', 'Laravel Cloud (Laravel Holdings Inc.)'),
    'mail_provider' => env('LEGAL_MAIL_PROVIDER'),
    'error_tracker' => env('LEGAL_ERROR_TRACKER'),

    // How long deleted data can linger in backups before it is overwritten.
    'backup_retention_days' => (int) env('LEGAL_BACKUP_RETENTION_DAYS', 30),

    // What the Support page promises.
    'support_response' => env('SUPPORT_RESPONSE_TIME', 'within two working days'),

    // Bump these when the documents change materially. Registration records the
    // terms version a user accepted.
    'terms_version' => '2026-10-07',
    'privacy_version' => '2026-10-11',
    'cookies_version' => '2026-10-11',
];
