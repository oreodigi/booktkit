<?php

// Production gateway settings live outside the public directory and Git.
$privateConfig = dirname(__DIR__, 2) . '/booktkit-shared/payment-config.php';
if (is_file($privateConfig)) {
  return require $privateConfig;
}

return array (
  'PUBLIC_API_BASE' => (getenv('PUBLIC_API_BASE') ?: 'https://booktkit.com/pgw'),
  'AUTHORIZE_LOGIN_ID' => (getenv('AUTHORIZE_LOGIN_ID') ?: ''),
  'AUTHORIZE_TRANSACTION_KEY' => (getenv('AUTHORIZE_TRANSACTION_KEY') ?: ''),
  'AUTHORIZE_ENV' => 'sandbox',
  'STRIPE_SECRET_KEY' => (getenv('STRIPE_SECRET_KEY') ?: ''),
  'MIDTRANS_SERVER_KEY' => (getenv('MIDTRANS_SERVER_KEY') ?: ''),
  'MIDTRANS_BASE' => 'https://app.sandbox.midtrans.com/snap/v1/transactions',
  'MOLLIE_API_KEY' => (getenv('MOLLIE_API_KEY') ?: ''),
  'MYFATOORAH_API_KEY' => (getenv('MYFATOORAH_API_KEY') ?: ''),
  'MYFATOORAH_BASE' => 'https://apitest.myfatoorah.com',
  'XENDIT_SECRET_KEY' => (getenv('XENDIT_SECRET_KEY') ?: ''),
  'XENDIT_BASE' => 'https://api.xendit.co',
  'FLW_SECRET_KEY' => (getenv('FLW_SECRET_KEY') ?: ''),
  'FLW_BASE' => 'https://api.flutterwave.com',
  'PAYPAL_CLIENT_ID' => (getenv('PAYPAL_CLIENT_ID') ?: ''),
  'PAYPAL_SECRET' => (getenv('PAYPAL_SECRET') ?: ''),
  'PAYPAL_BASE' => 'https://api-m.sandbox.paypal.com',
  'PHONEPE_BASE' => 'https://api-preprod.phonepe.com/apis/pg-sandbox',
  'PHONEPE_MERCHANT_ID' => (getenv('PHONEPE_MERCHANT_ID') ?: ''),
  'PHONEPE_SALT_KEY' => (getenv('PHONEPE_SALT_KEY') ?: ''),
  'PHONEPE_SALT_INDEX' => '1',
  'MP_ACCESS_TOKEN' => (getenv('MP_ACCESS_TOKEN') ?: ''),
  'MP_BASE' => 'https://api.mercadopago.com',
  'PAYSTACK_SECRET_KEY' => (getenv('PAYSTACK_SECRET_KEY') ?: ''),
  'PAYSTACK_BASE' => 'https://api.paystack.co',
  'TOYYIBPAY_BASE' => 'https://www.toyyibpay.com',
  'TOYYIBPAY_SECRET_KEY' => (getenv('TOYYIBPAY_SECRET_KEY') ?: ''),
  'TOYYIBPAY_CATEGORY_CODE' => (getenv('TOYYIBPAY_CATEGORY_CODE') ?: ''),
  'MONNIFY_BASE' => 'https://sandbox.monnify.com',
  'MONNIFY_API_KEY' => (getenv('MONNIFY_API_KEY') ?: ''),
  'MONNIFY_SECRET_KEY' => (getenv('MONNIFY_SECRET_KEY') ?: ''),
  'MONNIFY_CONTRACT_CODE' => (getenv('MONNIFY_CONTRACT_CODE') ?: ''),
  'NOWPAYMENTS_API_KEY' => (getenv('NOWPAYMENTS_API_KEY') ?: ''),
  'NOWPAYMENTS_BASE' => 'https://api.nowpayments.io/v1',
);
