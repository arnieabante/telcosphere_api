<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number'
    ];

    public function formatInvoiceNumber()
    {
        $site = Site::select(['invoice_id_pattern'])
            ->where('id', auth()->user()->site_id)
            ->first();

        $prefix = strlen(trim($site['invoice_id_pattern'])) > 0 ?
            $site['invoice_id_pattern'] . '-' :
            '';

        return $prefix . date('y') . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }
}
