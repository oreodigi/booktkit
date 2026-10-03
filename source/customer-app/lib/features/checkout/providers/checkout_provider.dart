import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:booktkit_customer/features/checkout/ui/screens/checkout_screen.dart';
import 'package:booktkit_customer/features/account/data/models/customer_model.dart';
import 'package:booktkit_customer/features/checkout/data/models/checkout_payment_model.dart';
import 'package:booktkit_customer/features/checkout/data/models/checkout_booking_model.dart';
import 'package:booktkit_customer/features/checkout/data/models/checkout_preflight_model.dart';
import 'package:booktkit_customer/features/checkout/data/models/checkout_customer_info.dart';
import 'package:booktkit_customer/features/checkout/data/models/checkout_navigation_helper.dart';

/// Orchestrator view model that delegates to smaller domain models.
class CheckoutProvider extends ChangeNotifier {
  // Public state
  bool submitting = false;
  bool initializingPayment = false;
  bool bookingStarted = false;
  bool autoSubmitTriggered = false;
  bool paymentFinalized = false;
  bool paymentExecuted = false;
  String? error;
  double couponDiscount = 0.0;
  String? couponMessage;
  PaymentGateway gateway = PaymentGateway.offline;
  Map<String, String>? selectedOfflineGateway;
  String? offlineAttachmentPath;

  // Internal
  Map<String, dynamic> _rawData = {};
  List _items = const [];
  double _total = 0.0;

  // Minimal references (set on init)
  String _authToken = '';
  CustomerModel? _customer;
  Future<void> Function(String token)? _onRefreshBookings;

  // Extracted models
  final CheckoutPreflightModel _preflight = const CheckoutPreflightModel();

  void init({
    required String token,
    required CustomerModel? customer,
    Future<void> Function(String token)? onRefreshBookings,
    required Map<String, dynamic> data,
    required List items,
    required double total,
  }) {
    _authToken = token;
    _customer = customer;
    _onRefreshBookings = onRefreshBookings;
    _rawData = data;
    _items = items;
    _total = total;

    // Logging removed.
  }

  // Guest billing helpers: allow UI to update raw data fields
  String getRawField(String key) {
    final v = _rawData[key];
    return v == null ? '' : v.toString();
  }

  void setRawField(String key, String? value) {
    _rawData[key] = (value ?? '').toString().trim();
  }

  void setGateway(PaymentGateway g) {
    // Logging removed.
    gateway = g;
    if (g != PaymentGateway.offline) {
      // Clear any offline-specific selections
      selectedOfflineGateway = null;
      _rawData['gatewayType'] = 'online';
      _rawData['gateway'] = g.name;
      _rawData['paymentMethod'] = g.name;
      _rawData.remove('offline_gateway_id');
      _rawData.remove('offline_has_attachment');
    } else {
      _rawData['gatewayType'] = 'offline';
      _rawData['gateway'] = 'offline';
      _rawData['paymentMethod'] = 'offline';
    }
    notifyListeners();
  }

  void setSelection({required PaymentGateway g, Map<String, String>? offline}) {
    gateway = g;
    selectedOfflineGateway = offline;
    if (g == PaymentGateway.offline && offline != null) {
      _rawData['gatewayType'] = 'offline';
      _rawData['gateway'] = offline['name'] ?? 'offline';
      _rawData['paymentMethod'] = offline['name'] ?? 'offline';
      _rawData['offline_gateway_id'] = offline['id'] ?? '';
      _rawData['offline_has_attachment'] = (offline['has_attachment'] ?? '0');
    } else if (g != PaymentGateway.offline) {
      _rawData['gatewayType'] = 'online';
      _rawData['gateway'] = g.name;
      _rawData['paymentMethod'] = g.name;
      _rawData.remove('offline_gateway_id');
      _rawData.remove('offline_has_attachment');
    }
    notifyListeners();
  }

  void setOfflineAttachmentPath(String? path) {
    offlineAttachmentPath = path;
    if (path == null || path.isEmpty) {
      _rawData.remove('attachment_path');
    } else {
      _rawData['attachment_path'] = path;
    }
    notifyListeners();
  }

  void setCoupon(double discount, String? message) {
    // Logging removed for production.
    couponDiscount = discount;
    couponMessage = message;
    notifyListeners();
  }

