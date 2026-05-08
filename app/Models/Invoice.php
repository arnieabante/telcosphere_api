<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'site_id',
        'invoice_number'
    ];

    public function formatInvoiceNumber($prefix, $suffix)
    {
        return $prefix . date('y') . str_pad($suffix, 6, '0', STR_PAD_LEFT);
    }
}
