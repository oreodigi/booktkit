<div class="modal fade" id="aiSliderModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Generate Images') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @php
                $engineCount = count($aiEngines);
            @endphp

            <div class="modal-body">
                @if ($engineCount > 1)
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group mb-0">
                                <label class="mb-1 d-block label-color-4">{{ __('Select AI Engine') }}</label>
                                <select id="ai_slider_engine" name="engine" class="form-control">
                                    @foreach ($aiEngines as $engine)
                                        <option value="{{ $engine }}">{{ ucfirst($engine) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @else
                    <input type="hidden" id="ai_slider_engine" name="engine" value="{{ $aiEngines[0] ?? '' }}">
                @endif
                <div class="form-group">
                    <label class="mb-1 d-block label-color-4">{{ __('Prompt') }} <span
                            class="text-danger">*</span></label>
                    <textarea id="ai_slider_prompt" class="form-control" rows="4"
                        placeholder="{{ __('Example') . ': ' . __('Modern product showcase, clean background, commercial look') }}"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="label-color-4">{{ __('Number of Images') }}</label>
                            <input type="number" min="1" max="10" class="form-control"
                                id="ai_slider_count" value="3">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="label-color-4">{{ __('Art Style') }}</label>
                            <select class="form-control" id="ai_slider_style">
                                <option value="photorealistic">{{ __('Photorealistic') }}</option>
                                <option value="3d_render">{{ __('3D Render') }}</option>
                                <option value="flat_illustration">{{ __('Flat Illustration') }}</option>
                                <option value="minimal">{{ __('Minimal') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="label-color-4">{{ __('Lighting') }}</label>
                            <select class="form-control" id="ai_slider_lighting">
                                <option value="natural">{{ __('Natural Light') }}</option>
                                <option value="studio">{{ __('Studio Light') }}</option>
                                <option value="soft">{{ __('Soft Light') }}</option>
                                <option value="dramatic">{{ __('Dramatic') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="label-color-4">{{ __('Camera Angle') }}</label>
                            <select class="form-control" id="ai_slider_angle">
                                <option value="eye_level">{{ __('Eye-level') }}</option>
                                <option value="top_down">{{ __('Top-down') }}</option>
                                <option value="close_up">{{ __('Close-up') }}</option>
                                <option value="wide">{{ __('Wide') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group mb-0">
                            <label class="label-color-4">{{ __('Image Size') }}</label>
                            <select class="form-control" id="ai_slider_size">
                                <option value="custom_1170_570">{{ __('Gallery') . ' (' . __('1170x570') . ')' }}
                                </option>
                                <option value="square_1024">{{ __('Square') . ' (' . __('1024x1024') . ')' }}</option>
                                <option value="portrait_1024_1536">{{ __('Portrait') . ' (' . __('1024x1536') . ')' }}
                                </option>
                                <option value="landscape_1536_1024">
                                    {{ __('Landscape') . ' (' . __('1536x1024') . ')' }}</option>

                            </select>
                        </div>
                    </div>

                </div>

                <div id="aiSliderErr" class="text-danger mt-3 d-none"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary"
                    id="aiSliderConfirmBtn">{{ __('Generate Images') }}</button>
            </div>
        </div>
    </div>
</div>
