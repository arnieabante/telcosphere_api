<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory, HasUuids;

    // default values
    protected $attributes = [
       'site_id' => 1,
       'is_active' => 1,
       'user_id' => 99, // TODO
       'created_by' => 99, // TODO
       'updated_by' => 99 // TODO
    ];

    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];

    public function getRouteKeyName(): string {
        // use uuid instead of id in model binding
        return 'uuid';
    }

    public function uniqueIds(): array {
        return ['uuid'];
    }

    public function user() : BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function modules() : BelongsToMany {
        return $this->belongsToMany(Module::class, 'permissions')
            ->using(Permission::class);
    }
}
