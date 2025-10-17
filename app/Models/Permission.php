<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Permission extends Pivot
{
    // Pivot table: Roles_Modules

    use HasFactory;

    protected $table = 'permissions';
    
    protected $fillable = [
        'role_id',
        'module_id'
    ];

    public $incrementing = true;
}
