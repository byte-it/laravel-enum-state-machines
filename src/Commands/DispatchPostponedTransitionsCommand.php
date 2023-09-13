<?php

namespace byteit\LaravelEnumStateMachines\Commands;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Jobs\PostponedTransitionExecutor;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

use function sprintf;

class DispatchPostponedTransitionsCommand extends Command
{
    protected $signature = 'state-machine:dispatch-postponed';

    protected $description = 'Dispatches all postponed transitions that are on schedule';

    public function handle(): void
    {
        /** @var Collection<int, PostponedTransition<States>> $transitions */
        $transitions = PostponedTransition::query()
            ->onlyDue()
            ->with(['model'])
            ->get();

        $transitions
            ->each(function (PostponedTransition $transition, $_) {
                $model = $transition->model;

                if ($model === null) {
                    return;
                }
                /** @var string|int $id */
                $id = $model->getKey();
                $description = sprintf(
                    '<fg=gray>%s</> Dispatching [%s:%s] %s: %s -> %s',
                    Carbon::now()->format('Y-m-d H:i:s'),
                    $model::class,
                    $id,
                    $transition->field,
                    $transition->start->value,
                    $transition->target->value
                );

                $this->line($description);
                PostponedTransitionExecutor::dispatch($transition);
            });
    }
}
