<?php
namespace App\Http\Controllers\BackEnd\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Payments\OrganizerPaymentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrganizerPayoutKycController extends Controller
{
    private const TYPES = ['individual','proprietorship','partnership','private_limited','public_limited','llp','trust','society','ngo'];
    private const ENTITY_TYPES = ['partnership','private_limited','public_limited','llp','trust','society','ngo'];

    public function edit()
    {
        $profile = OrganizerPaymentProfile::firstOrCreate(['organizer_id' => Auth::guard('organizer')->id()]);
        return view('organizer.payments.kyc', compact('profile'));
    }

    public function save(Request $request)
    {
        $organizerId = Auth::guard('organizer')->id();
        $profile = OrganizerPaymentProfile::firstOrCreate(['organizer_id' => $organizerId]);
        abort_unless((int) $profile->organizer_id === (int) $organizerId, 403);

        if (in_array($profile->kyc_status, ['activated','suspended'], true)) {
            return back()->withErrors(['kyc' => 'This payout profile cannot be edited in its current status.']);
        }

        $data = $request->validate([
            'business_type' => ['required', Rule::in(self::TYPES)],
            'legal_business_name' => 'required|string|max:255',
            'brand_name' => 'nullable|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => ['required','regex:/^[6-9][0-9]{9}$/'],
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'address_city' => 'required|string|max:120',
            'address_state' => 'required|string|max:120',
            'address_postcode' => ['required','regex:/^[1-9][0-9]{5}$/'],
            'address_country' => ['required','size:2'],
            'business_category' => 'required|string|max:120',
            'business_subcategory' => 'required|string|max:120',
            'pan' => ['nullable','regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'stakeholder_pan' => ['nullable','regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'gstin' => ['nullable','size:15','regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/'],
            'bank_account_holder' => 'required|string|max:255',
            'bank_ifsc' => ['required','regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'bank_account_number' => ['nullable','string','regex:/^[0-9]{6,20}$/'],
            'submit_kyc' => 'nullable|boolean',
        ]);

        if (!empty($data['pan'])) $data['pan'] = strtoupper($data['pan']); else unset($data['pan']);
        if (!empty($data['stakeholder_pan'])) $data['stakeholder_pan'] = strtoupper($data['stakeholder_pan']); else unset($data['stakeholder_pan']);
        if (!empty($data['gstin'])) $data['gstin'] = strtoupper($data['gstin']); else unset($data['gstin']);
        $data['bank_ifsc'] = strtoupper($data['bank_ifsc']);
        $data['address_country'] = strtoupper($data['address_country']);

        $effectivePan = $data['pan'] ?? $profile->pan;
        $effectiveStakeholderPan = $data['stakeholder_pan'] ?? $profile->stakeholder_pan;
        $effectiveGstin = $data['gstin'] ?? $profile->gstin;
        if (empty($effectivePan)) {
            throw ValidationException::withMessages(['pan' => 'Business PAN is required.']);
        }
        if (in_array($data['business_type'], self::ENTITY_TYPES, true) && empty($effectiveStakeholderPan)) {
            throw ValidationException::withMessages(['stakeholder_pan' => 'A stakeholder/person PAN is required for this business type.']);
        }
        if (!empty($effectiveGstin) && substr($effectiveGstin, 2, 10) !== $effectivePan) {
            throw ValidationException::withMessages(['gstin' => 'GSTIN characters 3-12 must match the business PAN.']);
        }
        if (empty($data['bank_account_number']) && empty($profile->bank_account_last4)) {
            throw ValidationException::withMessages(['bank_account_number' => 'Bank account number is required.']);
        }

        if (!empty($data['bank_account_number'])) {
            $data['bank_account_last4'] = substr($data['bank_account_number'], -4);
        }
        unset($data['bank_account_number']);

        $submit = (bool) ($data['submit_kyc'] ?? false);
        unset($data['submit_kyc']);

        $profile->fill($data);
        $profile->kyc_status = $submit ? 'submitted' : 'draft';
        $profile->submitted_at = $submit ? now() : null;
        if ($submit) {
            $profile->kyc_remarks = null;
        }
        $profile->save();

        return redirect()->route('organizer.payouts.kyc')
            ->with('success', $submit ? 'Payout details submitted for verification.' : 'Payout details saved as draft.');
    }
}