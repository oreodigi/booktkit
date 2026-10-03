import '../../../../utils/app_logger.dart';
import '../../../../utils/number_formatter.dart';
import '../../../withdraw/data/models/withdraws_model.dart';

/// Dashboard Response Model
class DashboardResponse {
  final bool success;
  final DashboardData data;

  DashboardResponse({required this.success, required this.data});

  factory DashboardResponse.fromJson(Map<String, dynamic> json) {
    try {
      // Handle case where 'data' might be returned differently
      var dataJson = json['data'];

      // If data is null or not a Map, create empty default
      if (dataJson == null || dataJson is! Map<String, dynamic>) {
        dataJson = <String, dynamic>{};
      }

      return DashboardResponse(
        success: json['success'] == true,
        data: DashboardData.fromJson(dataJson),
      );
    } catch (e) {
      AppLogger.e('Error parsing DashboardResponse: $e');
      AppLogger.info('JSON structure: $json');
      rethrow;
    }
  }

  Map<String, dynamic> toJson() {
    return {'success': success, 'data': data.toJson()};
  }
}

class DashboardData {
  final String totalBalance;
  final int totalEvents;
  final int totalEventBookings;
  final int transactionCount;
  final List<double> eventIncomes;
  final List<String> eventMonths;
  final List<int> totalBookings;
  final CurrencyInfo? currencyInfo;

  DashboardData({
    required this.totalBalance,
    required this.totalEvents,
    required this.totalEventBookings,
    required this.transactionCount,
    required this.eventIncomes,
    required this.eventMonths,
    required this.totalBookings,
    this.currencyInfo,
  });

  /// Balance formatted with the currency symbol from API.
  String get formattedBalance =>
      currencyInfo?.format(totalBalance) ?? totalBalance;

  /// Compact balance for display (e.g. \$12.9M). Falls back to [formattedBalance]
  /// for values below 1,000,000.
  String get formattedBalanceCompact {
    final n = NumberFormatter.parseNum(totalBalance);
    if (NumberFormatter.needsCompact(n)) {
      return currencyInfo?.formatCompact(totalBalance) ?? NumberFormatter.compact(n);
    }
    return formattedBalance;
  }

  /// Full exact formatted balance string (always full decimal).
  String get exactFormattedBalance => formattedBalance;

  factory DashboardData.fromJson(Map<String, dynamic> json) {
    try {
      AppLogger.info('Starting DashboardData parsing...');
      AppLogger.info('Raw JSON: $json');

      // Parse eventIncomes - handle mixed int/string values
      final List<double> eventIncomes = [];
      try {
        final eventIncomesRaw = json['eventIncomes'];
        AppLogger.info('eventIncomesRaw type: ${eventIncomesRaw.runtimeType}');
        AppLogger.info('eventIncomesRaw value: $eventIncomesRaw');

        if (eventIncomesRaw != null) {
          if (eventIncomesRaw is List) {
            for (var i = 0; i < eventIncomesRaw.length; i++) {
              var item = eventIncomesRaw[i];
              if (item is num) {
                eventIncomes.add(item.toDouble());
              } else if (item is String) {
                eventIncomes.add(double.tryParse(item) ?? 0.0);
              } else {
                eventIncomes.add(0.0);
              }
            }
          } else if (eventIncomesRaw is Map) {
            // If it's a map, convert to list
            var keys = eventIncomesRaw.keys.toList();
            for (var key in keys) {
              var item = eventIncomesRaw[key];
              if (item is num) {
                eventIncomes.add(item.toDouble());
              } else if (item is String) {
                eventIncomes.add(double.tryParse(item) ?? 0.0);
              } else {
                eventIncomes.add(0.0);
              }
            }
          }
        }
      } catch (e) {
        AppLogger.e('Error parsing eventIncomes: $e');
      }

      // Parse eventMonths
      final List<String> eventMonths = [];
      try {
        final eventMonthsRaw = json['eventMonths'];
        AppLogger.info('eventMonthsRaw type: ${eventMonthsRaw.runtimeType}');

        if (eventMonthsRaw != null) {
          if (eventMonthsRaw is List) {
            for (var i = 0; i < eventMonthsRaw.length; i++) {
              eventMonths.add(eventMonthsRaw[i].toString());
            }
          } else if (eventMonthsRaw is Map) {
            var keys = eventMonthsRaw.keys.toList();
            for (var key in keys) {
              eventMonths.add(eventMonthsRaw[key].toString());
            }
          }
        }
      } catch (e) {
        AppLogger.e('Error parsing eventMonths: $e');
      }

      // Parse totalBookings - handle mixed int/string values
      final List<int> totalBookings = [];
      try {
        final totalBookingsRaw = json['totalBookings'];
        AppLogger.info(
          'totalBookingsRaw type: ${totalBookingsRaw.runtimeType}',
        );

        if (totalBookingsRaw != null) {
          if (totalBookingsRaw is List) {
            for (var i = 0; i < totalBookingsRaw.length; i++) {
              var item = totalBookingsRaw[i];
              if (item is int) {
                totalBookings.add(item);
              } else if (item is String) {
                totalBookings.add(int.tryParse(item) ?? 0);
              } else if (item is num) {
                totalBookings.add(item.toInt());
              } else {
                totalBookings.add(0);
              }
            }
          } else if (totalBookingsRaw is Map) {
            var keys = totalBookingsRaw.keys.toList();
            for (var key in keys) {
              var item = totalBookingsRaw[key];
              if (item is int) {
                totalBookings.add(item);
              } else if (item is String) {
                totalBookings.add(int.tryParse(item) ?? 0);
              } else if (item is num) {
                totalBookings.add(item.toInt());
              } else {
                totalBookings.add(0);
              }
            }
          }
        }
      } catch (e) {
        AppLogger.e('Error parsing totalBookings: $e');
      }

      return DashboardData(
        totalBalance: json['balance'] is String
            ? json['balance']
            : json['balance']?.toString() ?? '',
        totalEvents: json['total_events'] is int
            ? json['total_events']
            : int.tryParse(json['total_events']?.toString() ?? '') ?? 0,

        totalEventBookings: json['total_event_bookings'] is int
            ? json['total_event_bookings']
            : int.tryParse(json['total_event_bookings']?.toString() ?? '') ?? 0,

        transactionCount: json['transcation_count'] is int
            ? json['transcation_count']
            : int.tryParse(json['transcation_count']?.toString() ?? '') ?? 0,

        eventIncomes: eventIncomes,
        eventMonths: eventMonths,
        totalBookings: totalBookings,
        currencyInfo: json['getCurrencyInfo'] is Map<String, dynamic>
            ? CurrencyInfo.fromJson(json['getCurrencyInfo'] as Map<String, dynamic>)
            : null,
      );
    } catch (e, stackTrace) {
      AppLogger.e('Error parsing DashboardData: $e');
      AppLogger.e('Stack trace: $stackTrace');
      AppLogger.info('JSON: $json');
      rethrow;
    }
  }

  Map<String, dynamic> toJson() {
    return {
      'total_balance': totalBalance,
      'total_events': totalEvents,
      'total_event_bookings': totalEventBookings,
      'transcation_count': transactionCount,
      'eventIncomes': eventIncomes,
      'eventMonths': eventMonths,
      'totalBookings': totalBookings,
    };
  }
}
