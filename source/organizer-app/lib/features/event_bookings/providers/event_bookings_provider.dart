import 'package:booktkit_organizer/features/event_bookings/data/models/event_booking_model.dart';
import 'package:booktkit_organizer/services/event_booking_service.dart';
import 'package:booktkit_organizer/utils/app_logger.dart';
import 'package:flutter/foundation.dart';

class EventBookingsProvider with ChangeNotifier {
  final EventBookingService _bookingService = EventBookingService();

  bool _isLoading = false;
  bool _isLoadingMore = false;
  String? _errorMessage;
  List<EventBooking> _bookings = [];

  // Pagination state
  int _currentPage = 1;
  bool _hasMore = true;

  // Filter state
  String? _statusFilter;
  String? _searchQuery;

  bool get isLoading => _isLoading;
  bool get isLoadingMore => _isLoadingMore;
  String? get errorMessage => _errorMessage;
  List<EventBooking> get bookings => _bookings;
  bool get hasMore => _hasMore;
  String? get statusFilter => _statusFilter;

  Future<void> fetchBookings({
    String? status,
    String? searchQuery,
    bool refresh = false,
  }) async {
    if (refresh) {
      _currentPage = 1;
      _bookings = [];
      _hasMore = true;
    }

    if (status != null) _statusFilter = status;
    if (searchQuery != null) _searchQuery = searchQuery;

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      // Basic check: if 'searchQuery' starts with # or BK, it might be a booking ID.
      // E.g '6989a...' could be bookingId. 
      // For simplicity let's pass it to bookingId and eventTitle based on heuristics, 
      // or we can pass it to eventTitle entirely if the backend supports fuzzy search.
      // Wait, the API supports both. We'll pass as bookingId if it looks like one (hex/digits without space), else eventTitle.
      String? bId;
      String? eTitle;

      if (_searchQuery != null && _searchQuery!.isNotEmpty) {
        if (!_searchQuery!.contains(' ') && _searchQuery!.length > 6) {
             bId = _searchQuery; 
        } else {
             eTitle = _searchQuery;
        }
      }

      final response = await _bookingService.getBookings(
        status: _statusFilter,
        bookingId: bId,
        eventTitle: eTitle,
        page: _currentPage,
      );

      if (refresh) {
        _bookings = response.data.bookings;
      } else {
        _bookings.addAll(response.data.bookings);
      }

      _hasMore = response.data.hasNextPage;

      if (kDebugMode) {
        AppLogger.info(
          '📄 Loaded page $_currentPage: ${response.data.bookings.length} bookings',
        );
      }

      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchNextPage() async {
    if (_isLoadingMore || !_hasMore) return;

    _isLoadingMore = true;
    notifyListeners();

    try {
      _currentPage++;

      String? bId;
      String? eTitle;

      if (_searchQuery != null && _searchQuery!.isNotEmpty) {
        if (!_searchQuery!.contains(' ') && _searchQuery!.length > 6) {
             bId = _searchQuery; 
        } else {
             eTitle = _searchQuery;
        }
      }

      final response = await _bookingService.getBookings(
        status: _statusFilter,
        bookingId: bId,
        eventTitle: eTitle,
        page: _currentPage,
      );

      _bookings.addAll(response.data.bookings);
      _hasMore = response.data.hasNextPage;

      _isLoadingMore = false;
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _currentPage--;
      _isLoadingMore = false;
      notifyListeners();
    }
  }

  void setStatusFilter(String? status) {
    if (_statusFilter == status) return;
    _statusFilter = status;
    fetchBookings(refresh: true);
  }

  void setSearchQuery(String query) {
    if (_searchQuery == query) return;
    _searchQuery = query;
    fetchBookings(refresh: true);
  }

  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }

  Future<bool> deleteBooking(int bookingId) async {
    try {
      await _bookingService.deleteBooking(bookingId);
      _bookings.removeWhere((b) => b.id == bookingId);
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }
}
