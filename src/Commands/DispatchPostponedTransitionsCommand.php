<?php

namespace byteit\LaravelEnumStateMachines\Commands;

use byteit\LaravelEnumStateMachines\Jobs\PostponedTransitionExecutor;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * TODO: Finish command
 * TODO: Add progress
 * TODO: Add better logging
 */
class DispatchPostponedTransitionsCommand extends Command
{
    protected $signature = 'state-machine:dispatch-postponed';

    protected $description = 'Dispatches all postponed transitions that are on schedule';

    public function handle(): void
    {
        /** @var Collection<PostponedTransition> $transitions */
        $transitions = PostponedTransition::query()
            ->with(['model'])
            ->onScheduleOrOverdue()
            ->get();

        $count = count($transitions);
        $this->line("Found ${count} transitions");

        $transitions
            ->each(function (PostponedTransition $pendingTransition) {
                PostponedTransitionExecutor::dispatch($pendingTransition);
                $this->line('Dispatched');
            });
    }
}
