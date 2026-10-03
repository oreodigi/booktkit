import 'package:booktkit_organizer/app/urls.dart';
import 'package:booktkit_organizer/features/event_bookings/data/models/event_booking_model.dart';
import 'package:booktkit_organizer/services/api_client.dart';

class EventBookingService {
  final ApiClient _apiClient = ApiClient();

  Future<EventBookingResponse> getBookings({
    String? status,
    String? eventTitle,
    String? bookingId,
    int page = 1,
  }) async {
    try {
      final queryParams = {
        'page': page.toString(),
        if (status != null &&
            status.isNotEmpty &&
            status.toLowerCase() != 'all')
          'status': status.toLowerCase(),
        if (eventTitle != null && eventTitle.isNotEmpty)
          'event_title': eventTitle,
        if (bookingId != null && bookingId.isNotEmpty) 'booking_id': bookingId,
      };

      final response = await _apiClient.get(
        Urls.getBookings,
        queryParameters: queryParams,
      );

      return EventBookingResponse.fromJson(response.data);
    } catch (e) {
      throw Exception('Failed to fetch event bookings: $e');
    }
  }

  Future<bool> deleteBooking(int bookingId) async {
    try {
      final response = await _apiClient.post(
        '${Urls.deleteBooking}/$bookingId',
      );
      final data = response.data;
      if (data is Map<String, dynamic> && data['success'] == true) {
        return true;
      }
      final msg = data is Map ? data['message'] : 'Failed to delete booking';
      throw Exception(msg);
    } catch (e) {
      throw Exception('Failed to delete booking: $e');
    }
  }
}
