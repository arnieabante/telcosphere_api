<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ApiController extends Controller
{
    use AuthorizesRequests;

    protected $policyClass;
    
    public function include(string $relationship) : bool {
        $include = request()->query('include');

        if (!$include) {
            return false;
        }

        $includes = explode(',', strtolower($include));

        return in_array(strtolower($relationship), $includes);
    }

    public function isAble(string $ability, $model) {
        return $this->authorize($ability, [$model, $this->policyClass]);
    }
}
