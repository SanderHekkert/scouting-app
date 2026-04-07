<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToSection
{
    private static function currentSection(): string
    {
        return app()->bound('currentSection')
            ? app('currentSection')
            : 'dolfijnen';
    }

    protected static function bootBelongsToSection(): void
    {
        static::creating(function ($model): void {
            if (empty($model->section)) {
                $model->section = self::currentSection();
            }
        });

        static::addGlobalScope('section', function (Builder $builder): void {
            $builder->where(
                $builder->getModel()->getTable().'.section',
                self::currentSection()
            );
        });
    }
}
