import 'package:booktkit_organizer/features/dashboard/data/models/dashboard_response.dart';
import 'package:booktkit_organizer/services/dashboard_service.dart';
import 'package:booktkit_organizer/utils/app_logger.dart';
import 'package:flutter/material.dart';

/// Dashboard Provider for managing dashboard state
class DashboardProvider with ChangeNotifier {
  final DashboardService _dashboardService = DashboardService();

  bool _isLoading = false;
  String? _errorMessage;
  DashboardData? _dashboardData;

  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  DashboardData? get dashboardData => _dashboardData;

  /// Fetch dashboard data
  Future<void> fetchDashboardData() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      AppLogger.info('Fetching dashboard data...');
      final response = await _dashboardService.getDashboardData();
      AppLogger.info('Dashboard response received successfully');
      _dashboardData = response.data;
      _isLoading = false;
      notifyListeners();
    } catch (e, stackTrace) {
      AppLogger.e('Error in fetchDashboardData: $e');
      AppLogger.e('Stack trace: $stackTrace');
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Clear error message
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }

  /// Refresh dashboard data
  Future<void> refreshDashboard() async {
    await fetchDashboardData();
  }
}
