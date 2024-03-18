<?php

namespace byteit\LaravelEnumStateMachines\Scopes;

use byteit\LaravelEnumStateMachines\Contracts\States;
use byteit\LaravelEnumStateMachines\Models\PostponedTransition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Carbon;

class AppliedScope implements Scope
{
    /**
     * @param  Builder<PostponedTransition<States>>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNull('applied_at');
    }

    /**
     * @param  Builder<PostponedTransition<States>>  $builder
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withApplied', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('onlyApplied', function (Builder $builder) {
            $builder
                ->withoutGlobalScope($this)
                ->whereNotNull('applied_at');

            return $builder;
        });

        $builder->macro('onlyDue', function (Builder $builder, ?Carbon $now = null) {
            $now = $now ?? now();

            $builder->where('transition_at', '<=', $now);

            return $builder;
        });
    }
}
