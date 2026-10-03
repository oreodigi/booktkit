
import 'package:booktkit_organizer/features/transactions/data/models/transactions_model.dart';
import 'package:booktkit_organizer/services/transactions_service.dart';
import 'package:booktkit_organizer/utils/app_logger.dart';
import 'package:flutter/foundation.dart';

/// Transactions Provider - manages transactions state
class TransactionsProvider with ChangeNotifier {
  final TransactionsService _service = TransactionsService();

  bool _isLoading = false;
  bool _isLoadingMore = false;
  String? _errorMessage;
  List<TransactionItem> _transactions = [];
  List<TransactionItem> _filteredTransactions = [];
  String _searchQuery = '';

  // Pagination state
  int _currentPage = 1;
  bool _hasMore = true;

  bool get isLoading => _isLoading;
  bool get isLoadingMore => _isLoadingMore;
  String? get errorMessage => _errorMessage;
  List<TransactionItem> get transactions => _filteredTransactions;
  String get searchQuery => _searchQuery;
  bool get hasMore => _hasMore;

  /// Fetch transactions from API
  Future<void> fetchTransactions({
    String? transactionId,
    bool refresh = false,
  }) async {
    if (refresh) {
      _currentPage = 1;
      _transactions = [];
      _hasMore = true;
    }

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _service.getTransactions(
        transactionId: transactionId,
        page: _currentPage,
      );

      if (refresh) {
        _transactions = response.data.transactions.data;
      } else {
        _transactions.addAll(response.data.transactions.data);
      }

      _filteredTransactions = _transactions;
      _hasMore = response.data.transactions.hasNextPage;

      if (kDebugMode) {
        AppLogger.info(
          '📄 Loaded page $_currentPage: ${response.data.transactions.data.length} transactions',
        );
        AppLogger.info('📊 Total transactions now: ${_transactions.length}');
        AppLogger.info('🔄 Has more pages: $_hasMore');
      }

      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Fetch next page of transactions
  Future<void> fetchNextPage({String? transactionId}) async {
    if (_isLoadingMore || !_hasMore) {
      if (kDebugMode) {
        AppLogger.w(
          '⚠️ Skipping fetchNextPage: isLoadingMore=$_isLoadingMore, hasMore=$_hasMore',
        );
      }
      return;
    }

    _isLoadingMore = true;
    notifyListeners();

    try {
      _currentPage++;
      if (kDebugMode) {
        AppLogger.info('⬇️ Fetching next page: $_currentPage');
      }

      final response = await _service.getTransactions(
        transactionId: transactionId,
        page: _currentPage,
      );

      _transactions.addAll(response.data.transactions.data);
      _filteredTransactions = _transactions;
      _hasMore = response.data.transactions.hasNextPage;

      if (kDebugMode) {
        AppLogger.info(
          '✅ Page $_currentPage loaded: ${response.data.transactions.data.length} new transactions',
        );
        AppLogger.info('📊 Total transactions now: ${_transactions.length}');
        AppLogger.info('🔄 Has more pages: $_hasMore');
      }

      _isLoadingMore = false;
      notifyListeners();
    } catch (e) {
      if (kDebugMode) {
        AppLogger.e('❌ Error loading page $_currentPage: $e');
      }
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _currentPage--;
      _isLoadingMore = false;
      notifyListeners();
    }
  }

  /// Search transactions by transaction ID
  void searchTransactions(String query) {
    _searchQuery = query;
    if (query.isEmpty) {
      _filteredTransactions = _transactions;
    } else {
      _filteredTransactions = _transactions
          .where(
            (transaction) => transaction.transcationId.toLowerCase().contains(
              query.toLowerCase(),
            ),
          )
          .toList();
    }
    notifyListeners();
  }

  /// Clear search
  void clearSearch() {
    _searchQuery = '';
    _filteredTransactions = _transactions;
    notifyListeners();
  }

  /// Refresh transactions
  Future<void> refreshTransactions() => fetchTransactions();

  /// Filter by transaction type
  void filterByType(String type) {
    if (type.isEmpty || type == 'all') {
      _filteredTransactions = _transactions;
    } else {
      _filteredTransactions = _transactions
          .where(
            (transaction) =>
                transaction.transcationType.toLowerCase() == type.toLowerCase(),
          )
          .toList();
    }
    notifyListeners();
  }

  /// Filter by payment status
  void filterByStatus(String status) {
    if (status.isEmpty || status == 'all') {
      _filteredTransactions = _transactions;
    } else {
      _filteredTransactions = _transactions
          .where(
            (transaction) =>
                transaction.paymentStatus.toLowerCase() == status.toLowerCase(),
          )
          .toList();
    }
    notifyListeners();
  }
}
