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
        return 'INV-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }
}
