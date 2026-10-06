@extends('organizer.layout')

@section('content')
  <div class="mt-2 mb-4">
    <h2 class=" pb-2 ">{{ __('Welcome back') .','}} {{ Auth::guard('organizer')->user()->username . '!' }}</h2>
  </div>

  @if (Session::get('secret_login') != true)
    @if (Auth::guard('organizer')->user()->status == 0 && $admin_setting->organizer_admin_approval == 1)
      <div class="mt-2 mb-4">
        <div class="alert alert-danger text-dark">
          {{ $admin_setting->admin_approval_notice != null ? $admin_setting->admin_approval_notice : __( 'Your account is deactive') }}
        </div>
      </div>
    @endif
  @endif

  <div class="row dashboard-items">
    <div class="col-xl-3 col-lg-6">
      <a href="{{ route('organizer.monthly_income') }}">
        <div class="card card-stats card-info card-round">
          <div class="card-body">
            <div class="row">
              <div class="col-5">
                <div class="icon-big text-center">
                  <i class="fas fa-sack-dollar"></i>
                </div>
              </div>

              <div class="col-7 col-stats">
                <div class="numbers">
                  <p class="card-category">{{ __('My Balance') }}</p>
                  <h4 class="card-title">

                    {{ adminDefaultCurrency(Auth::guard('organizer')->user()->amount) }}

                  </h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-xl-3 col-lg-6">
      <a href="{{ route('organizer.event_management.event', ['language' => $defaultLang->code]) }}">
        <div class="card card-stats card-success card-round">
          <div class="card-body">
            <div class="row">
              <div class="col-5">
                <div class="icon-big text-center">
                  <i class="fas fa-calendar-alt"></i>
                </div>
              </div>

              <div class="col-7 col-stats">
                <div class="numbers">
                  <p class="card-category">{{ __('Events') }}</p>
                  <h4 class="card-title">{{ $total_events }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-xl-3 col-lg-6">
      <a href="{{ route('organizer.event.booking') }}">
        <div class="card card-stats card-danger card-round">
          <div class="card-body">
            <div class="row">
              <div class="col-5">
                <div class="icon-big text-center">
                  <i class="fas fa-presentation"></i>
                </div>
              </div>
              <div class="col-7 col-stats">
                <div class="numbers">
                  <p class="card-category">{{ __('Total Event Bookings') }}</p>
                  <h4 class="card-title">{{ $total_event_bookings }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-xl-3 col-lg-6">
      <a href="{{ route('organizer.transcation') }}">
        <div class="card card-stats card-secondary card-round">
          <div class="card-body">
            <div class="row">
              <div class="col-5">
                <div class="icon-big text-center">
                  <i class="fal fa-exchange-alt"></i>
                </div>
              </div>

              <div class="col-7 col-stats">
                <div class="numbers">
                  <p class="card-category">{{ __('Total Payment Transactions') }}</p>
                  <h4 class="card-title">{{ $transcation_count }}
                  </h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </a>
    </div>

    @if((int) ($settings->ai_system_status ?? 1) === 1 && isset($ai_usage_stats) && $ai_usage_stats->count() > 0)
      @php
        $tokenGradients = ['ai-usage-gradient-token-1', 'ai-usage-gradient-token-2'];
        $imageGradients = ['ai-usage-gradient-image-1', 'ai-usage-gradient-image-2'];
      @endphp

      @foreach ($ai_usage_stats as $index => $usage)
        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
          <div class="ai-usage-card {{ $tokenGradients[$index % count($tokenGradients)] }}">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="ai-usage-badge">{{ __('Token Usage') }}</span>
              <span class="ai-usage-engine">{{ $usage['engine'] }}</span>
            </div>
            <div class="d-flex">
              <div class="ai-usage-icon">
                <i class="fas fa-coins"></i>
              </div>
              <div class="w-100">
                <div class="ai-usage-item">
                  <span>{{ __('Total AI Tokens') }}</span>
                  <strong>{{ number_format($usage['token']['total']) }}</strong>
                </div>
                <div class="ai-usage-item">
                  <span>{{ __('Used AI Tokens') }}</span>
                  <strong>{{ number_format($usage['token']['used']) }}</strong>
                </div>
                <div class="ai-usage-item">
                  <span>{{ __('Remaining AI Tokens') }}</span>
                  <strong>{{ number_format($usage['token']['remaining']) }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>
      @endforeach

      @foreach ($ai_usage_stats as $index => $usage)
        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
          <div class="ai-usage-card {{ $imageGradients[$index % count($imageGradients)] }}">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="ai-usage-badge">{{ __('Image Usage') }}</span>
              <span class="ai-usage-engine">{{ $usage['engine'] }}</span>
            </div>
            <div class="d-flex">
              <div class="ai-usage-icon">
                <i class="fas fa-image"></i>
              </div>
              <div class="w-100">
                <div class="ai-usage-item">
                  <span>{{ __('Total AI Images') }}</span>
                  <strong>{{ number_format($usage['image']['total']) }}</strong>
                </div>
                <div class="ai-usage-item">
                  <span>{{ __('Used AI Images') }}</span>
                  <strong>{{ number_format($usage['image']['used']) }}</strong>
                </div>
                <div class="ai-usage-item">
                  <span>{{ __('Remaining AI Images') }}</span>
                  <strong>{{ number_format($usage['image']['remaining']) }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    @endif

    <div class="w-100"></div>

    <div class="col-lg-6">
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{ __('Event Booking Monthly Income') }} ({{ date('Y') }})</div>
        </div>

        <div class="card-body">
          <div class="chart-container">
            <canvas id="incomeChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{ __('Monthly Event Bookings') }} ({{ date('Y') }})</div>
        </div>

        <div class="card-body">
          <div class="chart-container">
            <canvas id="TotalEventBookingChart"></canvas>
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection

@section('script')
  {{-- chart js --}}
  <script type="text/javascript" src="{{ asset('assets/admin/js/chart.min.js') }}"></script>

  <script>
    "use strict";
    const monthArr = @php echo json_encode($eventMonths) @endphp;
    const incomeArr = @php echo json_encode($eventIncomes) @endphp;
    const totalBookings = @php echo json_encode($totalBookings) @endphp;
  </script>

  <script type="text/javascript" src="{{ asset('assets/admin/js/chart-init.js') }}"></script>
@endsection
