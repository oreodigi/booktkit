<?php
namespace App\Models\Payments;

use Illuminate\Database\Eloquent\Model;

class OrganizerPaymentProfile extends Model
{
    protected $guarded = [];

    protected $hidden = ['pan', 'stakeholder_pan', 'gstin'];

    protected $casts = [
        'gst_verified' => 'boolean',
        'split_enabled' => 'boolean',
        'metadata' => 'array',
        'split_suspended' => 'boolean',
        'route_terms_accepted_at' => 'datetime',
        'kyc_remarks' => 'array',
        'pan' => 'encrypted',
        'stakeholder_pan' => 'encrypted',
        'gstin' => 'encrypted',
        'submitted_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function organizer()
    {
        return $this->belongsTo(\App\Models\Organizer::class);
    }

    public function canSplit(): bool
    {
        return $this->split_enabled
            && in_array($this->razorpay_status, ['active','activated'], true)
            && $this->kyc_status === 'activated'
            && !$this->split_suspended
            && !empty($this->razorpay_account_id)
            && !empty($this->razorpay_product_id);
    }

    public function maskedPan(): ?string
    {
        return $this->pan ? substr($this->pan, 0, 2).'*****'.substr($this->pan, -3) : null;
    }

    public function maskedStakeholderPan(): ?string
    {
        return $this->stakeholder_pan ? substr($this->stakeholder_pan, 0, 2).'*****'.substr($this->stakeholder_pan, -3) : null;
    }

    public function maskedGstin(): ?string
    {
        return $this->gstin ? substr($this->gstin, 0, 4).'*******'.substr($this->gstin, -4) : null;
    }

    public function maskedBankAccount(): ?string
    {
        return $this->bank_account_last4 ? '••••'.$this->bank_account_last4 : null;
    }
}