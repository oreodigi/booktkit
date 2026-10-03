    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Edit Currency') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="ajaxEditForm" action="{{ route('admin.currency-update') }}" method="POST">
                        @csrf
                        <input id="in_id" type="hidden" name="id" value="">
                        <div class="form-group">
                            <label for="">{{ __('Text') }} <span class="text-danger">**</span></label>
                            <input id="in_text" type="text" class="form-control" name="text" value=""
                                placeholder="{{ __('Enter text') }}">
                            <p id="editErr_text" class="mb-0 text-danger em"></p>
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Symbol') }} <span class="text-danger">**</span></label>
                            <input id="in_symbol" class="form-control" name="symbol"
                                placeholder="{{ __('Enter symbol') }}">
                            <p id="editErr_symbol" class="mb-0 text-danger em"></p>
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Text Position') }}</label>
                            <select name="text_position" id="in_text_position" class="form-control">
                                <option value="left">{{ __('Left') }}</option>
                                <option value="right">{{ __('Right') }}</option>
                            </select>
                            <p id="editErr_text_position" class="mb-0 text-danger em"></p>
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Symbol Position') }}</label>
                            <select name="symbol_position" id="in_symbol_position" class="form-control">
                                <option value="left">{{ __('Left') }}</option>
                                <option value="right">{{ __('Right') }}</option>
                            </select>
                            <p id="editErr_symbol_position" class="mb-0 text-danger em"></p>
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Rate') }} <span class="text-danger">**</span></label>
                            <input type="number" id="in_value" class="form-control" name="value"
                                placeholder="{{ __('Enter rate') }}">
                            <p id="editErr_value" class="mb-0 text-danger em"></p>
                            <p class="mt-1 mb-0 text-info">
                                <strong>{{ __('Please Enter The Rate For 1') }}
                                    {{ $default_currency->text }} = ?</strong>
                            </p>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button id="updateBtn" type="button" class="btn btn-primary">{{ __('Save Changes') }}</button>
                </div>
            </div>
        </div>
    </div>
