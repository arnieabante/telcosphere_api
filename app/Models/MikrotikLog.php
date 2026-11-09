<?php

namespace App\Models;

use App\Models\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Model;

class MikrotikLog extends Model
{
    public const RESULT_SUCCESS = 'success';
    public const RESULT_FAILED = 'failed';
    public const RESULT_DRY_RUN = 'dry_run';

    protected $table = 'mikrotik_logs';

    protected $fillable = [
        'site_id',
        'mikrotik_id',
        'client_id',
        'pppoe_username',
        'action',
        'result',
        'trigger',
        'message',
        'actor_id',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new SiteScope);
    }

    public function mikrotik()
    {
        return $this->belongsTo(Mikrotik::class, 'mikrotik_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
