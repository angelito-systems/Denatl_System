<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'patient_id',
    'treatment_name',
    'total_cost',
    'down_payment',
    'installments',
    'installment_amount',
    'start_date',
    'status',
    'document_id',
])]
class TreatmentContract extends Model
{
    use HasFactory;

    protected $casts = [
        'start_date' => 'date',
        'total_cost' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'installments' => 'integer',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Accessor para calcular el total pagado
    public function getTotalPaidAttribute()
    {
        return $this->payments()->where('status', 'Pagado')->sum('amount');
    }

    // Accessor para calcular el saldo pendiente
    public function getBalanceDueAttribute()
    {
        return max(0, $this->total_cost - $this->total_paid);
    }
}
