import 'package:dio/dio.dart';
import 'package:booktkit_organizer/app/urls.dart';
import 'package:booktkit_organizer/features/income/data/models/income_model.dart';
import 'package:booktkit_organizer/services/api_client.dart';

/// Income Service - handles transactions API calls
class IncomeService {
  final ApiClient _apiClient = ApiClient();

  /// Get Income list with optional year filter
  Future<IncomeModel> getTransactions({String? year}) async {
    try {
      final queryParams = <String, dynamic>{};

      if (year != null && year.isNotEmpty) {
        queryParams['year'] = year;
      }

      final Response response = await _apiClient.get(
        Urls.orgIncome,
        queryParameters: queryParams,
      );

      // Validate HTTP status
      if (response.statusCode != 200) {
        throw Exception('Server error: ${response.statusCode}');
      }

      // Parse model
      final IncomeModel incomeModel = IncomeModel.fromJson(response.data);

      // Validate API success flag
      if (incomeModel.success != true) {
        throw Exception('API returned success=false');
      }

      return incomeModel;
    } on DioException catch (e) {
      throw Exception(e.response?.data?['message'] ?? 'Network error occurred');
    } catch (e) {
      throw Exception('Failed to fetch transactions: $e');
    }
  }
}
