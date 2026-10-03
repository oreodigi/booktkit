    <div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Currency') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="ajaxForm" class="modal-form create" action="{{ route('admin.currency-store') }}"
                        method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="">{{ __('Text') }} <span class="text-danger">**</span></label>
                            <input type="text" class="form-control" name="text" value=""
                                placeholder="{{ __('Enter Text') }}">
                            <p id="err_text" class="mb-0 text-danger em"></p>
                        </div>

                        <div class="form-group">
                            <label for="">{{ __('Symbol') }} <span class="text-danger">**</span></label>
                            <input class="form-control" name="symbol" placeholder="{{ __('Enter Symbol') }}">
                            <p id="err_symbol" class="mb-0 text-danger em"></p>
                        </div>

                        <div class="form-group">
                            <label for="">{{ __('Text Position') }} <span class="text-danger">**</span></label>
                            <select name="text_position" id="" class="form-control">
                                <option value="left">{{ __('Left') }}</option>
                                <option value="right">{{ __('Right') }}</option>
                            </select>
                            <p id="err_text_position" class="mb-0 text-danger em"></p>
                        </div>

                        <div class="form-group">
                            <label for="">{{ __('Symbol Position') }} <span
                                    class="text-danger">**</span></label>
                            <select name="symbol_position" id="" class="form-control">
                                <option value="left">{{ __('Left') }}</option>
                                <option value="right">{{ __('Right') }}</option>
                            </select>
                            <p id="err_symbol_position" class="mb-0 text-danger em"></p>
                        </div>

                        <div class="form-group">
                            <label for="">{{ __('Rate') }} <span class="text-danger">**</span></label>
                            <input type="number" class="form-control" name="value"
                                placeholder="{{ __('Enter rate') }}">
                            <p id="err_value" class="mb-0 text-danger em"></p>
                            <p class="mt-1 mb-0 text-info">
                                <strong>{{ __('Please Enter The Rate For 1') }}
                                    {{ $default_currency->text }} = ?</strong>
                            </p>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button id="submitBtn" type="button" class="btn btn-primary">{{ __('Submit') }}</button>
                </div>
            </div>
        </div>
    </div>
