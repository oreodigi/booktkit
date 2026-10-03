@extends('backend.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('AI Token Packages') }}</h4>
    <ul class="breadcrumbs">
      <li class="nav-home">
        <a href="{{ route('admin.dashboard') }}">
          <i class="flaticon-home"></i>
        </a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('AI Token Management') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Token Packages') }}</a>
      </li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-lg-8">
              <div class="card-title d-inline-block">{{ __('Token Packages') }}</div>
            </div>

            <div class="col-lg-4 mt-2 mt-lg-0">
              <a href="{{ route('admin.ai_token_packages.create') }}"
                class="btn btn-primary btn-sm float-lg-right float-left">
                <i class="fas fa-plus"></i> {{ __('Add Token Package') }}
              </a>
            </div>
          </div>
        </div>

        <div class="card-body">
          <div class="row">
            <div class="col-lg-12">
              @if ($packages->count() == 0)
                <h3 class="text-center">{{ __('NO TOKEN PACKAGE FOUND') . '!' }}</h3>
              @else
                <div class="table-responsive">
                  <table class="table table-striped mt-3" id="basic-datatables">
                    <thead>
                      <tr>
                        <th scope="col">#</th>
                        <th scope="col">{{ __('Title') }}</th>
                        <th scope="col">{{ __('AI Engine') }}</th>
                        <th scope="col">{{ __('Token Limit') }}</th>
                        <th scope="col">{{ __('Image Limit') }}</th>
                        <th scope="col">{{ __('Price') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col">{{ __('Actions') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($packages as $package)
                        <tr>
                          <td>{{ $loop->iteration }}</td>
                          <td>{{ $package->title }}</td>
                          <td>{{ strtoupper($package->ai_engine) }}</td>
                          <td>{{ $package->ai_token_limit }}</td>
                          <td>{{ $package->ai_image_limit }}</td>
                          <td>{{ adminDefaultCurrency($package->price) }}</td>
                          <td>
                            @if ($package->status == 1)
                              <span class="badge badge-success">{{ __('Active') }}</span>
                            @else
                              <span class="badge badge-danger">{{ __('Inactive') }}</span>
                            @endif
                          </td>
                          <td>
                            <a href="{{ route('admin.ai_token_packages.edit', ['id' => $package->id]) }}"
                              class="btn btn-secondary mt-1 btn-xs mr-1">
                              <span class="btn-label">
                                <i class="fas fa-edit"></i>
                              </span>
                            </a>

                            <form class="deleteForm d-inline-block"
                              action="{{ route('admin.ai_token_packages.delete', ['id' => $package->id]) }}"
                              method="post">
                              @csrf
                              <button type="submit" class="btn btn-danger mt-1 btn-xs deleteBtn">
                                <span class="btn-label">
                                  <i class="fas fa-trash"></i>
                                </span>
                              </button>
                            </form>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            </div>
          </div>
        </div>

        <div class="card-footer"></div>
      </div>
    </div>
  </div>
@endsection
