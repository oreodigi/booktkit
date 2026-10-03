<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>{{ __('AI Token Purchase via Midtrans') }}</title>
</head>

<body>
  <button class="btn btn-primary" id="pay-button" style="display: none">Pay Now</button>

  <script src="{{ asset('assets/admin/js/jquery.min.js') }}"></script>
  @if ($is_production == 0)
    <script src="https://app.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>
  @else
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>
  @endif

  <script>
    var baseUrl = "{{ route('index') }}";

    $(document).ready(function() {
      $('#pay-button').trigger('click');
    });

    document.querySelector('#pay-button').addEventListener('click', function(e) {
      e.preventDefault();

      snap.pay('{{ $snapToken }}', {
        onSuccess: function(result) {
          window.location.href = baseUrl + "/organizer/ai-token-purchase/midtrans/notify/" + result.order_id;
        },
        onPending: function(result) {
          window.location.href = "{{ route('organizer.ai_token_purchase.cancel') }}";
        },
        onError: function(result) {
          window.location.href = "{{ route('organizer.ai_token_purchase.cancel') }}";
        }
      });
    });
  </script>
</body>

</html>
