<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $fillable = [
        'receipt_last_YY_no',
        'receipt_YY',
        'receipt_no'
    ];

    /**
     * Example output: 25000001
     */
    public function formatReceiptNumber(): string
    {
        return $this->receipt_YY . str_pad($this->receipt_last_YY_no, 6, '0', STR_PAD_LEFT);
    }
}