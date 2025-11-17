<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = ['transaction_date', 'status', 'payment_method', 'total_amount', 'payment_amount', 'change_amount', 'invoice_code'];

    // Relation to TransactionDetail
    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
