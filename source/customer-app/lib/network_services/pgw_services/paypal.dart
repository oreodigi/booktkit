import 'dart:convert';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;

import 'package:booktkit_customer/app/app_constants.dart';
import 'package:booktkit_customer/app/app_routes.dart';
import 'package:booktkit_customer/network_services/core/http_headers.dart';

class PayPalGateway {
  static Future<bool> startCheckout({
    required int amountMinor,
    required String currency,
    required String name,
    required String email,
    String description = 'Order',
  }) async {
    // Logging removed.
    final create = await http.post(
      Uri.parse('$pgwBaseUrl/paypal-create-order.php'),
      headers: {
        ...HttpHeadersHelper.base(),
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'amount_minor': amountMinor,
        'currency': currency.toUpperCase(),
        'name': name,
        'email': email,
        'description': description,
      }),
    );
  // Logging removed.
    if (create.statusCode >= 300) {
      throw Exception('PayPal create failed: ${create.body}');
    }

    final j = jsonDecode(create.body) as Map<String, dynamic>;
    final approveUrl = j['redirect_url'] as String?;
    final orderId = j['order_id'] as String?;
    if (approveUrl == null || orderId == null) {
      throw Exception('Missing approve link/order_id');
    }

  // Logging removed.
    final finished =
        await Get.toNamed(
              AppRoutes.checkoutWebView,
              arguments: {
                'url': approveUrl,
                'finishScheme': 'myapp://paypal-finish',
                'title': 'PayPal',
              },
            )
            as bool?;

    if (finished != true) return false; // user cancelled

    // Logging removed.
    final cap = await http.post(
      Uri.parse('$pgwBaseUrl/paypal-capture-order.php'),
      headers: {
        ...HttpHeadersHelper.base(),
        'Content-Type': 'application/json',
      },
      body: jsonEncode({'order_id': orderId}),
    );
  // Logging removed.
    if (cap.statusCode >= 300) {
      throw Exception('Capture failed: ${cap.body}');
    }
    final c = jsonDecode(cap.body) as Map<String, dynamic>;
    final status = (c['status'] ?? 'UNKNOWN').toString().toUpperCase();
    final ok = status == 'COMPLETED';
  // Logging removed.
    return ok;
  }
}
