@extends('backend.layout')

@section('content')
  <div class="mt-2 mb-4">
    <h2 class="{{ $settings->admin_theme_version == 'light' ? 'text-dark' : 'text-light' }} pb-2">{{ __('Welcome back,') }}
      {{ Auth::guard('admin')->user()->first_name . ' ' . Auth::guard('admin')->user()->last_name . '!' }}</h2>
  </div>

  {{-- dashboard information start --}}
  @php
    if (!is_null($roleInfo)) {
        $rolePermissions = json_decode($roleInfo->permissions);
    }
  @endphp

  <div class="row dashboard-items">

    @if (is_null($roleInfo) || (!empty($rolePermissions) && in_array('Lifetime Earning', $rolePermissions)))
      <div class="col-sm-6 col-md-4">
        <a href="{{ route('admin.monthly_earning') }}">
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
                    <p class="card-category">{{ __('Life Time Earning') }}</p>
                    <h4 class="card-title">
                      {{ adminDefaultCurrency($total_earning->total_revenue) }}

                    </h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>
    @endif

    @if (is_null($roleInfo) || (!empty($rolePermissions) && in_array('Total Profit', $rolePermissions)))
      <div class="col-sm-6 col-md-4">
        <a href="{{ route('admin.monthly_profit') }}">
          <div class="card card-stats card-earning card-round text-white ">
            <div class="card-body">
              <div class="row">
                <div class="col-5">
                  <div class="icon-big text-center">
                    <i class="fas fa-usd-square"></i>
                  </div>
                </div>

                <div class="col-7 col-stats">
                  <div class="numbers">
                    <p class="card-category text-white">{{ __('Total Profit') }}</p>
                    <h4 class="card-title text-white">

                     {{ adminDefaultCurrency($total_earning->total_revenue) }}
                    </h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>
    @endif
    @if (is_null($roleInfo) || (!empty($rolePermissions) && in_array('Event Management', $rolePermissions)))
      <div class="col-sm-6 col-md-4">
        <a href="{{ route('admin.event_management.event', ['language' => $defaultLang->code]) }}">
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
                    <h4 class="card-title">{{ $totalEvents }}</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>
    @endif

    @if (is_null($roleInfo) || (!empty($rolePermissions) && in_array('Event Management', $rolePermissions)))
      <div class="col-sm-6 col-md-4">
        <a href="{{ route('admin.event_management.categories', ['language' => $defaultLang->code]) }}">
          <div class="card card-stats card-danger card-round">
            <div class="card-body">
              <div class="row">
                <div class="col-5">
                  <div class="icon-big text-center">
                    <i class="fal fa-sitemap"></i>
                  </div>
                </div>

                <div class="col-7 col-stats">
                  <div class="numbers">
                    <p class="card-category">{{ __('Event Categories') }}</p>
                    <h4 class="card-title">{{ $totalEventCategories }}</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>
    @endif


    @if (is_null($roleInfo) || (!empty($rolePermissions) && in_array('Transaction', $rolePermissions)))
      <div class="col-sm-6 col-md-4">
        <a href="{{ route('admin.transcation') }}">
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
                    <p class="card-category">{{ __('Total Transaction') }}</p>
                    <h4 class="card-title">{{ $transcation_count }}
                    </h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>
    @endif

    @if (is_null($roleInfo) || (!empty($rolePermissions) && in_array('Event Bookings', $rolePermissions)))
      <div class="col-sm-6 col-md-4">
        <a href="{{ route('admin.event.booking') }}">
          <div class="card card-stats card-primary card-round">
            <div class="card-body">
              <div class="row">
                <div class="col-5">
                  <div class="icon-big text-center">
                    <i class="fas fa-hotel"></i>
                  </div>
                </div>
                <div class="col-7 col-stats">
                  <div class="numbers">
                    <p class="card-category">{{ __('Total Event Booking') }}</p>
                    <h4 class="card-title">{{ $totalEventBookings }}</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>
    @endif

    @if (is_null($roleInfo) || (!empty($rolePermissions) && in_array('Organizer Mangement', $rolePermissions)))
      <div class="col-sm-6 col-md-4">
        <a href="{{ route('admin.organizer_management.registered_organizer', ['language' => $defaultLang->code]) }}">
          <div class="card card-stats card-warning card-round">
            <div class="card-body">
              <div class="row">
                <div class="col-5">
                  <div class="icon-big text-center">
                    <i class="fal fa-chalkboard-teacher"></i>
                  </div>
                </div>

                <div class="col-7 col-stats">
                  <div class="numbers">
                    <p class="card-category">{{ __('Organizers') }}</p>
                    <h4 class="card-title">{{ $totalOrganizers }}</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>
    @endif

    @if (is_null($roleInfo) || (!empty($rolePermissions) && in_array('Blog Management', $rolePermissions)))
      <div class="col-sm-6 col-md-4">
        <a href="{{ route('admin.blog_management.blogs', ['language' => $defaultLang->code]) }}">
          <div class="card card-stats card-info card-round">
            <div class="card-body">
              <div class="row">
                <div class="col-5">
                  <div class="icon-big text-center">
                    <i class="fal fa-blog"></i>
                  </div>
                </div>

                <div class="col-7 col-stats">
                  <div class="numbers">
                    <p class="card-category">{{ __('Blog') }}</p>
                    <h4 class="card-title">{{ $totalBlog }}</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>
    @endif

    @if (is_null($roleInfo) || (!empty($rolePermissions) && in_array('Customer Management', $rolePermissions)))
      <div class="col-sm-6 col-md-4">
        <a href="{{ route('admin.organizer_management.registered_customer') }}">
          <div class="card card-stats card-secondary card-round">
            <div class="card-body">
              <div class="row">
                <div class="col-5">
                  <div class="icon-big text-center">
                    <i class="la flaticon-users"></i>
                  </div>
                </div>

                <div class="col-7 col-stats">
                  <div class="numbers">
                    <p class="card-category">{{ __('Registered Customers') }}</p>
                    <h4 class="card-title">{{ $totalRegisteredUsers }}</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>
    @endif

    @if (is_null($roleInfo) || (!empty($rolePermissions) && in_array('Shop Management', $rolePermissions)))
      <div class="col-sm-6 col-md-4">
        <a href="{{ route('admin.shop_management.products', ['language' => $defaultLang->code]) }}">
          <div class="card card-stats card-danger card-round">
            <div class="card-body">
              <div class="row">
                <div class="col-5">
                  <div class="icon-big text-center">
                    <i class="fas fa-shopping-basket"></i>
                  </div>
                </div>

                <div class="col-7 col-stats">
                  <div class="numbers">
                    <p class="card-category">{{ __('Products') }}</p>
                    <h4 class="card-title">{{ $totalProducts }}</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>
    @endif
    @if (is_null($roleInfo) || (!empty($rolePermissions) && in_array('Shop Management', $rolePermissions)))
      <div class="col-sm-6 col-md-4">
        <a href="{{ route('admin.product.order') }}">
          <div class="card card-stats card-success card-round">
            <div class="card-body">
              <div class="row">
                <div class="col-5">
                  <div class="icon-big text-center">
                    <i class="fas fa-receipt"></i>
                  </div>
                </div>

                <div class="col-7 col-stats">
                  <div class="numbers">
                    <p class="card-category">{{ __('Orders') }}</p>
                    <h4 class="card-title">{{ $totalOrders }}</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>
    @endif

    @if ((is_null($roleInfo) || (!empty($rolePermissions) && in_array('AI Token Management', $rolePermissions))) &&
            isset($ai_usage_stats) &&
            $ai_usage_stats->count() > 0)
      <div class="w-100"></div>
      @php
        $tokenGradients = ['admin-ai-gradient-token-1', 'admin-ai-gradient-token-2'];
        $imageGradients = ['admin-ai-gradient-image-1', 'admin-ai-gradient-image-2'];
      @endphp

      @foreach ($ai_usage_stats as $index => $usage)
        <div class="col-xl-3 col-lg-6 col-md-6">
          <div class="admin-ai-card {{ $tokenGradients[$index % count($tokenGradients)] }}">
            <div class="admin-ai-header">
              <span class="admin-ai-badge">{{ __('Token Usage') }}</span>
              <div class="admin-ai-header-right">
                <span class="admin-ai-engine">{{ $usage['engine'] }}</span>
                <button type="button" class="admin-ai-info-btn" data-admin-ai-tooltip="token-{{ $index }}">
                  <i class="fas fa-info-circle"></i>
                </button>
              </div>
            </div>

            <div class="admin-ai-body">
              <div class="admin-ai-icon">
                <i class="fas fa-coins"></i>
              </div>
              <div class="admin-ai-stats">
                <div class="admin-ai-item">
                  <span>{{ __('Required AI Tokens') }}</span>
                  <strong>{{ number_format($usage['token']['total']) }}</strong>
                </div>
                <div class="admin-ai-item">
                  <span>{{ __('Used AI Tokens') }}</span>
                  <strong>{{ number_format($usage['token']['used']) }}</strong>
                </div>
                <div class="admin-ai-item">
                  <span>{{ __('Remaining AI Tokens') }}</span>
                  <strong>{{ number_format($usage['token']['remaining']) }}</strong>
                </div>
              </div>
            </div>

            <div class="admin-ai-tooltip-template d-none" id="token-{{ $index }}">
              <h6>{{ __('AI Token Statistics') }} ({{ $usage['engine'] }})</h6>
              <p><strong>{{ __('Scope') }}:</strong> {{ __('Combined data for all organizers') .'.' }}</p>
              <p><strong>{{ __('Required AI Tokens') }}:</strong>
                {{ __('Total purchased/allocated tokens across all organizers for this engine') . '.' }}</p>
              <p><strong>{{ __('Used AI Tokens') }}:</strong>
                {{ __('Total tokens already consumed by organizers for this engine') . '.' }}</p>
              <p><strong>{{ __('Remaining AI Tokens') }}:</strong>
                {{ __('Required minus used tokens (combined)') . '
                .'}}</p>
            </div>
          </div>
        </div>
      @endforeach

      @foreach ($ai_usage_stats as $index => $usage)
        <div class="col-xl-3 col-lg-6 col-md-6">
          <div class="admin-ai-card {{ $imageGradients[$index % count($imageGradients)] }}">
            <div class="admin-ai-header">
              <span class="admin-ai-badge">{{ __('Image Usage') }}</span>
              <div class="admin-ai-header-right">
                <span class="admin-ai-engine">{{ $usage['engine'] }}</span>
                <button type="button" class="admin-ai-info-btn" data-admin-ai-tooltip="image-{{ $index }}">
                  <i class="fas fa-info-circle"></i>
                </button>
              </div>
            </div>

            <div class="admin-ai-body">
              <div class="admin-ai-icon">
                <i class="fas fa-image"></i>
              </div>
              <div class="admin-ai-stats">
                <div class="admin-ai-item">
                  <span>{{ __('Required AI Images') }}</span>
                  <strong>{{ number_format($usage['image']['total']) }}</strong>
                </div>
                <div class="admin-ai-item">
                  <span>{{ __('Used AI Images') }}</span>
                  <strong>{{ number_format($usage['image']['used']) }}</strong>
                </div>
                <div class="admin-ai-item">
                  <span>{{ __('Remaining AI Images') }}</span>
                  <strong>{{ number_format($usage['image']['remaining']) }}</strong>
                </div>
              </div>
            </div>

            <div class="admin-ai-tooltip-template d-none" id="image-{{ $index }}">
              <h6>{{ __('AI Image Statistics') }} ({{ $usage['engine'] }})</h6>
              <p><strong>{{ __('Scope') }}:</strong> {{ __('Combined data for all organizers') .'.'}}</p>
              <p><strong>{{ __('Required AI Images') }}:</strong>
                {{ __('Total purchased/allocated image credits across all organizers for this engine') .'.' }}</p>
              <p><strong>{{ __('Used AI Images') }}:</strong>
                {{ __('Total generated images consumed by organizers for this engine') . '.' }}</p>
              <p><strong>{{ __('Remaining AI Images') }}:</strong>
                {{ __('Required minus used image credits (combined)') . '.' }}</p>
            </div>
          </div>
        </div>
      @endforeach
    @endif

  </div>

  @if (is_null($roleInfo) || (!empty($rolePermissions) && in_array('Event Management', $rolePermissions)))
    <div class="row">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <div class="card-title">{{ __('Event Booking Monthly Earning') }} ({{ date('Y') }})</div>
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
  @endif

  {{-- product chart --}}
  @if (is_null($roleInfo) || (!empty($rolePermissions) && in_array('Shop Management', $rolePermissions)))
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{ __('Product Order Monthly Income') }} ({{ date('Y') }})</div>
        </div>

        <div class="card-body">
          <div class="chart-container">
            <canvas id="ProductOrderChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  @endif

  @if (is_null($roleInfo) || (!empty($rolePermissions) && in_array('Shop Management', $rolePermissions)))
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{ __('Monthly Product Orders') }} ({{ date('Y') }})</div>
        </div>

        <div class="card-body">
          <div class="chart-container">
            <canvas id="TotalProductOrderChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  @endif
  </div>
  {{-- dashboard information end --}}
@endsection

@section('script')
  {{-- chart js --}}
  <script type="text/javascript" src="{{ asset('assets/admin/js/chart.min.js') }}"></script>

  <script>
    "use strict";
    const monthArr = @php echo json_encode($eventMonths) @endphp;
    const incomeArr = @php echo json_encode($eventIncomes) @endphp;
    const totalBookings = @php echo json_encode($totalBookings) @endphp;

    const productIncome = @php echo json_encode($productIncome) @endphp;
    const totalOders = @php echo json_encode($totalOders) @endphp;
  </script>

  <script type="text/javascript" src="{{ asset('assets/admin/js/chart-init.js') }}"></script>
@endsection
