<div class="modal fade" id="aiItemSeoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            @php
                $engineCount = count($aiEngines);
            @endphp

            <div class="modal-header">
                <h5 class="modal-title" id="aiItemSeoModalTitle">{{ __('AI Generate Event Content') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    {{ __('AI will generate content for the selected field and selected language') . '.' }}
                </div>
                <input type="hidden" id="ai_item_field" value="">
                <input type="hidden" id="ai_item_lang" value="">

                @if ($engineCount > 1)
                    <div class="form-group">
                        <label class="mb-1 d-block label-color-4">{{ __('Select AI Engine') }}</label>
                        <select id="ai_engine" class="form-control">
                            @foreach ($aiEngines as $engine)
                                <option value="{{ $engine }}">{{ ucfirst($engine) }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" id="ai_engine" value="{{ $aiEngines[0] ?? '' }}">
                @endif

                <div class="form-group">
                    <label class="mb-1 d-block label-color-4">{{ __('Prompt') }} <span
                            class="text-danger">*</span></label>
                    <textarea id="ai_item_prompt" class="form-control" rows="6"
                        placeholder="{{ __('Example') . ' :' . __('Premium leather wallet, handmade, slim, RFID, for men. Tone: premium, persuasive. Include keywords: wallet, leather, rfid') . '.' }}"></textarea>
                    <small class="text-muted d-block mt-1">
                        {{ __('Tip') . ': ' . __('Write features, audience, use-case, tone, and keywords') }}
                    </small>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="aiItemSeoConfirmBtn">{{ __('Generate') }}</button>
            </div>

        </div>
    </div>
</div>
