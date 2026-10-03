import 'dart:convert';
import 'package:booktkit_customer/app/urls.dart';
import 'package:booktkit_customer/features/events/data/models/seat_map_models.dart';
import 'package:booktkit_customer/network_services/core/http_headers.dart';
import 'package:http/http.dart' as http;

class SeatMapService {
  static Future<SeatMapResponse> fetch({
    required int eventId,
    required int ticketId,
    required int slotUniqueId,
  }) async {
    final uri = Uri.parse(
      AppUrls.seatDetails(
        eventId: eventId,
        ticketId: ticketId,
        slotUniqueId: slotUniqueId,
      ),
    );
    final res = await http.get(uri, headers: HttpHeadersHelper.base());
    if (res.statusCode != 200) {
      throw Exception('Failed to load seat plan: ${res.statusCode}');
    }
    final decoded = json.decode(res.body);
    if (decoded is! Map<String, dynamic>) {
      throw Exception('Invalid seat plan response');
    }
    return SeatMapResponse.fromJson(decoded);
  }
}

