(function (window, $) {
  'use strict';
  if (!$) return console.error('ai-slider-dropzone requires jQuery');

  function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  function showErr(sel, msg) {
    const $b = $(sel);
    if ($b.length) $b.removeClass('d-none').text(msg || 'Something went wrong.');
  }

  function hideErr(sel) {
    const $b = $(sel);
    if ($b.length) $b.addClass('d-none').text('');
  }

  function notify(type, message, title) {
    if (!message) return;

    if (window.toastr && typeof window.toastr[type] === 'function') {
      window.toastr.options = Object.assign({
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 4000,
        extendedTimeOut: 1000,
        newestOnTop: true
      }, window.toastr.options || {});

      window.toastr[type](message, title || 'Notice');
      return;
    }

    if ($.notify) {
      $.notify({
        message: message,
        title: title || 'Notice'
      }, {
        type: type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'danger'),
        placement: {
          from: 'top',
          align: 'right'
        },
        showProgressbar: true,
        time: 1000,
        delay: 4000
      });
    }
  }

  function extractErrorMessage(resp) {
    if (!resp) return '';
    if (typeof resp.message === 'string' && resp.message.trim() !== '') return resp.message;
    if (typeof resp.msg === 'string' && resp.msg.trim() !== '') return resp.msg;

    if (Array.isArray(resp.file) && resp.file.length) return resp.file[0];

    if (resp.errors) {
      const keys = Object.keys(resp.errors);
      for (let i = 0; i < keys.length; i++) {
        const value = resp.errors[keys[i]];
        if (Array.isArray(value) && value.length) return value[0];
        if (typeof value === 'string' && value.trim() !== '') return value;
      }
    }

    const keys = Object.keys(resp);
    for (let i = 0; i < keys.length; i++) {
      const value = resp[keys[i]];
      if (Array.isArray(value) && value.length && typeof value[0] === 'string') return value[0];
    }

    return '';
  }

  function val(sel) {
    if (!sel) return '';
    const $el = $(sel);
    return $el.length ? ($el.val() || '') : '';
  }

  function setIfExists(sel, value) {
    if (!sel) return;
    const $el = $(sel);
    if ($el.length) {
      $el.val(value).trigger('change');
    }
  }

  function finishPreview(dz, file, thumbnailUrl) {
    dz.emit('thumbnail', file, thumbnailUrl);
    dz.emit('complete', file);
    file.status = Dropzone.SUCCESS;
  }

  // Create a "fake" file preview in Dropzone from an image URL
  function addUrlToDropzone(dz, imageUrl, fileId, hiddenWrapSel, hiddenInputName, removeEndpoint, removePayloadKey) {
    // Create a mock file object
    const safeName = String(fileId || '').trim() || ('ai-' + Date.now() + '.jpg');
    const ext = safeName.split('.').pop().toLowerCase();
    const mime = ext === 'png' ? 'image/png' : (ext === 'gif' ? 'image/gif' : 'image/jpeg');
    const mockFile = {
      name: safeName,
      size: 12345,
      type: mime,
      accepted: true,
      status: Dropzone.ADDED,
      upload: { progress: 100, total: 12345, bytesSent: 12345 },
      dataURL: imageUrl
    };
    // Emit events to create preview
    dz.emit('addedfile', mockFile);
    if (typeof dz.createThumbnailFromUrl === 'function') {
      dz.createThumbnailFromUrl(
        mockFile,
        dz.options.thumbnailWidth,
        dz.options.thumbnailHeight,
        dz.options.thumbnailMethod,
        true,
        function (thumbnailUrl) {
          finishPreview(dz, mockFile, thumbnailUrl || imageUrl);
        }
      );
    } else {
      finishPreview(dz, mockFile, imageUrl);
    }

    // Add hidden input like existing upload flow
    $(hiddenWrapSel).append(
      `<input type="hidden" name="${hiddenInputName}" id="slider${fileId}" value="${fileId}">`
    );

    // Add remove button like existing upload flow
    const removeButton = Dropzone.createElement(
      "<button class='btn btn-xs rmv-btn'><i class='fa fa-times'></i></button>"
    );

    removeButton.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      dz.removeFile(mockFile);
      rmvImg(fileId, removeEndpoint, removePayloadKey);
    });

    if (mockFile.previewElement) {
      mockFile.previewElement.appendChild(removeButton);
    }
  }


  function rmvImg(fileId, removeEndpoint, removePayloadKey) {
    const csrf = csrfToken();
    const payload = { _token: csrf };
    payload[removePayloadKey] = fileId;

    $.ajax({
      url: removeEndpoint,
      type: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf },
      data: payload,
      success: function () {
        const ele = document.getElementById("slider" + fileId);
        if (ele) ele.remove();
      }
    });
  }

  // Upload a remote image URL to your existing upload endpoint 
  async function uploadRemoteToServer(uploadEndpoint, imageUrl) {
    const csrf = csrfToken();
    const resp = await $.ajax({
      url: uploadEndpoint,
      type: 'POST',
      dataType: 'json',
      headers: { 'X-CSRF-TOKEN': csrf },
      data: { _token: csrf, image_url: imageUrl } 
    });
    return resp; 
  }

  window.AiSliderDropzone = {
    active: null,

    boot: function () {
      // open modal
      $(document).on('click', '[data-ai-slider-open]', function () {
        const $btn = $(this);

        const dropzoneSel = $btn.data('dropzone') || '#my-dropzone';
        const hiddenWrapSel = $btn.data('hidden-wrap') || '#sliders';

        const dzEl = document.querySelector(dropzoneSel);
        if (!dzEl || !dzEl.dropzone) {
          console.error('Dropzone not found on selector:', dropzoneSel);
          return;
        }

        // store context
        window.AiSliderDropzone.active = {
          btn: $btn,
          dz: dzEl.dropzone,
          endpoint: $btn.data('endpoint'), 
          uploadEndpoint: $btn.data('upload-endpoint'), 
          removeEndpoint: $btn.data('remove-endpoint'), 
          removePayloadKey: $btn.data('remove-key') || 'fileid',
          hiddenInputName: $btn.data('hidden-input-name') || 'slider_images[]',
          hiddenWrapSel: hiddenWrapSel,
          maxCount: parseInt($btn.data('max-count') || '10', 10),
          styleDefault: $btn.data('style') || 'photorealistic',
          lightingDefault: $btn.data('lighting') || 'natural',
          angleDefault: $btn.data('angle') || 'eye_level',
          sizeDefault: $btn.data('size') || 'square_1024'
        };

        // set defaults
        const defCount = parseInt($btn.data('count-default') || '3', 10);
        $('#ai_slider_count').val(defCount);

        hideErr('#aiSliderErr');
        $('#ai_slider_prompt').val('');
        setIfExists('#ai_slider_style', window.AiSliderDropzone.active.styleDefault);
        setIfExists('#ai_slider_lighting', window.AiSliderDropzone.active.lightingDefault);
        setIfExists('#ai_slider_angle', window.AiSliderDropzone.active.angleDefault);
        setIfExists('#ai_slider_size', window.AiSliderDropzone.active.sizeDefault);

        $('#aiSliderModal').modal('show');
      });

      function updateCreditCost() { const n=Math.max(1,parseInt($('#ai_slider_count').val()||'1',10)||1); $('#aiSliderCost').text(n+' AI image '+(n===1?'credit':'credits')); $('#aiSliderButtonCost').text(n+' '+(n===1?'credit':'credits')); }
      $(document).on('input change','#ai_slider_count',updateCreditCost); updateCreditCost();

      // confirm generate
      $(document).on('click', '#aiSliderConfirmBtn', async function () {
        const ctx = window.AiSliderDropzone.active;
        if (!ctx) return;

        const prompt = ($('#ai_slider_prompt').val() || '').trim();
        let count = parseInt($('#ai_slider_count').val() || '1', 10);

        if (!prompt) return showErr('#aiSliderErr', imagePrompt);
        if (isNaN(count) || count < 1) count = 1;
        if (count > ctx.maxCount) count = ctx.maxCount;

        const engine = (val('#ai_slider_engine') || '').trim();
        if (!engine) {
          const engineMsg = 'Please select an AI engine before generating images.';
          showErr('#aiSliderErr', engineMsg);
          notify('warning', engineMsg, 'Warning');
          return;
        }

        hideErr('#aiSliderErr');

        const $btn = $(this);
        const oldHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> ' + imageGenerating + '...');

        try {
          // 1) generate images (expects array of URLs)
          const genResp = await $.ajax({
            url: ctx.endpoint,
            type: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
            data: {
              _token: csrfToken(),
              prompt: prompt,
              count: count,
              style: val('#ai_slider_style'),
              lighting: val('#ai_slider_lighting'),
              angle: val('#ai_slider_angle'),
              size: val('#ai_slider_size'),
              engine: engine
            }
          });

          const ok = genResp && (genResp.status === true || genResp.success === true);
          const urls = genResp ? (genResp.images || genResp.image_urls || []) : [];

          if (!ok || !Array.isArray(urls) || urls.length === 0) {
            const errorMessage = (genResp && genResp.message) ? genResp.message : 'Failed to generate images.';
            showErr('#aiSliderErr', errorMessage);
            notify('error', errorMessage, 'Error');
            return;
          }

          // 2) For each generated URL: upload to server to get file_id
          for (let i = 0; i < urls.length; i++) {
            const imageUrl = urls[i];
            const up = await uploadRemoteToServer(ctx.uploadEndpoint, imageUrl);

            const fileId = up && (up.file_id || up.id || up.uniqueName);
            const previewUrl = up && (up.preview_url || up.url || imageUrl);

            if (!fileId) continue;

            addUrlToDropzone(
              ctx.dz,
              previewUrl,
              fileId,
              ctx.hiddenWrapSel,
              ctx.hiddenInputName,
              ctx.removeEndpoint,
              ctx.removePayloadKey
            );
          }

          $('#aiSliderModal').modal('hide');
        } catch (e) {
          console.error('AI Slider error:', e);
          const errMsg = extractErrorMessage(e && e.responseJSON) || 'Network error. Please try again.';
          showErr('#aiSliderErr', errMsg);
          notify('error', errMsg, 'Error');
        } finally {
          $btn.prop('disabled', false).html(oldHtml);
        }
      });
    }
  };

  $(function () {
    window.AiSliderDropzone.boot();
  });

})(window, window.jQuery);
