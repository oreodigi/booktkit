@php
  $recaptchaV3SiteKey = $basicInfo->google_recaptcha_site_key ?? \App\Models\BasicSettings\Basic::query()->value('google_recaptcha_site_key');
@endphp

@if (($basicInfo->google_recaptcha_status ?? 0) == 1 && !empty($recaptchaV3SiteKey))
  <input type="hidden" name="g-recaptcha-response" value="">
  <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaV3SiteKey }}"></script>
  <script>
    (function () {
      const form = document.getElementById(@json($formId));
      if (!form || form.dataset.recaptchaV3Bound === '1') return;
      form.dataset.recaptchaV3Bound = '1';

      form.addEventListener('submit', function (event) {
        if (form.dataset.recaptchaV3Verified === '1') {
          form.dataset.recaptchaV3Verified = '0';
          return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        grecaptcha.ready(function () {
          grecaptcha.execute(@json($recaptchaV3SiteKey), { action: @json($action) })
            .then(function (token) {
              form.querySelector('input[name="g-recaptcha-response"]').value = token;
              form.dataset.recaptchaV3Verified = '1';
              if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
              } else {
                form.submit();
              }
            });
        });
      }, true);
    })();
  </script>
  @error('g-recaptcha-response')
    <p class="text-danger">{{ $message }}</p>
  @enderror
@endif
