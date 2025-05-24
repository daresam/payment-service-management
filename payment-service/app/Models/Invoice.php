<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'corporate_id',
        'vendor_id',
        'invoice_number',
        'quantity',
        'rate',
        'amount',
        'status',
        'issue_date',
        'due_date',
        'payment_terms',
        'description',
    ];

    protected $casts = [
        'issue_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
        'corporate_id' => 'integer',
        'vendor_id' => 'integer',
    ];

    public function calculateAmount(): float|int
    {
        return $this->quantity * $this->rate;
    }

    public function isOverdue(): bool
    {
        return $this->status === 'OPEN' && now()->gt($this->due_date);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

}
