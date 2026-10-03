import 'dart:convert';
import 'package:booktkit_customer/app/urls.dart';
import 'package:booktkit_customer/features/bookings/data/models/booking_models.dart';
import 'package:http/http.dart' as http;
import 'package:booktkit_customer/utils/net_utils.dart';
import 'package:booktkit_customer/features/account/data/models/dashboard_models.dart';
import 'package:booktkit_customer/network_services/core/http_errors.dart';


class DashboardService {
  static Future<DashboardResponseModel> fetch(String token) async {
    http.Response response;
    try {
      response = await NetUtils.getWithRetry(
        Uri.parse(AppUrls.dashboard),
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
      throw Exception(
        'Server responded ${response.statusCode}: ${response.body}',
      );
    }
    dynamic decoded;
    try {
      decoded = json.decode(response.body);
    } catch (e) {
      throw Exception('Invalid JSON: $e');
    }
    try {
    } catch (_) {}

    Map<String, dynamic> container = const {};
    if (decoded is Map<String, dynamic>) {
      final d = decoded['data'];
      if (d is Map<String, dynamic>) {
        container = d;
      } else {
        container = decoded;
      }
    }

    final String pageTitle =
        container['page_title']?.toString() ??
        container['pageTitle']?.toString() ??
        'Dashboard';

    Map<String, dynamic>? authUserJson;
    if (container['auth_user'] is Map<String, dynamic>) {
      authUserJson = container['auth_user'] as Map<String, dynamic>;
    } else if (container['authUser'] is Map<String, dynamic>) {
      authUserJson = container['authUser'] as Map<String, dynamic>;
    }

    List<dynamic> bookingsJson = const [];
    final rawBookings = container['bookings'];
    if (rawBookings is List) bookingsJson = rawBookings;

    final bookings = bookingsJson
        .whereType<Map<String, dynamic>>()
        .map(BookingItemModel.fromJson)
        .toList();

    return DashboardResponseModel(
      pageTitle: pageTitle,
      authUser: authUserJson == null
          ? null
          : AuthUserModel.fromJson(authUserJson),
      bookings: bookings,
    );
  }
}
