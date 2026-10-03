import 'package:dio/dio.dart';
import 'package:booktkit_organizer/features/withdraw/data/models/method_input_model.dart';
import 'package:booktkit_organizer/features/withdraw/data/models/withdraw_calculation_model.dart';
import 'package:booktkit_organizer/features/withdraw/data/models/withdraws_model.dart';
import 'package:booktkit_organizer/services/api_client.dart';
import 'package:booktkit_organizer/services/withdraw_service.dart';
import 'package:flutter/material.dart';

class WithdrawProvider extends ChangeNotifier {
  final WithdrawService _service = WithdrawService(ApiClient().dio);

  // ── Withdrawal history ──────────────────────────────────────────────────
  List<WithdrawData> _withdrawals = [];
  bool _isLoading = false;
  String? _errorMessage;
  String _balance = '0';
  CurrencyInfo? _currencyInfo;

  List<WithdrawData> get withdrawals => _withdrawals;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  String get balance => _balance;
  CurrencyInfo? get currencyInfo => _currencyInfo;

  Future<void> fetchWithdrawals() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _service.getWithdrawHistory();
      _withdrawals = response.withdraws;
      _balance = response.balance;
      _currencyInfo = response.currencyInfo;
    } catch (e) {
      _errorMessage = e.toString().replaceFirst('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // ── Withdraw methods (GET /organizer/withdraw/create) ───────────────────
  List<Method> _methods = [];
  bool _isLoadingMethods = false;
  String? _methodsError;

  List<Method> get methods => _methods;
  bool get isLoadingMethods => _isLoadingMethods;
  String? get methodsError => _methodsError;

  Future<void> fetchWithdrawMethods() async {
    if (_methods.isNotEmpty) return; // already loaded
    _isLoadingMethods = true;
    _methodsError = null;
    notifyListeners();

    try {
      _methods = await _service.getWithdrawMethods();
    } catch (e) {
      _methodsError = e.toString().replaceFirst('Exception: ', '');
    } finally {
      _isLoadingMethods = false;
      notifyListeners();
    }
  }

  // ── Method input fields (GET /organizer/withdraw/get-method-input/{id}) ──
  List<MethodInputField> _methodInputs = [];
  bool _isLoadingInputs = false;
  String? _inputsError;
  int? _loadedMethodId;

  List<MethodInputField> get methodInputs => _methodInputs;
  bool get isLoadingInputs => _isLoadingInputs;
  String? get inputsError => _inputsError;

  Future<void> fetchMethodInputs(int methodId) async {
    if (_loadedMethodId == methodId && _methodInputs.isNotEmpty) return;
    _isLoadingInputs = true;
    _inputsError = null;
    _methodInputs = [];
    _loadedMethodId = methodId;
    notifyListeners();

    try {
      _methodInputs = await _service.getMethodInputs(methodId);
    } catch (e) {
      _inputsError = e.toString().replaceFirst('Exception: ', '');
    } finally {
      _isLoadingInputs = false;
      notifyListeners();
    }
  }

  void clearMethodInputs() {
    _methodInputs = [];
    _inputsError = null;
    _loadedMethodId = null;
    notifyListeners();
  }

  // ── Withdrawal Calculation ────────────────────────────────────────────────
  WithdrawCalculationModel? _calculationResult;
  bool _isCalculating = false;
  String? _calculationError;

  WithdrawCalculationModel? get calculationResult => _calculationResult;
  bool get isCalculating => _isCalculating;
  String? get calculationError => _calculationError;

  void clearCalculation() {
    _calculationResult = null;
    _calculationError = null;
    notifyListeners();
  }

  Future<void> calculateWithdrawal(int methodId, double amount) async {
    _isCalculating = true;
    _calculationError = null;
    notifyListeners();

    try {
      _calculationResult = await _service.calculateWithdrawal(methodId, amount);
    } catch (e) {
      _calculationResult = null;
      _calculationError = e.toString().replaceFirst('Exception: ', '');
    } finally {
      _isCalculating = false;
      notifyListeners();
    }
  }

  // ── Delete Withdrawal ─────────────────────────────────────────────────────
  bool _isDeleting = false;
  String? _deleteError;

  bool get isDeleting => _isDeleting;
  String? get deleteError => _deleteError;

  Future<bool> deleteWithdrawal(int id) async {
    _isDeleting = true;
    _deleteError = null;
    notifyListeners();

    try {
      await _service.deleteWithdrawHistory(id);
      _withdrawals.removeWhere((w) => w.id == id);
      _isDeleting = false;
      notifyListeners();
      return true;
    } catch (e) {
      _deleteError = e.toString().replaceFirst('Exception: ', '');
      _isDeleting = false;
      notifyListeners();
      return false;
    }
  }

  // ── Submit Withdrawal Request ─────────────────────────────────────────────
  bool _isSubmitting = false;
  String? _submitError;

  bool get isSubmitting => _isSubmitting;
  String? get submitError => _submitError;

  Future<bool> submitWithdrawRequest(FormData data) async {
    _isSubmitting = true;
    _submitError = null;
    notifyListeners();

    try {
      await _service.submitWithdrawRequest(data);
      _isSubmitting = false;
      notifyListeners();
      return true;
    } catch (e) {
      _submitError = e.toString().replaceFirst('Exception: ', '');
      _isSubmitting = false;
      notifyListeners();
      return false;
    }
  }
}
