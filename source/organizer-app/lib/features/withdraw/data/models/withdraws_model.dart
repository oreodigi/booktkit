import 'dart:convert';

import 'package:booktkit_organizer/utils/number_formatter.dart';

class WithdrawResponse {
  final bool success;
  final String balance;
  final List<WithdrawData> withdraws;
  final CurrencyInfo currencyInfo;

  WithdrawResponse({
    required this.success,
    required this.balance,
    required this.withdraws,
    required this.currencyInfo,
  });

  factory WithdrawResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>;
    return WithdrawResponse(
      success: json['success'] ?? false,
      balance: data['balance']?.toString() ?? '0',
      withdraws: (data['withdraws'] as List<dynamic>? ?? [])
          .map((e) => WithdrawData.fromJson(e))
          .toList(),
      currencyInfo: CurrencyInfo.fromJson(
        data['getCurrencyInfo'] as Map<String, dynamic>? ?? {},
      ),
    );
  }
}

class CurrencyInfo {
  final String symbol;
  final String symbolPosition; // 'left' or 'right'
  final String text;
  final String textPosition;
  final String rate;

  CurrencyInfo({
    required this.symbol,
    required this.symbolPosition,
    required this.text,
    required this.textPosition,
    required this.rate,
  });

  factory CurrencyInfo.fromJson(Map<String, dynamic> json) {
    return CurrencyInfo(
      symbol: json['base_currency_symbol']?.toString() ?? '',
      symbolPosition:
          json['base_currency_symbol_position']?.toString() ?? 'left',
      text: json['base_currency_text']?.toString() ?? '',
      textPosition: json['base_currency_text_position']?.toString() ?? 'right',
      rate: json['base_currency_rate']?.toString() ?? '1.00',
    );
  }

  /// Format a numeric [value] string with the currency symbol
  String format(dynamic value) {
    final n = double.tryParse(value?.toString() ?? '') ?? 0.0;
    final formatted = n.toStringAsFixed(2);
    return symbolPosition == 'right'
        ? '$formatted$symbol'
        : '$symbol$formatted';
  }

  /// Format a numeric [value] string in compact notation for values >= 1,000,000.
  /// e.g. 12900000 → "\$12.9M"
  String formatCompact(dynamic value) {
    final n = NumberFormatter.parseNum(value);
    final compactNum = NumberFormatter.compact(n);
    return symbolPosition == 'right'
        ? '$compactNum$symbol'
        : '$symbol$compactNum';
  }
}

class WithdrawData {
  final int id;
  final String organizerId;
  final String withdrawId;
  final String methodId;
  final String amount;
  final String payableAmount;
  final String totalCharge;
  final String? additionalReference;
  final Map<String, dynamic>? fields;
  final String status;
  final DateTime createdAt;
  final DateTime updatedAt;
  final Method method;

  WithdrawData({
    required this.id,
    required this.organizerId,
    required this.withdrawId,
    required this.methodId,
    required this.amount,
    required this.payableAmount,
    required this.totalCharge,
    this.additionalReference,
    this.fields,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
    required this.method,
  });

  factory WithdrawData.fromJson(Map<String, dynamic> json) {
    return WithdrawData(
      id: json['id'],
      organizerId: json['organizer_id'],
      withdrawId: json['withdraw_id'],
      methodId: json['method_id'],
      amount: json['amount'],
      payableAmount: json['payable_amount'],
      totalCharge: json['total_charge'],
      additionalReference: json['additional_reference'],
      fields: json['feilds'] != null ? jsonDecode(json['feilds']) : null,
      status: json['status'],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
      method: Method.fromJson(json['method']),
    );
  }
}

class Method {
  final int id;
  final String fixedCharge;
  final String percentageCharge;
  final String minLimit;
  final String maxLimit;
  final String name;
  final String status;
  final DateTime createdAt;
  final DateTime updatedAt;

  Method({
    required this.id,
    required this.fixedCharge,
    required this.percentageCharge,
    required this.minLimit,
    required this.maxLimit,
    required this.name,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Method.fromJson(Map<String, dynamic> json) {
    return Method(
      id: json['id'],
      fixedCharge: json['fixed_charge'],
      percentageCharge: json['percentage_charge'],
      minLimit: json['min_limit'],
      maxLimit: json['max_limit'],
      name: json['name'],
      status: json['status'],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }
}
