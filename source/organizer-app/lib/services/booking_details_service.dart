import 'package:dio/dio.dart';
import 'package:booktkit_organizer/app/urls.dart';
import 'package:booktkit_organizer/features/event_bookings/data/models/booking_details_model.dart';
import 'package:booktkit_organizer/services/api_client.dart';

class BookingDetailsService {
  final ApiClient _apiClient;
  BookingDetailsService(this._apiClient);

  Future<BookingDetailsResponse> getDetails(String bookingId) async {
    try {
      final response = await _apiClient.get(
        '${Urls.getBookingDetails}/$bookingId',
      );
      return BookingDetailsResponse.fromJson(
          response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      throw Exception(
          e.response?.data?['message'] ?? 'Failed to load booking details');
    } catch (e) {
      throw Exception('Failed to load booking details: $e');
    }
  }
}
