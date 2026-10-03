(function (window, $) {
  'use strict';
  if (!$) return console.error('AiImageModal requires jQuery');

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : null;
  }

  function val(sel) {
    if (!sel) return '';
    var $el = $(sel);
    return $el.length ? ($el.val() || '') : '';
  }

  function setIfExists(sel, value) {
    if (!sel) return;
    var $el = $(sel);
    if ($el.length) {
      $el.val(value).trigger('change');
    }
  }

  function setImageIfExists(sel, value) {
    if (!sel) return;
    var $el = $(sel);
    if ($el.length) {
      $el.attr('src', value || '');
    }
  }

  function showErr(sel, msg) {
    var $b = $(sel);
    if ($b.length) $b.removeClass('d-none').text(msg || 'Something went wrong.');
  }

  function hideErr(sel) {
    var $b = $(sel);
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

  //  Find current top z-index among visible modals
  function getTopModalZIndex() {
    var top = 1040; 
    $('.modal.show').each(function () {
      var z = parseInt($(this).css('z-index'), 10);
      if (!isNaN(z)) top = Math.max(top, z);
    });
    return top;
  }

  //  Force a modal to the top 
  function bringModalToFront(modalSelector) {
    var $m = $(modalSelector);

    // IMPORTANT: modal should be a child of body (prevents nesting issues)
    if ($m.length && !$m.parent().is('body')) {
      $m.appendTo('body');
    }

    var topZ = getTopModalZIndex();
    var newZ = topZ + 20; 

    $m.css('z-index', newZ);

    // Backdrop created after .modal('show')
    setTimeout(function () {
      // last backdrop is the newest
      var $backdrop = $('.modal-backdrop').not('.modal-stack-adjusted').last();
      if ($backdrop.length) {
        $backdrop
          .css('z-index', newZ - 10)
          .addClass('modal-stack-adjusted');
      }
      // keep body scroll locked if another modal still open
      if ($('.modal.show').length) {
        $('body').addClass('modal-open');
      }
    }, 0);
  }

  function readConfigFromBtn($btn) {
    return {
      modalId: $btn.data('modal') || '#aiImageModal',
      confirmBtn: $btn.data('confirm') || '#aiGenerateImagesConfirmBtn',

      promptInput: '#ai_img_prompt',
      styleSelect: '#ai_img_style',
      lightingSelect: '#ai_img_lighting',
      angleSelect: '#ai_img_angle',
      sizeSelect: '#ai_img_size',
      engineSelect: $btn.data('engine-select') || '#ai_img_engine',

      errorBox: '#aiImgErr',
      modalPreviewWrap: '#aiImgPreviewWrap',
      modalPreviewImg: '#aiImgPreview',

      endpoint: $btn.data('endpoint'),
      targetPreviewImg: $btn.data('target'),
      hiddenInput: $btn.data('hidden'),
      fileInput: $btn.data('file-input'),
      styleDefault: $btn.data('style') || 'photorealistic',
      lightingDefault: $btn.data('lighting') || 'natural',
      angleDefault: $btn.data('angle') || 'eye_level',
      sizeDefault: $btn.data('size') || 'square_1024',
      confirmText: $btn.data('confirm-text') || 'Generate Image'
    };
  }

  window.AiImageModal = {
    activeConfig: null,
    confirmBound: false,

    boot: function () {

      //  Whenever AI modal is shown, force it to top 
      $(document).on('shown.bs.modal', '#aiImageModal', function () {
        bringModalToFront('#aiImageModal');
      });

      // Open modal from any button
      $(document).on('click', '[data-ai-image-open]', (e) => {
        var $btn = $(e.currentTarget);
        var cfg = readConfigFromBtn($btn);

        if (!cfg.endpoint || !cfg.targetPreviewImg || !cfg.hiddenInput) {
          console.error('AiImageModal: missing data-endpoint/data-target/data-hidden on button', $btn[0]);
          return;
        }

        this.activeConfig = cfg;

        hideErr(cfg.errorBox);
        $(cfg.modalPreviewWrap).addClass('d-none');
        $(cfg.modalPreviewImg).attr('src', '');
        setIfExists(cfg.styleSelect, cfg.styleDefault);
        setIfExists(cfg.lightingSelect, cfg.lightingDefault);
        setIfExists(cfg.angleSelect, cfg.angleDefault);
        setIfExists(cfg.sizeSelect, cfg.sizeDefault);

        // Set confirm button text
        var $confirm = $(cfg.confirmBtn);
        if ($confirm.length) $confirm.text(cfg.confirmText);

        //  Show, then force stacking
        $(cfg.modalId).modal('show');
        bringModalToFront(cfg.modalId);
      });

      // Confirm binding (unchanged)
      if (!this.confirmBound) {
        this.confirmBound = true;

        $(document).on('click', '#aiGenerateImagesConfirmBtn', async (e) => {
          var cfg = this.activeConfig;
          if (!cfg) return;

          var prompt = (val(cfg.promptInput) || '').trim();
          if (!prompt) {
            showErr(cfg.errorBox, imagePrompt);
            return;
          }

          var engine = (val(cfg.engineSelect) || '').trim();
          if (!engine) {
            var engineMsg = 'Please select an AI engine before generating an image.';
            showErr(cfg.errorBox, engineMsg);
            notify('warning', engineMsg, 'Warning');
            return;
          }

          hideErr(cfg.errorBox);

          var $btn = $(e.currentTarget);
          var oldHtml = $btn.html();
          $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> ' + imageGenerating + '...');

          try {
            var resp = await $.ajax({
              url: cfg.endpoint,
              type: 'POST',
              dataType: 'json',
              data: {
                _token: csrfToken(),
                prompt: prompt,
                style: val(cfg.styleSelect),
                lighting: val(cfg.lightingSelect),
                angle: val(cfg.angleSelect),
                size: val(cfg.sizeSelect),
                engine: engine
              }
            });

            var ok = resp && (resp.status === true || resp.success === true);
            var url = resp ? (resp.image_url || resp.url) : null;

            if (ok && url) {
              setImageIfExists(cfg.targetPreviewImg, url);
              setIfExists(cfg.hiddenInput, url);
              if (cfg.fileInput) {
                $(cfg.fileInput).val('');
              }
              if (cfg.promptInput) {
                $(cfg.promptInput).val('').trigger('input');
              }
              $(cfg.modalId).modal('hide');
            } else {
              var errorMessage = (resp && resp.message) ? resp.message : 'Failed to generate image.';
              showErr(cfg.errorBox, errorMessage);
              notify('error', errorMessage, 'Error');
            }
          } catch (err) {
            var errMsg = (err && err.responseJSON && err.responseJSON.message)
              ? err.responseJSON.message
              : 'Network error. Please try again.';
            showErr(cfg.errorBox, errMsg);
            notify('error', errMsg, 'Error');
          } finally {
            $btn.prop('disabled', false).html(oldHtml);
          }
        });
      }

      //  When top modal closes but another modal is still open, keep body locked
      $(document).on('hidden.bs.modal', '#aiImageModal', function () {
        if ($('.modal.show').length) $('body').addClass('modal-open');
      });
    }
  };

  $(function () {
    window.AiImageModal.boot();
  });

})(window, window.jQuery);
