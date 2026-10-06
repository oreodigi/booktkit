@extends('organizer.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Add Ticket') }}</h4>
        <ul class="breadcrumbs">
            <li class="nav-home">
                <a href="{{ route('organizer.dashboard') }}">
                    <i class="flaticon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Event Management') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a
                    href="{{ route('organizer.event_management.event', ['language' => $defaultLang->code]) }}">{{ __('All Events') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>

            <li class="nav-item">
                <a href="#">
                    {{ strlen($event->title) > 35 ? mb_substr($event->title, 0, 35, 'UTF-8') . '...' : $event->title }}
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a
                    href="{{ route('organizer.event.ticket', ['language' => $defaultLang->code, 'event_id' => $event->event_id, 'event_type' => $eventType->event_type]) }}">{{ __('Tickets') }}</a>
            </li>

            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Add Ticket') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title d-inline-block">{{ __('Add Ticket') }}</div>
                    <a href="{{ route('organizer.event.ticket', ['language' => $defaultLang->code, 'event_id' => $event->event_id, 'event_type' => $eventType->event_type]) }}"
                        class="btn btn-info btn-sm float-right"><i class="fas fa-backward"></i>
                        {{ __('Back') }}</a>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8 offset-lg-2">
                            <div class="alert alert-danger pb-1 dis-none" id="eventErrors">
                                <button type="button" class="close" data-dismiss="alert">×</button>
                                <ul></ul>
                            </div>
                            <form id="eventForm" action="{{ route('organizer.ticket_management.store_ticket') }}"
                                method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="event_type" value="{{ request()->input('event_type') }}">
                                <input type="hidden" name="event_id" value="{{ request()->input('event_id') }}">
                                @if (request()->input('event_type') == 'venue')
                                    <div class="row ">
                                        <!-- ======--variationwise ticket & early bird discount--====== -->
                                        <div class="col-lg-12">
                                            <div class="form-group mt-1">
                                                <label for="">{{ __('Pricing') . '*' }}</label>
                                                <div class="selectgroup w-100">
                                                    <label class="selectgroup-item">
                                                        <input type="radio" name="pricing_type_2" value="free"
                                                            class="selectgroup-input" checked>
                                                        <span class="selectgroup-button">{{ __('Free Tickets') }}</span>
                                                    </label>

                                                    <label class="selectgroup-item">
                                                        <input type="radio" name="pricing_type_2" value="variation"
                                                            class="selectgroup-input">
                                                        <span class="selectgroup-button">{{ __('Variation Wise') }}</span>
                                                    </label>

                                                    <label class="selectgroup-item">
                                                        <input type="radio" name="pricing_type_2" value="normal"
                                                            class="selectgroup-input">
                                                        <span
                                                            class="selectgroup-button">{{ __('Without Variation') }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 d-none" id="variation_pricing">
                                            <div class="form-group">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered ">
                                                        <thead>
                                                            <tr>
                                                                <th>{{ __('Variation Name') }}</th>
                                                                <th>{{ __('Price') . '*' }}</th>
                                                                <th>{{ __('Available Tickets') . '*' }}</th>
                                                                @if ($websiteInfo->event_guest_checkout_status != 1)
                                                                    <th>{{ __('Max ticket for each customer') . '*' }}</th>
                                                                @endif
                                                                <th><a href="javascrit:void(0)"
                                                                        class="btn btn-success btn-sm addRow"><i
                                                                            class="fas fa-plus-circle"></i></a></th>
                                                            </tr>
                                                        <tbody>
                                                            <tr>
                                                                <td>
                                                                    @foreach ($languages as $language)
                                                                        <div class="form-group">
                                                                            <label
                                                                                for="">{{ __('Variation Name') . '*' }}
                                                                                ({{ $language->name }})
                                                                            </label>
                                                                            <input type="text"
                                                                                name="{{ $language->code }}_variation_name[]"
                                                                                class="form-control">
                                                                        </div>
                                                                    @endforeach
                                                                </td>
                                                                <td>
                                                                    <div class="form-group">
                                                                        <label for="">{{ __('Price') . '*' }}
                                                                            ({{ $getCurrencyInfo->text }})
                                                                        </label>
                                                                        <input type="text" name="variation_price[]"
                                                                            class="form-control">
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="from-group mt-1">
                                                                        <input type="checkbox" checked
                                                                            name="v_ticket_available_type[]" value="limited"
                                                                            class="ticket_available_type" id="limited_1"
                                                                            data-id="1">
                                                                        <label for="limited_1"
                                                                            class="limited_1 ">{{ __('Limited') }}</label>

                                                                        <input type="checkbox"
                                                                            name="v_ticket_available_type[]"
                                                                            value="unlimited"
                                                                            class="ticket_available_type d-none"
                                                                            id="unlimited_1" data-id="1">
                                                                        <label for="unlimited_1"
                                                                            class="unlimited_1 d-none">{{ __('Unlimited') }}</label>
                                                                    </div>

                                                                    <div class="form-group" id="input_1">
                                                                        <label for="">{{ __('Ticket Available') }}
                                                                            * </label>
                                                                        <input type="text" name="v_ticket_available[]"
                                                                            value="" class="form-control">
                                                                    </div>
                                                                </td>

                                                                @if ($websiteInfo->event_guest_checkout_status != 1)
                                                                    <td>
                                                                        <div class="from-group mt-1">
                                                                            <input type="checkbox" checked
                                                                                name="v_max_ticket_buy_type[]"
                                                                                value="limited"
                                                                                class="max_ticket_buy_type"
                                                                                id="buy_limited_1" data-id="1">
                                                                            <label for="buy_limited_1"
                                                                                class="buy_limited_1 ">{{ __('Limited') }}</label>

                                                                            <input type="checkbox"
                                                                                name="v_max_ticket_buy_type[]"
                                                                                value="unlimited"
                                                                                class="max_ticket_buy_type d-none"
                                                                                id="buy_unlimited_1" data-id="1">
                                                                            <label for="buy_unlimited_1"
                                                                                class="buy_unlimited_1 d-none">{{ __('Unlimited') }}</label>
                                                                        </div>

                                                                        <div class="form-group" id="input2_1">
                                                                            <label
                                                                                for="">{{ __('Max ticket for each customer') . '*' }}
                                                                            </label>
                                                                            <input type="text"
                                                                                name="v_max_ticket_buy[]"
                                                                                class="form-control">
                                                                        </div>
                                                                    </td>
                                                                @else
                                                                    <input type="hidden" name="v_max_ticket_buy_type[]"
                                                                        value="unlimited">
                                                                    <input type="hidden" name="v_max_ticket_buy[]"
                                                                        class="form-control">
                                                                @endif
                                                                <td>
                                                                    <a href="javascript:void(0)"
                                                                        class="btn btn-danger btn-sm deleteRow">
                                                                        <i class="fas fa-minus"></i></a>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                        </thead>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 d-none" id="normal_pricing">
                                            <div class="form-group">
                                                <label for="">{{ __('Price') }}
                                                    ({{ $getCurrencyInfo->text }})
                                                    *</label>
                                                <input type="number" name="price" class="form-control"
                                                    placeholder="Enter Price">
                                            </div>
                                        </div>

                                        <div class="col-lg-12 d-none" id="early_bird_discount_free">
                                            <div class="form-group mt-1">
                                                <label for="">{{ __('Early Bird Discount') . '*' }}</label>
                                                <div class="selectgroup w-100">
                                                    <label class="selectgroup-item">
                                                        <input type="radio" name="early_bird_discount_type"
                                                            value="disable" class="selectgroup-input" checked>
                                                        <span class="selectgroup-button">{{ __('Disable') }}</span>
                                                    </label>

                                                    <label class="selectgroup-item">
                                                        <input type="radio" name="early_bird_discount_type"
                                                            value="enable" class="selectgroup-input">
                                                        <span class="selectgroup-button">{{ __('Enable') }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 d-none" id="early_bird_dicount">
                                            <div class="row">
                                                <div class="col-lg-3">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Discount') . '*' }}</label>
                                                        <select name="discount_type" class="form-control">
                                                            <option disabled>{{ __('Select Discount Type') }}</option>
                                                            <option value="fixed">{{ __('Fixed') }}</option>
                                                            <option value="percentage">{{ __('Percentage') }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Amount') . '*' }}</label>
                                                        <input type="number" name="early_bird_discount_amount"
                                                            class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Discount End Date') . '*' }}</label>
                                                        <input type="date" name="early_bird_discount_date"
                                                            class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Discount End Time') . '*' }}</label>
                                                        <input type="time" name="early_bird_discount_time"
                                                            class="form-control">
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                        <!-- ======---variationwise ticket & early bird discount--======- --->


                                        <!--- /======---Ticekt limtit & ticket for each customer start--======- --->
                                        <div class="hideInvariatinwiseTicket col-lg-12">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group mt-1">
                                                        <label
                                                            for="">{{ __('Total Number of Available Tickets') . '*' }}</label>
                                                        <div class="selectgroup w-100">
                                                            <label class="selectgroup-item">
                                                                <input type="radio" name="ticket_available_type"
                                                                    value="unlimited" class="selectgroup-input" checked>
                                                                <span
                                                                    class="selectgroup-button">{{ __('Unlimited') }}</span>
                                                            </label>

                                                            <label class="selectgroup-item">
                                                                <input type="radio" name="ticket_available_type"
                                                                    value="limited" class="selectgroup-input">
                                                                <span
                                                                    class="selectgroup-button">{{ __('Limited') }}</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 d-none" id="ticket_available">
                                                    <div class="form-group">
                                                        <label>{{ __('Enter total number of available tickets') . '*' }}</label>
                                                        <input type="number" name="ticket_available"
                                                            placeholder="Enter total number of available tickets"
                                                            class="form-control">
                                                    </div>
                                                </div>

                                                @if ($websiteInfo->event_guest_checkout_status != 1)
                                                    <div class="col-lg-6">
                                                        <div class="form-group mt-1">
                                                            <label
                                                                for="">{{ __('Maximum number of tickets for each customer') . '*' }}</label>
                                                            <div class="selectgroup w-100">
                                                                <label class="selectgroup-item">
                                                                    <input type="radio" name="max_ticket_buy_type"
                                                                        value="unlimited" class="selectgroup-input"
                                                                        checked>
                                                                    <span
                                                                        class="selectgroup-button">{{ __('Unlimited') }}</span>
                                                                </label>

                                                                <label class="selectgroup-item">
                                                                    <input type="radio" name="max_ticket_buy_type"
                                                                        value="limited" class="selectgroup-input">
                                                                    <span
                                                                        class="selectgroup-button">{{ __('Limited') }}</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <input type="hidden" name="max_ticket_buy_type" value="unlimited">
                                                @endif

                                                <div class="col-lg-6 d-none" id="max_buy_ticket">
                                                    <div class="form-group">
                                                        <label>{{ __('Enter Maximum number of tickets for each customer') . '*' }}</label>
                                                        <input type="number" name="max_buy_ticket"
                                                            placeholder="{{ __('Enter Maximum number of tickets for each customer') }}"
                                                            class="form-control">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!----Ticekt limtit & ticket for each customer start----->

                                    </div>
                                @endif
                                @if (request()->input('event_type') == 'online')
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="">
                                                <div class="form-group">
                                                    <label for="">{{ __('Price') }}
                                                        {{ $getCurrencyInfo->text }}</label>
                                                    <input type="number" name="price" id="ticket-pricing"
                                                        class="form-control">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <input type="checkbox" name="pricing_type" value="free" class=""
                                                    id="free_ticket">
                                                <label for="free_ticket">{{ __('Tickets are Free') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label for="">{{ __('Ticket Available') }}</label>
                                                <input type="number" name="ticket_available" class="form-control">
                                            </div>
                                        </div>
                                        <div class="row" id="early_bird_discount_free">
                                            <div class="col-lg-12">
                                                <div class="form-group mt-1">
                                                    <label for="">{{ __('Early Bird Discount') . '*' }}</label>
                                                    <div class="selectgroup w-100">
                                                        <label class="selectgroup-item">
                                                            <input type="radio" name="early_bird_discount_type"
                                                                value="disable" class="selectgroup-input" checked>
                                                            <span class="selectgroup-button">{{ __('Disable') }}</span>
                                                        </label>

                                                        <label class="selectgroup-item">
                                                            <input type="radio" name="early_bird_discount_type"
                                                                value="enable" class="selectgroup-input">
                                                            <span class="selectgroup-button">{{ __('Enable') }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-12 d-none" id="early_bird_dicount">
                                                <div class="row">
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label for="">{{ __('Discount') }}</label>
                                                            <select name="discont_type" class="form-control">
                                                                <option disabled>{{ __('Select Discount Type') }}</option>
                                                                <option value="fixed">{{ __('Fixed') }}</option>
                                                                <option value="percentage">{{ __('Percentage') }}</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label for="">{{ __('Amount') }}</label>
                                                            <input type="number" name="early_bird_discount_amount"
                                                                class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label for="">{{ __('Discount End Date') }}</label>
                                                            <input type="date" name="early_bird_discount_date"
                                                                class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label for="">{{ __('Discount End Time') }}</label>
                                                            <input type="time" name="early_bird_discount_time"
                                                                class="form-control">
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div id="accordion" class="mt-3">
                                    @foreach ($languages as $language)
                                        <div class="version">
                                            <div class="version-header" id="heading{{ $language->id }}">
                                                <h5 class="mb-0">
                                                    <button type="button" class="btn btn-link" data-toggle="collapse"
                                                        data-target="#collapse{{ $language->id }}"
                                                        aria-expanded="{{ $language->is_default == 1 ? 'true' : 'false' }}"
                                                        aria-controls="collapse{{ $language->id }}">
                                                        {{ $language->name . ' ' . __('Language') }}
                                                        {{ $language->is_default == 1 ? '(' . __('Default') . ')' : '' }}
                                                    </button>
                                                </h5>
                                            </div>

                                            <div id="collapse{{ $language->id }}"
                                                class="collapse {{ $language->is_default == 1 ? 'show' : '' }}"
                                                aria-labelledby="heading{{ $language->id }}" data-parent="#accordion">
                                                <div class="version-body">
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Ticket Name') . '*' }}</label>
                                                                <input type="text" name="{{ $language->code }}_title"
                                                                    placeholder="{{ __('Enter Ticket Name') }}"
                                                                    class="form-control">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col">
                                                            <div
                                                                class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Description') }}</label>
                                                                <textarea class="form-control" name="{{ $language->code }}_description"
                                                                    placeholder="{{ __('Enter Description') }}"></textarea>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col">
                                                            @php $currLang = $language; @endphp

                                                            @foreach ($languages as $language)
                                                                @continue($language->id == $currLang->id)

                                                                <div class="form-check py-0">
                                                                    <label class="form-check-label">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            onchange="cloneInput('collapse{{ $currLang->id }}', 'collapse{{ $language->id }}', event)">
                                                                        <span
                                                                            class="form-check-sign">{{ __('Clone for') }}
                                                                            <strong
                                                                                class="text-capitalize text-secondary">{{ $language->name }}</strong>
                                                                            {{ __('language') }}</span>
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="card border mt-4 mb-4" id="admissionPassSettings">
                                    <div class="card-header"><strong>{{ __('Admission & Pass Type') }}</strong><div class="small text-muted">{{ __('Choose what the attendee will use at entry. Physical credentials are assigned to the issued ticket at collection.') }}</div></div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>{{ __('Pass / Credential Type') }} *</label>
                                            <select name="admission_pass_type" id="admissionPassType" class="form-control" required>
                                                @foreach(['mobile_qr'=>'QR Ticket on Phone','qr_wristband'=>'QR Wristband','rfid_wristband'=>'RFID Wristband','rfid_card'=>'RFID Card','nfc_wristband'=>'NFC Wristband','qr_badge'=>'QR Badge / Physical Pass','physical_id'=>'Physical ID Card'] as $value => $label)
                                                    <option value="{{ $value }}" {{ _dummy = old('admission_pass_type', 'mobile_qr') == $value ? 'selected' : '' }}>{{ __($label) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div id="physicalCredentialSettings">
                                            <div class="form-check mb-2"><label class="form-check-label"><input type="checkbox" class="form-check-input" name="collection_required" value="1" {{ old('collection_required', 1) ? 'checked' : '' }}> {{ __('Collection / credential assignment required at venue') }}</label></div>
                                            <div class="form-check mb-3"><label class="form-check-label"><input type="checkbox" class="form-check-input" name="allow_mobile_qr_before_assignment" value="1" {{ old('allow_mobile_qr_before_assignment', 1) ? 'checked' : '' }}> {{ __('Allow mobile ticket QR until physical credential is assigned') }}</label></div>
                                            <div class="form-check mb-2"><label class="form-check-label"><input type="checkbox" class="form-check-input" id="replacementAllowed" name="replacement_allowed" value="1" {{ old('replacement_allowed', 0) ? 'checked' : '' }}> {{ __('Allow lost/damaged credential replacement') }}</label></div>
                                            <div class="row" id="replacementSettings">
                                                <div class="col-md-6"><div class="form-group"><label>{{ __('Maximum Replacements') }}</label><input type="number" min="1" max="100" name="max_replacements" class="form-control" value="{{ old('max_replacements') }}"></div></div>
                                                <div class="col-md-6"><div class="form-group"><label>{{ __('Replacement Fee') }} ({{ $getCurrencyInfo->text }})</label><input type="number" min="0" step="0.01" name="replacement_fee" class="form-control" value="{{ old('replacement_fee', 0) }}"></div></div>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="form-check mb-3"><label class="form-check-label"><input type="checkbox" class="form-check-input" name="exit_scan_required" value="1" {{ old('exit_scan_required', 0) ? 'checked' : '' }}> {{ __('Require exit scan for re-entry tracking') }}</label></div>
                                        <div class="row">
                                            <div class="col-md-6"><div class="form-group"><label>{{ __('Re-entry') }}</label><select name="reentry_policy" id="ticketReentryPolicy" class="form-control"><option value="none">{{ __('No Re-entry') }}</option><option value="limited">{{ __('Limited') }}</option><option value="unlimited">{{ __('Unlimited') }}</option></select></div></div>
                                            <div class="col-md-6" id="ticketMaxReentries"><div class="form-group"><label>{{ __('Maximum Re-entries') }}</label><input type="number" min="1" max="1000" name="max_reentries" class="form-control" value="{{ old('max_reentries') }}"></div></div>
                                        </div>
                                        <div class="alert alert-info mb-0">{{ __('Credential inventory, wristband/card assignment, replacements, gates and live scanning remain available in Access Operations.') }}</div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" id="EventSubmit" class="btn btn-success">
                                {{ __('Save') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


    <script>
        $(function () {
            function syncAdmissionSettings() {
                const physical = $('#admissionPassType').val() !== 'mobile_qr';
                $('#physicalCredentialSettings').toggle(physical);
                $('#replacementSettings').toggle(physical && $('#replacementAllowed').is(':checked'));
                const limited = $('#ticketReentryPolicy').val() === 'limited';
                $('#ticketMaxReentries').toggle(limited).find('input').prop('required', limited);
            }
            $('#admissionPassType,#ticketReentryPolicy,#replacementAllowed').on('change', syncAdmissionSettings);
            $('#ticketReentryPolicy').val(@json(old('reentry_policy', 'none')));
            syncAdmissionSettings();
        });
    </script>
@section('script')
    @php
        $languages = App\Models\Language::get();
        $names = '';
        foreach ($languages as $language) {
            $varitaion_name = $language->code . '_variation_name[]';
            $names .= "<div class='form-group'><label for=''>Variation Name *($language->name)</label><input type='text' name='$varitaion_name' class='form-control'></div>";
        }
    @endphp

    <script>
        let BaseCTxt = "{{ $getCurrencyInfo->base_currency_text }}";
        var names = "{!! $names !!}";
        var guest_checkout_status = "{{ $websiteInfo->event_guest_checkout_status }}";
    </script>
    <script type="text/javascript" src="{{ asset('assets/admin/js/admin-partial.js') }}"></script>
@endsection

@section('variables')
    <script>
        "use strict";
        var storeUrl = "{{ route('organizer.event.imagesstore') }}";
        var removeUrl = "{{ route('organizer.event.imagermv') }}";
        var edit_event_page = 0
    </script>
@endsection


@section('vuescripts')
    <script>
        let app = new Vue({
            el: '#app',
            data() {
                return {
                    variants: [],
                    addons: []
                }
            },
            methods: {
                addVariant() {
                    let n = Math.floor(Math.random() * 11);
                    let k = Math.floor(Math.random() * 1000000);
                    let m = String.fromCharCode(n) + k;
                    this.variants.push({
                        uniqid: m,
                        options: []
                    });
                },
                addOption(vKey) {
                    let n = Math.floor(Math.random() * 11);
                    let k = Math.floor(Math.random() * 1000000);
                    let m = String.fromCharCode(n) + k;
                    this.variants[vKey].options.push({
                        uniqid: m,
                        name: '',
                        price: 0
                    });
                },
                removeVariant(index) {
                    this.variants.splice(index, 1);
                },
                removeOption(vIndex, oIndex) {
                    this.variants[vIndex].options.splice(oIndex, 1);
                },
                addAddOn() {
                    let n = Math.floor(Math.random() * 11);
                    let k = Math.floor(Math.random() * 1000000);
                    let m = String.fromCharCode(n) + k;
                    this.addons.push({
                        uniqid: m
                    });
                },
                removeAddOn(index) {
                    this.addons.splice(index, 1);
                }
            }
        });
    </script>
@endsection
