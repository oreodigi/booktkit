import 'package:booktkit_organizer/app/urls.dart';
import 'package:booktkit_organizer/features/transactions/data/models/transactions_model.dart';
import 'package:booktkit_organizer/services/api_client.dart';

/// Transactions Service - handles transactions API calls
class TransactionsService {
  final ApiClient _apiClient = ApiClient();

  /// Get transactions list with optional transaction_id filter
  Future<TransactionsResponse> getTransactions({
    String? transactionId,
    int page = 1,
  }) async {
    try {
      final queryParams = {
        'page': page.toString(),
        'per_page': '10',
        if (transactionId != null && transactionId.isNotEmpty)
          'transaction_id': transactionId,
      };

      final response = await _apiClient.get(
        Urls.orgTransaction,
        queryParameters: queryParams,
      );
      return TransactionsResponse.fromJson(response.data);
    } catch (e) {
      throw Exception('Failed to fetch transactions: $e');
    }
  }
}
