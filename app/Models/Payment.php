<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'patient_id', 'treatment_contract_id', 'amount', 'payment_method', 'receipt_type', 'status', 'notes',
    'billing_name', 'billing_document', 'sunat_serie', 'sunat_correlativo',
    'sunat_status', 'sunat_hash', 'sunat_xml_path', 'sunat_cdr_path', 'sunat_message',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, SoftDeletes;

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function treatmentContract(): BelongsTo
    {
        return $this->belongsTo(TreatmentContract::class);
    }
}
