<?php

namespace byteit\LaravelEnumStateMachines\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Carbon;

class AppliedScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNull('applied_at');
    }

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
