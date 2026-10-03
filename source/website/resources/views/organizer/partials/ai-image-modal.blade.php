{{-- Reusable AI IMAGE GENERATE MODAL --}}
@php
    $engineCount = count($aiEngines);
@endphp
<div class="modal modal-v2 fade" id="aiImageModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header border-0">
                <h5 class="modal-title">{{ __('Generate Image') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                @if ($engineCount > 1)
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="font-weight-bold label-color-4">{{ __('Select AI Engine') }}</label>
                                <select class="form-control" id="ai_img_engine" name="engine">
                                    @foreach ($aiEngines as $engine)
                                        <option value="{{ $engine }}">{{ ucfirst($engine) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @else
                    <input type="hidden" id="ai_img_engine" name="engine" value="{{ $aiEngines[0] ?? '' }}">
                @endif
                <div class="form-group">
                    <label class="font-weight-bold label-color-4">{{ __('Describe Your Image Idea') }} <span
                            class="text-danger">*</span></label>
                    <textarea class="form-control" id="ai_img_prompt" rows="4"
                        placeholder=" {{ __('e.g. ') . __('A modern living room with a view of the city at sunset') }}"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold label-color-4">{{ __('Art Style') }}</label>
                            <select class="form-control" id="ai_img_style">
                                <option value="photorealistic">{{ __('Photorealistic') }}</option>
                                <option value="3d_render">{{ __('3D Render') }}</option>
                                <option value="flat_illustration">{{ __('Flat Illustration') }}</option>
                                <option value="minimal">{{ __('Minimal') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold label-color-4">{{ __('Lighting') }}</label>
                            <select class="form-control" id="ai_img_lighting">
                                <option value="natural">{{ __('Natural Light') }}</option>
                                <option value="studio">{{ __('Studio Light') }}</option>
                                <option value="soft">{{ __('Soft Light') }}</option>
                                <option value="dramatic">{{ __('Dramatic') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold label-color-4">{{ __('Camera Angle') }}</label>
                            <select class="form-control" id="ai_img_angle">
                                <option value="eye_level">{{ __('Eye-level') }}</option>
                                <option value="top_down">{{ __('Top-down') }}</option>
                                <option value="close_up">{{ __('Close-up') }}</option>
                                <option value="wide">{{ __('Wide') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold label-color-4">{{ __('Image Size') }}</label>
                            <select class="form-control" id="ai_img_size">
                                <option value="square_1024">{{ __('Square') . ' (' . __('1024x1024') . ')' }}</option>
                                <option value="portrait_1024_1536">{{ __('Portrait') . ' (' . __('1024x1536') . ')' }}
                                </option>
                                <option value="landscape_1536_1024">
                                    {{ __('Landscape') . ' (' . __('1536x1024') . ')' }}</option>
                                <option value="custom_320_230">{{ __('Custom') . ' (320x230)' }}</option>

                            </select>
                        </div>
                    </div>
                </div>

                <div id="aiImgErr" class="text-danger mt-2 d-none"></div>

                <div class="mt-3 d-none" id="aiImgPreviewWrap">
                    <label class="font-weight-bold">{{ __('Preview') }}</label>
                    <img id="aiImgPreview" src="" class="img-fluid rounded" style="max-height:220px;">
                </div>
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="aiGenerateImagesConfirmBtn">
                    {{ __('Generate Image') }}
                </button>
            </div>
        </div>
    </div>
</div>
