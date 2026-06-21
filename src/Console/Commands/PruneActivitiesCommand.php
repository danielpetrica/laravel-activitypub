<?php

namespace DanielPetrica\LaravelActivityPub\Console\Commands;

use DanielPetrica\LaravelActivityPub\Jobs\PruneOldActivities;
use Illuminate\Console\Command;

final class PruneActivitiesCommand extends Command
{
    protected $signature = 'activitypub:prune-activities {--days=30 : Delete activities older than this many days}';

    protected $description = 'Prune old delivered ActivityPub activities from the database.';

    public function handle(): int
    {
        $days = (int) $this->option(key: 'days');

        PruneOldActivities::dispatch(daysOld: $days);

        $this->info(string: 'Pruning activities older than '.$days.' days...');

        return self::SUCCESS;
    }
}
