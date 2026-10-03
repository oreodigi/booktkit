import 'dart:convert';

import 'package:booktkit_customer/app/urls.dart';
import 'package:booktkit_customer/features/events/data/models/event_details_models.dart';
import 'package:booktkit_customer/network_services/core/http_headers.dart';
import 'package:booktkit_customer/utils/net_utils.dart';

class EventDetailsService {
  static Future<EventDetailsPageModel> fetchDetails({
    required int eventId,
    String? languageCode,
  }) async {
    final uri = Uri.parse(AppUrls.eventDetails(eventId));
    final headers = HttpHeadersHelper.base();
    if (languageCode != null) headers['Accept-Language'] = languageCode;
    final response = await NetUtils.getWithRetry(
      uri,
      headers: headers,
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to load event details: ${response.statusCode}');
    }
    final decoded = json.decode(response.body);
    
    if (decoded is! Map<String, dynamic>) {
      throw Exception('Invalid response format');
    }
    return EventDetailsPageModel.fromJson(decoded);
  }
}
