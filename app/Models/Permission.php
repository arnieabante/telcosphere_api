<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Permission extends Pivot
{
    // Pivot table: Roles_Modules

    use HasFactory, HasUuids;

    public $incrementing = true;
    
    // default values
    protected $attributes = [
       'site_id' => 1,
        'is_read' => 1,
        'is_write' => 0,
        'is_delete' => 0,
        'is_active' => 1,
       'created_by' => 99, // TODO
       'updated_by' => 99 // TODO
    ];

    protected $table = 'permissions';
    
    protected $fillable = [
        'role_id',
        'module_id',
        'is_read',
        'is_write',
        'is_delete',
        'is_active',
        'created_at', // assign manually
        'updated_at' // assign manually
    ];

    public function uniqueIds(): array {
        return ['uuid'];
    }
}
