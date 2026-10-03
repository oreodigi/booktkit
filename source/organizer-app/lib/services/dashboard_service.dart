import 'package:dio/dio.dart';
import 'package:booktkit_organizer/app/urls.dart';
import 'package:booktkit_organizer/features/dashboard/data/models/dashboard_response.dart';
import 'package:booktkit_organizer/services/api_client.dart';
import 'package:booktkit_organizer/utils/app_logger.dart';

class DashboardService {
  final ApiClient _apiClient = ApiClient();

  Future<DashboardResponse> getDashboardData() async {
    try {
      AppLogger.info('Calling dashboard API: ${Urls.orgDashboard}');
      final response = await _apiClient.get(Urls.orgDashboard);

      AppLogger.info('Dashboard API response status: ${response.statusCode}');
      AppLogger.info(
        'Dashboard API response data type: ${response.data.runtimeType}',
      );
      AppLogger.info('Dashboard API response data: ${response.data}');

      if (response.statusCode == 200 || response.statusCode == 201) {
        if (response.data is! Map<String, dynamic>) {
          throw Exception(
            'Unexpected response format. Expected JSON but got: '
            '${response.data.runtimeType}',
          );
        }
        return DashboardResponse.fromJson(
          response.data as Map<String, dynamic>,
        );
      } else {
        throw Exception('Failed to load dashboard: ${response.statusMessage}');
      }
    } on DioException catch (e) {
      AppLogger.e('DioException in getDashboardData: $e');
      if (e.response != null) {
        // Handle both Map and String response data
        String errorMessage = 'Failed to load dashboard';
        final responseData = e.response?.data;

        if (responseData is Map) {
          errorMessage = responseData['message']?.toString() ?? errorMessage;
        } else if (responseData is String) {
          errorMessage = responseData;
        }

        throw Exception(errorMessage);
      } else {
        throw Exception('Network error: ${e.message}');
      }
    } catch (e, stackTrace) {
      AppLogger.e('Exception in getDashboardData: $e');
      AppLogger.e('Stack trace: $stackTrace');
      throw Exception('Unexpected error: $e');
    }
  }
}
