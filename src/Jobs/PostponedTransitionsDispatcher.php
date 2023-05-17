<?php

namespace byteit\LaravelEnumStateMachines\Jobs;

use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

// TODO: Move to command
class PostponedTransitionsDispatcher implements ShouldQueue
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public function handle(): void
    {
        PostponedTransition::with(['model'])
            ->notApplied()
            ->onScheduleOrOverdue()
            ->get()
            ->each(function (PostponedTransition $pendingTransition) {
                PostponedTransitionExecutor::dispatch($pendingTransition);
            });
    }
}
