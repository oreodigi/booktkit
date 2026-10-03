import 'package:booktkit_organizer/services/income_service.dart';
import 'package:flutter/material.dart';
import 'package:booktkit_organizer/features/income/data/models/income_model.dart';

class IncomeProvider extends ChangeNotifier {
  final IncomeService _incomeService = IncomeService();

  IncomeModel? _incomeModel;
  bool _isLoading = false;
  String? _errorMessage;
  String? _selectedYear;

  IncomeModel? get incomeModel => _incomeModel;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  String? get selectedYear => _selectedYear;

  /// Fetch income data
  Future<void> fetchIncome({String? year}) async {
    try {
      _isLoading = true;
      _errorMessage = null;
      _selectedYear = year;
      notifyListeners();

      final result = await _incomeService.getTransactions(year: year);

      _incomeModel = result;
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Optional: Clear data
  void clear() {
    _incomeModel = null;
    _errorMessage = null;
    notifyListeners();
  }
}
