@extends('organizer.layout')

@section('content')
  @php
    $fullName = trim($organizerInfo->name ?? $organizer->username);
    $nameParts = preg_split('/\s+/', $fullName);
    $firstName = $nameParts[0] ?? $organizer->username;
    $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : 'Organizer';
  @endphp

  <div class="page-header">
    <h4 class="page-title">{{ __('AI Token Checkout') }}</h4>
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
        <a href="{{ route('organizer.ai_token_purchase.packages') }}">{{ __('Buy Package') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Checkout') }}</a>
      </li>
    </ul>
  </div>

  <form action="{{ route('organizer.ai_token_purchase.pay') }}" method="POST" enctype="multipart/form-data" id="aiTokenPaymentForm">
    @csrf
    <input type="hidden" name="sameas_shipping" value="1">

    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header">
            <div class="card-title">{{ __('Billing Information') }}</div>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('First Name') }} *</label>
                  <input type="text" name="fname" class="form-control" value="{{ old('fname', $firstName) }}">
                  @error('fname')
                    <p class="text-danger mb-0">{{ $message }}</p>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('Last Name') }} *</label>
                  <input type="text" name="lname" class="form-control" value="{{ old('lname', $lastName) }}">
                  @error('lname')
                    <p class="text-danger mb-0">{{ $message }}</p>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('Email') }} *</label>
                  <input type="email" name="email" class="form-control" value="{{ old('email', $organizer->email) }}">
                  @error('email')
                    <p class="text-danger mb-0">{{ $message }}</p>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('Phone') }} *</label>
                  <input type="text" name="phone" class="form-control" value="{{ old('phone', $organizer->phone) }}">
                  @error('phone')
                    <p class="text-danger mb-0">{{ $message }}</p>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('Country') }} *</label>
                  <input type="text" name="country" class="form-control" value="{{ old('country', $organizerInfo->country ?? '') }}">
                  @error('country')
                    <p class="text-danger mb-0">{{ $message }}</p>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('State') }}</label>
                  <input type="text" name="state" class="form-control" value="{{ old('state', $organizerInfo->state ?? '') }}">
                  @error('state')
                    <p class="text-danger mb-0">{{ $message }}</p>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('City') }} *</label>
                  <input type="text" name="city" class="form-control" value="{{ old('city', $organizerInfo->city ?? '') }}">
                  @error('city')
                    <p class="text-danger mb-0">{{ $message }}</p>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('Zip Code') }} *</label>
                  <input type="text" name="zip_code" class="form-control" value="{{ old('zip_code', $organizerInfo->zip_code ?? '') }}">
                  @error('zip_code')
                    <p class="text-danger mb-0">{{ $message }}</p>
                  @enderror
                </div>
              </div>

              <div class="col-12">
                <div class="form-group">
                  <label>{{ __('Address') }} *</label>
                  <textarea name="address" class="form-control" rows="4">{{ old('address', $organizerInfo->address ?? '') }}</textarea>
                  @error('address')
                    <p class="text-danger mb-0">{{ $message }}</p>
                  @enderror
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-title">{{ __('Payment Method') }}</div>
          </div>
          <div class="card-body">
            <div class="form-group">
              <label>{{ __('Select Gateway') }} *</label>
              <select name="gateway" class="form-control" id="organizerAiGateway">
                <option disabled selected>{{ __('Choose an Option') }}</option>
                @foreach ($onlineGateways as $gateway)
                  <option value="{{ $gateway->keyword }}" {{ old('gateway') == $gateway->keyword ? 'selected' : '' }}>
                    {{ __($gateway->name) }}
                  </option>
                @endforeach
                @foreach ($offlineGateways as $gateway)
                  <option value="{{ $gateway->id }}" {{ old('gateway') == $gateway->id ? 'selected' : '' }}>
                    {{ __($gateway->name) }}
                  </option>
                @endforeach
              </select>
              @error('gateway')
                <p class="text-danger mb-0">{{ $message }}</p>
              @enderror
            </div>

            <div class="form-group organizer-iyzico-field {{ old('gateway') == 'iyzico' ? '' : 'd-none' }}">
              <label>{{ __('Identity Number') }} *</label>
              <input type="text" name="identity_number" class="form-control" value="{{ old('identity_number') }}">
              @error('identity_number')
                <p class="text-danger mb-0">{{ $message }}</p>
              @enderror
            </div>

            <div id="organizerStripeElementWrapper" class="{{ old('gateway') == 'stripe' ? '' : 'd-none' }}">
              <div id="organizerStripeElement" class="mb-2"></div>
              <div id="organizerStripeErrors" class="text-danger"></div>
            </div>

            @foreach ($offlineGateways as $offlineGateway)
              <div
                class="organizer-offline-gateway {{ $errors->has('attachment') && request()->session()->get('organizer_ai_gateway_id') == $offlineGateway->id ? '' : 'd-none' }}"
                id="organizer-offline-gateway-{{ $offlineGateway->id }}">
                @if (!is_null($offlineGateway->short_description))
                  <div class="form-group">
                    <label>{{ __('Description') }}</label>
                    <p class="mb-0">{{ $offlineGateway->short_description }}</p>
                  </div>
                @endif

                @if (!is_null($offlineGateway->instructions))
                  <div class="form-group">
                    <label>{{ __('Instructions') }}</label>
                    <div class="border rounded p-3">
                      {!! $offlineGateway->instructions !!}
                    </div>
                  </div>
                @endif

                <div class="form-group">
                  <label>{{ __('Payment Proof Image') }} *</label>
                  <input type="file" name="attachment" class="form-control-file" accept=".jpg,.jpeg,.png,.webp,image/*">
                  <small class="text-muted d-block">{{ __('Allowed: jpg, jpeg, png, webp. Max size 2 MB') .'.' }}</small>
                  @error('attachment')
                    <p class="text-danger mb-0">{{ $message }}</p>
                  @enderror
                </div>
              </div>
            @endforeach
          </div>
          <div class="card-footer text-right">
            <button type="submit" class="btn btn-primary">{{ __('Proceed to Payment') }}</button>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card">
          <div class="card-header">
            <div class="card-title">{{ __('Package Summary') }}</div>
          </div>
          <div class="card-body">
            <ul class="list-unstyled mb-0">
              <li class="mb-2"><strong>{{ __('Transaction ID') }}:</strong> {{ $checkoutInvoiceNo }}</li>
              <li class="mb-2"><strong>{{ __('Package') }}:</strong> {{ $packageTitle }}</li>
              <li class="mb-2 text-uppercase"><strong>{{ __('AI Engine') }}:</strong> {{ $package->ai_engine }}</li>
              <li class="mb-2"><strong>{{ __('Content Tokens') }}:</strong> {{ $package->ai_token_limit }}</li>
              <li class="mb-2"><strong>{{ __('Image Limit') }}:</strong> {{ $package->ai_image_limit }}</li>
              <li class="mb-2"><strong>{{ __('Order Status') }}:</strong> {{ __('Awaiting Payment') }}</li>
              <li><strong>{{ __('Payable Amount') }}:</strong> {{ symbolPrice($package->price) }}</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </form>
@endsection

@section('script')
  @if (!empty($stripeKey))
    <script src="https://js.stripe.com/v3/"></script>
  @endif
  <script>
    window.organizerAiTokenCheckout = {
      stripeKey: @json($stripeKey),
      selectedGateway: @json(old('gateway'))
    };
  </script>
  <script src="{{ asset('assets/admin/js/organizer-ai-token-checkout.js') }}"></script>
@endsection
