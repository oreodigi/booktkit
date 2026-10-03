window.AiFormGenerator = (function ($) {
  'use strict';
  console.log(listingAiLanguages);

  function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')
      ?.getAttribute('content');
  }

  function defaultTitle(type) {
    if (type === 'success') return 'Success';
    if (type === 'warning') return 'Warning';
    return 'Error';
  }

  function ensureToastrOptions() {
    if (!window.toastr) return;
    window.toastr.options = Object.assign({
      closeButton: true,
      progressBar: true,
      positionClass: 'toast-top-right',
      timeOut: 4000,
      extendedTimeOut: 1000,
      newestOnTop: true
    }, window.toastr.options || {});
  }

  function initToastHelper() {
    if (window.AppToast && typeof window.AppToast.show === 'function') return;

    window.AppToast = {
      show: function (type, message, title) {
        if (!message) return;
        const t = (type || 'error').toLowerCase();
        const ttl = title || defaultTitle(t);

        if (window.toastr && typeof window.toastr[t] === 'function') {
          ensureToastrOptions();
          window.toastr[t](message, ttl);
          return;
        }

        if (typeof window.bootnotify === 'function') {
          window.bootnotify(message, ttl, t);
          return;
        }
      }
    };
  }

  function showToast(type, message, title) {
    initToastHelper();
    window.AppToast.show(type, message, title);
  }

  function showPendingToast() {
    try {
      const raw = sessionStorage.getItem('ai_toast_pending');
      if (!raw) return;
      sessionStorage.removeItem('ai_toast_pending');

      const payload = JSON.parse(raw);
      if (payload && payload.message) {
        showToast(payload.type || 'warning', payload.message, payload.title || '');
      }
    } catch (e) {
      sessionStorage.removeItem('ai_toast_pending');
    }
  }

  function toast(type, message, title) {
    const t = (type || '').toLowerCase();
    if (t === 'warning') {
      try {
        sessionStorage.setItem('ai_toast_pending', JSON.stringify({
          type: t,
          message: message,
          title: title || defaultTitle(t)
        }));
      } catch (e) {
        // ignore storage failures
      }
      window.location.reload();
      return;
    }
    showToast(type, message, title);
  }

  function val(source) {
    if (!source) return '';
    if (typeof source === 'function') return source();
    if (typeof source === 'string') return $(source).val() || '';
    return source;
  }

  function collect(obj = {}) {
    let out = {};
    Object.keys(obj).forEach(k => {
      out[k] = val(obj[k]);
    });
    return out;
  }

  //  TinyMCE + tagsinput + normal
  function setValueSmart($el, value) {
    const id = $el.attr('id');

    // TinyMCE
    if (id && window.tinymce && typeof tinymce.get === 'function' && tinymce.get(id)) {
      tinymce.get(id).setContent(value || '');
      return;
    }

    if ($el.hasClass('summernote') && typeof $el.summernote === 'function') {
      $el.summernote('code', value || '');
      $el.trigger('change');
      return;
    }

    // tagsinput
    if ($el.attr('data-role') === 'tagsinput') {
      if (typeof $el.tagsinput === 'function') {
        $el.tagsinput('removeAll');

        if (Array.isArray(value)) {
          value.forEach(v => $el.tagsinput('add', v));
        } else {
          String(value || '')
            .split(',')
            .map(v => v.trim())
            .filter(Boolean)
            .forEach(v => $el.tagsinput('add', v));
        }

        $el.trigger('input');
        return;
      }
    }

    // normal input/textarea
    $el.val(value).trigger('input');
  }

  // dynamic fill: exact [name="key"] then suffix fallback
  function fill(outputs, data, suffix) {
    suffix = (suffix === undefined || suffix === null) ? '_name' : suffix;
    outputs = (typeof outputs === 'function') ? outputs() : outputs;

    // explicit mapping provided
    if (outputs && Object.keys(outputs).length) {
      Object.keys(outputs).forEach(key => {
        if (data[key] !== undefined && $(outputs[key]).length) {
          setValueSmart($(outputs[key]), data[key]);
        }
      });
      return;
    }

    // auto-match by name
    Object.keys(data || {}).forEach(key => {
      let $el = $('[name="' + key + '"]');

      if (!$el.length && suffix !== '') {
        $el = $('[name="' + key + suffix + '"]');
      }

      if ($el.length) {
        setValueSmart($el, data[key]);
      }
    });
  }

  // ---------- open handler ----------
  function handleOpen(cfg, e) {
    e.preventDefault();
    e.stopPropagation();

    const $btn = $(e.currentTarget);

    // modal title
    const title = $btn.data('title');
    if (title && cfg.modalTitleEl) {
      $(cfg.modalTitleEl).text(title);
    }

    // hidden field/lang
    if (cfg.hiddenField) {
      $(cfg.hiddenField).val($btn.data('field') || '');
    }
    if (cfg.hiddenLang) {
      $(cfg.hiddenLang).val($btn.data('lang') || '');
    }
    if (typeof cfg.onOpen === 'function') {
      cfg.onOpen($btn);
    }

    $(cfg.modalId).modal('show');
  }

  function init(cfg) {
    if (!cfg.openBtn || !cfg.confirmBtn || !cfg.endpoint || !cfg.modalId) {
      console.error('AiFormGenerator: invalid config');
      return;
    }

    // open modal
    $(document).on('click', cfg.openBtn, function (e) {
      handleOpen(cfg, e);
    });

    // confirm generate
    $(document).on('click', cfg.confirmBtn, function (e) {
      e.preventDefault();
      e.stopPropagation();

      const prompt = val(cfg.prompt?.from);
      if (!prompt) {
        toast('warning', 'Please write something first');
        return;
      }

      const engine = val(cfg.extra?.engine);
      if (!engine) {
        toast('warning', 'You have not purchased any AI token package yet. Please purchase to use AI features') ;
        return;
      }

      let payload = {
        _token: csrfToken(),
        prompt: prompt,
      };

      // add context + extra (functions supported)
      Object.assign(payload, collect(cfg.context || {}));
      Object.assign(payload, collect(cfg.extra || {}));

      // pass selected field/lang
      if (cfg.hiddenField) payload.field = $(cfg.hiddenField).val();
      if (cfg.hiddenLang) payload.lang = $(cfg.hiddenLang).val();

      const $btn = $(this);
      const html = $btn.html();
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> ' + imageGenerating + '...');

      $.post(cfg.endpoint, payload)
        .done(res => {
          if (res && res.status && res.names) {
            fill(cfg.outputs, res.names, cfg.inputSuffix);
            // if (cfg.prompt && cfg.prompt.from) {
            //   $(cfg.prompt.from).val('').trigger('input');
            // }
            $(cfg.modalId).modal('hide');
          } else if (res && res.message) {
            toast(res.warning ? 'warning' : 'error', res.message);
          } else {
            toast('error', 'AI request failed. Please try again.');
          }
        })
        .fail(err => {
          console.error('AiFormGenerator error:', err);
          const msg = (err && err.responseJSON && err.responseJSON.message)
            ? err.responseJSON.message
            : 'AI request failed. Please try again.';
          toast('error', msg);
        })
        .always(() => {
          $btn.prop('disabled', false).html(html);
        });
    });
  }

  showPendingToast();

  return { init };
})(jQuery);