  bool autoPaidFlag() {
    final ps = (_rawData['paymentStatus'] ?? _rawData['payment_status'] ?? '')
        .toString()
        .toLowerCase();
    final flag = (_rawData['pgwPaid'] == true || _rawData['pgw_paid'] == true);
    final result = flag || ps == 'completed' || ps == 'success' || ps == 'paid';

    // Logging removed.
    return result;
  }

  Future<void> maybeAutoSubmit() async {
    if (paymentFinalized) return;
    if (paymentExecuted && !autoSubmitTriggered) return;
    if (autoPaidFlag() &&
        !submitting &&
        !initializingPayment &&
        !autoSubmitTriggered &&
        !paymentFinalized) {
      autoSubmitTriggered = true;
      submitting = true;
      bookingStarted = true;
      notifyListeners();
      scheduleMicrotask(() => submit());
    }
  }

  Future<void> submit() async {
    // Logging removed.

    submitting = true;
    bookingStarted = true;
    error = null;
    notifyListeners();

    final customer = _customer;
    final token = _authToken;

    // Logging removed.

    try {
      // Preflight check (FCM readiness)
      final (ok, preErr) = await _preflight.ensureFcmReady();
      if (!ok) {
        error =
            preErr ??
            'Notification token unavailable. Please wait and try again.';
        submitting = false;
        bookingStarted = false;
        notifyListeners();
        return;
      }

      // Payment flow
      final existingStatus =
          (_rawData['paymentStatus'] ?? _rawData['payment_status'] ?? '')
              .toString()
              .toLowerCase();
      final alreadySuccessful =
          existingStatus == 'completed' ||
          existingStatus == 'success' ||
          existingStatus == 'paid' ||
          existingStatus == 'approved' ||
          existingStatus == 'captured';

      // Logging removed.

      if (gateway != PaymentGateway.offline &&
          !paymentExecuted &&
          !alreadySuccessful) {
  // Logging removed.

        // Build customer/payment identity snapshot
        final id = CheckoutCustomerInfo.from(customer, _rawData);

        initializingPayment = true;
        notifyListeners();
        final payModel = CheckoutPaymentModel(gateway: gateway);
        final payOutcome = await payModel.execute(
          rawData: _rawData,
          total: _total,
          couponDiscount: couponDiscount,
          fullName: id.fullName,
          email: id.email,
          phone: id.phone,
          customerId: id.customerId,
        );
        initializingPayment = false;
        if (!payOutcome.success) {
          error = payOutcome.error ?? 'Payment failed';
          submitting = false;
          bookingStarted = false;
          notifyListeners();
          return;
        }
        _rawData = payOutcome.updatedData ?? _rawData;
        paymentExecuted = true;
        notifyListeners();
      } else {
        // Logging removed.
      }

      // Booking creation (handle offline attachment requirement enforcement)
      if (gateway == PaymentGateway.offline) {
        final needAttach =
            (_rawData['offline_has_attachment']?.toString() ?? '0') == '1';
        final havePath =
            (_rawData['attachment_path']?.toString() ?? '').isNotEmpty;
        if (needAttach && !havePath) {
          error = 'Payment proof is required for the selected bank.';
          submitting = false;
          bookingStarted = false;
          notifyListeners();
          return;
        }
      }

      // Booking creation
      final bookingModel = CheckoutBookingModel();
      final (ok2, bookErr, info, payload) = await bookingModel.create(
        rawData: _rawData,
        items: _items,
        total: _total,
        customer: _customer,
        couponDiscount: couponDiscount,
        token: token,
      );
      if (!ok2) {
        error = bookErr;
        submitting = false;
        bookingStarted = false;
        notifyListeners();
        return;
      }

      final bookingId = CheckoutBookingModel.extractBookingId(info);
  // Logging removed.

      if (token.isNotEmpty && _onRefreshBookings != null) {
  // Logging removed.
        unawaited(_onRefreshBookings!(token));
      }

      final successArgs = <String, dynamic>{
        'booking_info': bookingId.isNotEmpty
            ? {...info, 'booking_id': bookingId}
            : info,
        'payload': payload,
      };

  Future.microtask(() => CheckoutNavigationHelper.goToSuccess(successArgs));
  paymentFinalized = true;
    } catch (e) {
      error = e.toString();
  // Logging removed.
      
    } finally {
      submitting = false;
      bookingStarted = false;
      notifyListeners();
      // Logging removed.
    }
  }
}
