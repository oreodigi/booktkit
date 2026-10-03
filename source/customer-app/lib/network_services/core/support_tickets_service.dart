import 'dart:convert';
import 'package:booktkit_customer/app/urls.dart';
import 'package:booktkit_customer/features/support/data/models/support_ticket_models.dart';
import 'package:booktkit_customer/network_services/core/http_errors.dart';
import 'package:http/http.dart' as http;
import 'package:booktkit_customer/utils/net_utils.dart';

class SupportTicketsService {
  static Future<SupportTicketsResponse> fetch({required String token}) async {
    http.Response response;
    try {
      final uri = Uri.parse(AppUrls.supportTickets);
      response = await NetUtils.getWithRetry(
        uri,
        headers: {
          'Accept': 'application/json',
          if (token.isNotEmpty) 'Authorization': 'Bearer $token',
        },
      );
    } catch (e) {
      throw Exception('Network error: $e');
    }
    if (response.statusCode == 401 ||
        response.statusCode == 419 ||
        response.statusCode == 403) {
      throw const AuthRequiredException('Session expired. Please login again.');
    }
    if (response.statusCode != 200) {
      throw Exception('Server responded ${response.statusCode}');
    }
    final decoded = json.decode(response.body) as Map<String, dynamic>;
    return SupportTicketsResponse.fromJson(decoded);
  }
}
