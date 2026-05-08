<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $fillable = [
        'site_id',
        'receipt_number'
    ];

    public function formatReceiptNumber($prefix, $suffix)
    {
        return $prefix . date('y') . str_pad($suffix, 6, '0', STR_PAD_LEFT);
    }
}
