<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/** The business and contact details the Terms, Privacy and Support pages quote. */
class LegalData extends Data
{
    public function __construct(
        public string $tradingName,
        public ?string $entityName,
        public ?string $entityType,
        public ?string $companyNumber,
        public ?string $address,
        public ?string $icoRegistration,
        public ?string $supportEmail,
        public ?string $privacyEmail,
        public ?string $hostingProvider,
        public ?string $mailProvider,
        public int $backupRetentionDays,
        public string $supportResponse,
        public string $termsVersion,
        public string $termsUpdatedLabel,
        public string $privacyVersion,
        public string $privacyUpdatedLabel,
        public string $cookiesVersion,
        public string $cookiesUpdatedLabel,
    ) {}

    public static function current(): self
    {
        return new self(
            tradingName: config('legal.trading_name'),
            entityName: config('legal.entity_name'),
            entityType: config('legal.entity_type'),
            companyNumber: config('legal.company_number'),
            address: config('legal.address'),
            icoRegistration: config('legal.ico_registration'),
            supportEmail: config('legal.support_email'),
            privacyEmail: config('legal.privacy_email'),
            hostingProvider: config('legal.hosting_provider'),
            mailProvider: config('legal.mail_provider'),
            backupRetentionDays: config('legal.backup_retention_days'),
            supportResponse: config('legal.support_response'),
            termsVersion: config('legal.terms_version'),
            termsUpdatedLabel: self::label(config('legal.terms_version')),
            privacyVersion: config('legal.privacy_version'),
            privacyUpdatedLabel: self::label(config('legal.privacy_version')),
            cookiesVersion: config('legal.cookies_version'),
            cookiesUpdatedLabel: self::label(config('legal.cookies_version')),
        );
    }

    private static function label(string $version): string
    {
        return CarbonImmutable::parse($version)->format('j F Y');
    }
}
