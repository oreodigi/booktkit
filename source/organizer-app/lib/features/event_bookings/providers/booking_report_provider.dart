import 'package:booktkit_organizer/features/event_bookings/data/models/booking_report_model.dart';
import 'package:booktkit_organizer/services/api_client.dart';
import 'package:booktkit_organizer/services/booking_report_service.dart';
import 'package:flutter/material.dart';

class BookingReportProvider extends ChangeNotifier {
  final BookingReportService _service;

  BookingReportProvider()
      : _service = BookingReportService(ApiClient());

  // State
  List<BookingReportItem> _reports = [];
  List<PaymentMethodItem> _onPms = [];
  List<PaymentMethodItem> _offPms = [];

  bool _isLoading = false;
  bool _isLoadingMore = false;
  String? _errorMessage;

  int _currentPage = 1;
  int _lastPage = 1;

  // Filters
  String? _fromDate;
  String? _toDate;
  String? _statusFilter;
  String? _methodFilter;

  // Getters
  List<BookingReportItem> get reports => _reports;
  List<PaymentMethodItem> get onPms => _onPms;
  List<PaymentMethodItem> get offPms => _offPms;

  bool get isLoading => _isLoading;
  bool get isLoadingMore => _isLoadingMore;
  String? get errorMessage => _errorMessage;
  bool get hasMore => _currentPage < _lastPage;

  String? get fromDate => _fromDate;
  String? get toDate => _toDate;
  String? get statusFilter => _statusFilter;
  String? get methodFilter => _methodFilter;

  /// All payment method names (online + offline) for the dropdown
  List<String> get allPaymentMethods {
    final methods = <String>{};
    for (final pm in _onPms) {
      methods.add(pm.name);
    }
    for (final pm in _offPms) {
      methods.add(pm.name);
    }
    return methods.toList();
  }

  void setFromDate(String? date) {
    _fromDate = date;
    fetchReport(refresh: true);
  }

  void setToDate(String? date) {
    _toDate = date;
    fetchReport(refresh: true);
  }

  void setStatusFilter(String? status) {
    _statusFilter = status;
    fetchReport(refresh: true);
  }

  void setMethodFilter(String? method) {
    _methodFilter = method;
    fetchReport(refresh: true);
  }

  Future<void> fetchReport({bool refresh = false}) async {
    if (refresh) {
      _currentPage = 1;
      _reports = [];
      _errorMessage = null;
    }

    if (_isLoading || _isLoadingMore) return;

    if (_currentPage == 1) {
      _isLoading = true;
    } else {
      _isLoadingMore = true;
    }
    notifyListeners();

    try {
      final result = await _service.getReport(
        fromDate: _fromDate,
        toDate: _toDate,
        paymentStatus: _statusFilter,
        paymentMethod: _methodFilter,
        page: _currentPage,
      );

      if (refresh) {
        _reports = result.bookings;
        _onPms = result.onPms;
        _offPms = result.offPms;
      } else {
        _reports.addAll(result.bookings);
      }

      _lastPage = result.lastPage;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      _isLoadingMore = false;
      notifyListeners();
    }
  }

  Future<void> fetchNextPage() async {
    if (!hasMore || _isLoadingMore) return;
    _currentPage++;
    await fetchReport();
  }
}
