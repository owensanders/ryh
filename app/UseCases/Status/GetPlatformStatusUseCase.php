<?php

declare(strict_types=1);

namespace App\UseCases\Status;

use App\Data\PlatformStatusData;
use App\Data\ServiceStatusData;
use App\Enums\ServiceStateEnum;
use App\Status\PlatformProbesService;
use Illuminate\Support\Facades\Cache;

class GetPlatformStatusUseCase
{
    public const string CACHE_KEY = 'status:platform';

    /** Results are shared by every visitor for this long, so the page can't be used to hammer the database. */
    public const int CACHE_SECONDS = 60;

    public function __construct(private readonly PlatformProbesService $probes) {}

    public function execute(): PlatformStatusData
    {
        /** @var array{checkedAt: string, states: array<string, string>} $result */
        $result = Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn (): array => [
            'checkedAt' => now()->toIso8601String(),
            'states' => [
                'app' => ServiceStateEnum::Operational->value,
                'database' => $this->probes->database()->value,
                'cache' => $this->probes->cache()->value,
                'queue' => $this->probes->queue()->value,
                'realtime' => $this->probes->realtime()->value,
                'ai' => $this->probes->ai()->value,
                'documents' => $this->probes->documents()->value,
            ],
        ]);

        $services = [];

        foreach (self::descriptions() as $key => [$name, $description]) {
            $services[] = ServiceStatusData::of($name, $description, ServiceStateEnum::from($result['states'][$key]));
        }

        $overall = collect($services)
            ->map(fn (ServiceStatusData $service) => $service->state)
            ->sortByDesc(fn (ServiceStateEnum $state) => $state->severity())
            ->firstOrFail();

        return new PlatformStatusData($overall, $services, $result['checkedAt']);
    }

    /** @return array<string, array{string, string}> */
    private static function descriptions(): array
    {
        return [
            'app' => ['Website & app', 'Signing in and using RYH'],
            'database' => ['Household data', 'Chores, meals, lists, calendar and budget'],
            'cache' => ['Sessions', 'Staying signed in'],
            'queue' => ['Emails & background jobs', 'Invites, verification emails and reminders'],
            'realtime' => ['Live notifications', 'Real-time updates in the notification bell'],
            'ai' => ['AI meal plans', 'Generating a week of dinners'],
            'documents' => ['Document vault', 'Uploading and opening household files'],
        ];
    }
}
