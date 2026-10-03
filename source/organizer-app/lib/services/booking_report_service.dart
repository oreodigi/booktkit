import 'package:dio/dio.dart';
import 'package:booktkit_organizer/app/urls.dart';
import 'package:booktkit_organizer/features/event_bookings/data/models/booking_report_model.dart';
import 'package:booktkit_organizer/services/api_client.dart';

class BookingReportService {
  final ApiClient _apiClient;

  BookingReportService(this._apiClient);

  Future<BookingReportResponse> getReport({
    String? fromDate,
    String? toDate,
    String? paymentStatus,
    String? paymentMethod,
    int page = 1,
  }) async {
    try {
      final queryParams = <String, dynamic>{'page': page};
      if (fromDate != null && fromDate.isNotEmpty) {
        queryParams['from_date'] = fromDate;
      }
      if (toDate != null && toDate.isNotEmpty) {
        queryParams['to_date'] = toDate;
      }
      if (paymentStatus != null && paymentStatus.isNotEmpty) {
        queryParams['payment_status'] = paymentStatus;
      }
      if (paymentMethod != null && paymentMethod.isNotEmpty) {
        queryParams['payment_method'] = paymentMethod;
      }

      final response = await _apiClient.get(
        Urls.getBookingReport,
        queryParameters: queryParams,
      );

      return BookingReportResponse.fromJson(
          response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      if (e.response != null) {
        throw Exception(
            e.response?.data['message'] ?? 'Failed to load report');
      }
      throw Exception('Network error: ${e.message}');
    } catch (e) {
      throw Exception('Failed to load report: $e');
    }
  }
}
