import 'package:booktkit_organizer/app/urls.dart';
import 'package:booktkit_organizer/features/withdraw/data/models/withdraws_model.dart';
import 'package:booktkit_organizer/services/api_client.dart';
import 'package:booktkit_organizer/utils/app_logger.dart';
import 'package:flutter/material.dart';

/// Global provider for base currency data fetched from [Urls.getCurrency].
/// Call [fetchIfNeeded] once after login (e.g. in AppHome.initState).
class CurrencyProvider extends ChangeNotifier {
  CurrencyInfo? _currencyInfo;
  bool _isLoading = false;
  String? _error;

  CurrencyInfo? get currencyInfo => _currencyInfo;
  bool get isLoading => _isLoading;
  String? get error => _error;

  /// Formats [value] using the loaded currency.
  /// Falls back to plain number string when currency is not yet loaded.
  String format(dynamic value) {
    if (_currencyInfo != null) return _currencyInfo!.format(value);
    final n = double.tryParse(value?.toString() ?? '') ?? 0.0;
    return n.toStringAsFixed(2);
  }

  /// Returns the currency symbol (e.g. "৳"). Empty string until loaded.
  String get symbol => _currencyInfo?.symbol ?? '';

  /// Returns the currency code/text (e.g. "BDT"). Empty string until loaded.
  String get text => _currencyInfo?.text ?? '';

  /// Fetches currency only if not already loaded. Safe to call multiple times.
  Future<void> fetchIfNeeded() async {
    if (_currencyInfo != null || _isLoading) return;
    await fetchCurrency();
  }

  Future<void> fetchCurrency() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await ApiClient().get(Urls.getCurrency);
      final data = response.data;
      if (data is! Map<String, dynamic>) {
        throw Exception(
          'Unexpected response format from currency API. '
          'Expected JSON but got: ${data.runtimeType}',
        );
      }
      final currencyJson =
          (data['data']?['currencyInfo'] as Map<String, dynamic>?) ?? {};
      _currencyInfo = CurrencyInfo.fromJson(currencyJson);
      AppLogger.info('CurrencyProvider: loaded ${_currencyInfo?.text}');
    } catch (e) {
      _error = e.toString().replaceFirst('Exception: ', '');
      AppLogger.e('CurrencyProvider error: $_error');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}
