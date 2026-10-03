@extends('backend.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Currencies') }}</h4>
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
                <a href="#">{{ __('Currencies') }}</a>
            </li>
        </ul>
    </div>
    <div class="row">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="card-title d-inline-block">{{ __('Currencies') }}</div>
                        </div>

                        <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                            <a href="#" class="btn btn-primary float-right btn-sm" data-toggle="modal"
                                data-target="#createModal"><i class="fas fa-plus"></i>
                                {{ __('Add Currency') }}</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">

                            @if (count($currencies) == 0)
                                <h3 class="text-center">{{ __('NO Currency FOUND') }}</h3>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped mt-3" id="basic-datatables">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">{{ __('Text') }}</th>
                                                <th scope="col">{{ __('Symbol') }}</th>
                                                <th scope="col">{{ __('Rate') }}</th>
                                                <th scope="col">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($currencies as $key => $currency)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $currency->text }}</td>
                                                    <td>{{ $currency->symbol }}</td>
                                                    <td>{{ $currency->value }}</td>
                                                    <td>
                                                        <a class="btn btn-info btn-sm editBtn mb-1" href="#editModal"
                                                            data-toggle="modal" data-id="{{ $currency->id }}"
                                                            data-text="{{ $currency->text }}"
                                                            data-value="{{ $currency->value }}"
                                                            data-symbol="{{ $currency->symbol }}"
                                                            data-text_position="{{ $currency->text_position }}"
                                                            data-symbol_position="{{ $currency->symbol_position }}">
                                                            <span class="btn-label">
                                                                <i class="fas fa-edit"></i>
                                                            </span>
                                                        </a>
                                                        <form class="deleteform d-inline-block "
                                                            action="{{ route('admin.currency-delete') }}" method="post">
                                                            @csrf
                                                            <input type="hidden" name="currency_id"
                                                                value="{{ $currency->id }}">
                                                            <button type="submit"
                                                                class="btn btn-danger btn-sm deletebtn  mb-1"
                                                                @disabled($currency->is_default == 1)>
                                                                <span class="btn-label">
                                                                    <i class="fas fa-trash"></i>
                                                                </span>
                                                            </button>
                                                        </form>

                                                        <form class="DefaultForm d-inline-block"
                                                            action="{{ route('admin.currency.make_default', ['id' => $currency->id, 'id2' => 1]) }}"
                                                            method="post">
                                                            @csrf
                                                            <input type="hidden" name="currency_id"
                                                                value="{{ $currency->id }}">
                                                            @if ($currency->is_default != 1)
                                                                <button type="submit"
                                                                    class="DefaultBtn btn btn-secondary btn-sm  mb-1"><span
                                                                        class="btn-label"><i class="fas fa-edit"></i></span>
                                                                    {{ __('Set Default') }}
                                                                </button>
                                                            @else
                                                                <button disabled class="btn btn-secondary btn-sm mb-1"><span
                                                                        class="btn-label"><i
                                                                            class="fas fa-check"></i></span>
                                                                    {{ __('Default') }}
                                                                </button>
                                                            @endif
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
            </div>
        </div>
    </div>
    @include('backend.currencies.create')
    @include('backend.currencies.edit')
@endsection

@section('script')
    <script>
        'use strict';

        $(document).on('click', '.DefaultBtn', function(e) {
            e.preventDefault();

            const $form = $(this).closest('form');

            swal({
                title: 'Are you sure ?',
                text: 'Important: Changing your default currency will affect both event and product pricing. You may need to adjust or reset your event tickets and product prices to reflect the new currency settings',
                type: 'warning',
                buttons: {
                    confirm: {
                        text: 'Yes',
                        className: 'btn btn-success'
                    },
                    cancel: {
                        visible: true,
                        text: 'Cancel',
                        className: 'btn btn-danger'
                    }
                }
            }).then((isConfirm) => {
                if (isConfirm) {
                    $form.submit();
                } else {
                    swal.close();
                }
            });
        });
    </script>
@endsection
