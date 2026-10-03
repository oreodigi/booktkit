"use strict";

(function ($) {
  const config = window.organizerAiTokenCheckout || {};
  const gatewaySelect = $('#organizerAiGateway');
  const stripeWrapper = $('#organizerStripeElementWrapper');
  const iyzicoField = $('.organizer-iyzico-field');
  const offlineSections = $('.organizer-offline-gateway');
  const form = document.getElementById('aiTokenPaymentForm');
  const stripeKey = config.stripeKey || null;
  let stripe = null;
  let cardElement = null;

  function toggleGatewayFields(value) {
    const isOffline = !isNaN(parseInt(value, 10));

    offlineSections.addClass('d-none');
    stripeWrapper.addClass('d-none');
    iyzicoField.addClass('d-none');

    if (!value) {
      return;
    }

    if (isOffline) {
      $('#organizer-offline-gateway-' + value).removeClass('d-none');
      return;
    }

    if (value === 'stripe') {
      stripeWrapper.removeClass('d-none');
    }

    if (value === 'iyzico') {
      iyzicoField.removeClass('d-none');
    }
  }

  function setupStripe() {
    if (!stripeKey || typeof Stripe === 'undefined') {
      return;
    }

    stripe = Stripe(stripeKey);

    if (!document.getElementById('organizerStripeElement')) {
      return;
    }

    const elements = stripe.elements();
    cardElement = elements.create('card', {
      style: {
        base: {
          color: '#454545',
          fontWeight: '500',
          lineHeight: '30px',
          '::placeholder': {
            color: '#777'
          }
        }
      }
    });

    cardElement.mount('#organizerStripeElement');
  }

  if (gatewaySelect.length) {
    toggleGatewayFields(config.selectedGateway || gatewaySelect.val());

    gatewaySelect.on('change', function () {
      toggleGatewayFields($(this).val());
    });
  }

  setupStripe();

  if (form) {
    form.addEventListener('submit', function (event) {
      const selectedGateway = gatewaySelect.val();

      if (selectedGateway !== 'stripe' || !stripe || !cardElement) {
        return;
      }

      event.preventDefault();

      stripe.createToken(cardElement).then(function (result) {
        if (result.error) {
          $('#organizerStripeErrors').text(result.error.message);
          return;
        }

        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'stripeToken';
        hiddenInput.value = result.token.id;
        form.appendChild(hiddenInput);
        form.submit();
      });
    });
  }
})(jQuery);
