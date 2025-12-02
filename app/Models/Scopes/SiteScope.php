<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SiteScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // Determine current site
        $siteId = auth()->check() 
                    ? auth()->user()->site_id 
                    : session('site_id') ?? request()->header('site_id');

        if ($siteId) {
            $builder->where($model->getTable() . '.site_id', $siteId);
        }
    }
}
