<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Event times were stored as the household's clock read them. They become
 * UTC moments, converted with each household's time zone. A time that
 * happened twice (the clocks going back) becomes the first one; a time that
 * never happened (the clocks going forward) is read on the winter clock,
 * as Household::momentAt() does for times typed from now on.
 */
return new class extends Migration {
    public function up(): void
    {
        $this->convert($this->toUtc(...));
    }

    public function down(): void
    {
        $this->convert(fn (string $time, string $timezone): CarbonImmutable => CarbonImmutable::parse($time, 'UTC')->setTimezone($timezone));
    }

    /** Household::momentAt(), copied so this migration stays as it ran if that changes. */
    private function toUtc(string $time, string $timezone): CarbonImmutable
    {
        $moment = CarbonImmutable::parse($time, $timezone);
        $first = $moment->subSeconds(max(0, $moment->subDay()->utcOffset() * 60 - $moment->utcOffset() * 60));

        return ($first->toDateTimeString() === $moment->toDateTimeString() ? $first : $moment)->utc();
    }

    /** @param  Closure(string, string): CarbonImmutable  $convert */
    private function convert(Closure $convert): void
    {
        DB::transaction(function () use ($convert): void {
            DB::table('households')->where('timezone', '!=', 'UTC')->orderBy('id')->select(['id', 'timezone'])
                ->each(function (object $household) use ($convert): void {
                    DB::table('calendar_events')->where('household_id', $household->id)->select(['id', 'starts_at', 'ends_at'])
                        ->chunkById(500, function ($events) use ($convert, $household): void {
                            foreach ($events as $event) {
                                DB::table('calendar_events')->where('id', $event->id)->update([
                                    'starts_at' => $convert($event->starts_at, $household->timezone)->toDateTimeString(),
                                    'ends_at' => $event->ends_at !== null ? $convert($event->ends_at, $household->timezone)->toDateTimeString() : null,
                                ]);
                            }
                        });
                });
        });
    }
};
