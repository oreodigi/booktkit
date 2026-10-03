import 'package:booktkit_customer/app/app_colors.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:provider/provider.dart';
import 'package:booktkit_customer/app/app_text_styles.dart';
import 'package:booktkit_customer/features/auth/providers/auth_provider.dart';
import 'package:booktkit_customer/features/bookings/providers/bookings_provider.dart';
import 'package:booktkit_customer/features/common/ui/widgets/custom_app_bar.dart';
import 'package:booktkit_customer/features/checkout/ui/sections/billing_section.dart';
import 'package:booktkit_customer/features/checkout/ui/widgets/payment_method_dropdown.dart';
import 'package:file_picker/file_picker.dart';
import 'package:booktkit_customer/features/checkout/ui/sections/order_summary_section.dart';
import 'package:booktkit_customer/features/checkout/ui/widgets/coupon_section.dart';
import 'package:booktkit_customer/features/checkout/ui/widgets/submit_booking_button.dart';
import 'package:booktkit_customer/features/checkout/ui/widgets/loading_overlay.dart';
import 'package:booktkit_customer/features/checkout/providers/checkout_provider.dart';

enum PaymentGateway {
  offline,
  stripe,
  flutterwave,
  paypal,
  paystack,
  xendit,
  toyyibpay,
  mollie,
  myfatoorah,
  monnify,
  nowpayments,
  phonepe,
  midtrans,
  mercadopago,
  authorizeNet,
  razorpay,
}

class CheckoutScreen extends StatelessWidget {
  const CheckoutScreen({super.key});

  String _stripHtml(String s) {
    try {
      return s
          .replaceAll(RegExp(r'<[^>]*>'), ' ')
          .replaceAll(RegExp(r'\s+'), ' ')
          .trim();
    } catch (_) {
      return s;
    }
  }

  Future<void> _onSubmit(BuildContext context, CheckoutProvider vm) async {
    if (vm.gateway == PaymentGateway.offline &&
        vm.selectedOfflineGateway != null) {
      final bank = vm.selectedOfflineGateway!;
      final needAttach = (bank['has_attachment'] ?? '0') == '1';
      String? pickedPath = vm.offlineAttachmentPath;
      final confirmed = await showDialog<bool>(
        context: context,
        builder: (ctx) => StatefulBuilder(
          builder: (dctx, setLocal) => AlertDialog.adaptive(
            backgroundColor: Colors.white,
            title: Text(bank['name'] ?? 'Offline Payment'),
            content: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  if ((bank['short_description'] ?? '').toString().isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: Text(
                        _stripHtml(bank['short_description'] ?? ''),
                        style: AppTextStyles.bodySmall,
                      ),
                    ),
                  if ((bank['instructions'] ?? '').toString().isNotEmpty) ...[
                    Text('Instructions', style: AppTextStyles.bodyLarge),
                    const SizedBox(height: 4),
                    Text(
                      _stripHtml(bank['instructions'] ?? ''),
                      style: AppTextStyles.bodySmall,
                    ),
                    const SizedBox(height: 8),
                  ],
                  if (needAttach) ...[
                    Text(
                      'Payment Proof (required)',
                      style: AppTextStyles.bodyLarge,
                    ),
                    const SizedBox(height: 6),
                    Container(
                      height: 48,
                      width: double.infinity,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: AppColors.primaryColor),
                      ),
                      child: Row(
                        children: [
                          SizedBox(
                            width: 140,
                            height: 48,
                            child: ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                padding: const EdgeInsets.symmetric(
                                  vertical: 12,
                                ),
                                elevation: 0,
                              ),
                              onPressed: () async {
                                final res = await FilePicker.platform.pickFiles(
                                  allowMultiple: false,
                                  withData: false,
                                );
                                if (res != null && res.files.isNotEmpty) {
                                  pickedPath = res.files.first.path;
                                  setLocal(() {});
                                }
                              },
                              icon: const Icon(Icons.attach_file),
                              label: const Text('Choose file'),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              (pickedPath == null || pickedPath!.isEmpty)
                                  ? 'No file selected'
                                  : pickedPath!,
                              style: AppTextStyles.bodySmall,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed:
                        needAttach &&
                            (pickedPath == null || pickedPath!.isEmpty)
                        ? null
                        : () => Navigator.of(dctx).pop(true),
                    child: const Text('Create Booking'),
                  ),
                  Center(
                    child: TextButton(
                      onPressed: () => Navigator.of(dctx).pop(false),
                      child: const Text('Cancel', textAlign: TextAlign.center),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
      if (confirmed == true) {
        vm.setOfflineAttachmentPath(pickedPath);
        await vm.submit();
      }
      return;
    }
    await vm.submit();
  }

  @override
  Widget build(BuildContext context) {
    final checkoutViewModel = context.watch<CheckoutProvider?>();
    final rawArgs = Get.arguments ?? ModalRoute.of(context)?.settings.arguments;
    Map<String, dynamic> data = <String, dynamic>{};
    if (rawArgs is Map) {
      try {
        data = Map<String, dynamic>.from(rawArgs);
      } catch (_) {
        data = {for (final e in (rawArgs).entries) e.key.toString(): e.value};
      }
    }

    final String eventTitle = (data['eventTitle'] ?? data['event_title'] ?? '')
        .toString();
    final String eventDateText =
        (data['eventDateText'] ??
                data['event_date_text'] ??
                data['event_date'] ??
                '')
            .toString();
    String eventPlaceText =
        (data['eventPlaceText'] ??
                data['event_place_text'] ??
                data['location'] ??
                '')
            .toString();
    if (eventPlaceText.trim().isEmpty) {
      final String eventTypeText =
          (data['event_type_text'] ?? data['event_type'] ?? '').toString();
      if (eventTypeText.trim().isNotEmpty) {
        eventPlaceText = eventTypeText;
      }
    }

    final List items = (data['items'] is List)
        ? List.from(data['items'] as List)
        : const [];
    final double total = () {
      final t = data['total'];
      if (t is num) return t.toDouble();
      if (t is String) return double.tryParse(t) ?? 0.0;
      final p = data['price'];
      if (p is num) return p.toDouble();
      if (p is String) return double.tryParse(p) ?? 0.0;
      return 0.0;
    }();
    double? tryD(dynamic v) {
      if (v == null) return null;
      if (v is num) return v.toDouble();
      if (v is String) return double.tryParse(v);
      return null;
    }

    final double? verifySubTotal = tryD(
      data['sub_total'] ?? data['subtotal'] ?? data['subTotal'],
    );
    final double? verifyTax = tryD(
      data['tax_total'] ?? data['tax_amount'] ?? data['tax'] ?? data['vat'],
    );
    final double? verifyTaxPercent = tryD(
      data['tax_percent'] ??
          data['tax_percentage'] ??
          data['vat_percent'] ??
          data['vat_percentage'] ??
          data['tax_rate'] ??
          data['vat_rate'],
    );
    // Normalize tax fields into data so downstream payloads include them
    if (verifyTax != null) {
      data['tax'] = verifyTax;
    }
    if (verifyTaxPercent != null) {
      data['tax_percent'] = verifyTaxPercent;
    }
    final double? verifyFees = () {
      final vals = [
        data['fees_total'],
        data['fees'],
        data['service_charge'],
        data['platform_fee'],
        data['processing_fee'],
        data['convenience_fee'],
        data['booking_fee'],
        data['charge'],
      ];
      double sum = 0;
      bool any = false;
      for (final v in vals) {
        final d = tryD(v);
        if (d != null) {
          sum += d;
          any = true;
        }
      }
      return any ? sum : null;
    }();
    final double? verifyGrandTotal = tryD(
      data['grand_total'] ?? data['grandTotal'] ?? data['total'],
    );

    // Initialize view model once
    if (checkoutViewModel == null) {
      // Provide the VM above in tree
      return _CheckoutScreenHost(data: data, items: items, total: total);
    }

    checkoutViewModel.maybeAutoSubmit();

    final autoPaid = checkoutViewModel.autoPaidFlag();

    return PopScope(
      canPop:
          !(checkoutViewModel.submitting ||
              checkoutViewModel.initializingPayment),
      onPopInvokedWithResult: (didPop, result) {
        final busy =
            checkoutViewModel.submitting ||
            checkoutViewModel.initializingPayment;
        if (busy) {}
      },
      child: Scaffold(
        appBar: CustomAppBar(
          title: 'Checkout',
          onTap: () => Navigator.of(context).pop(),
        ),
        body: Stack(
          children: [
            SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const BillingDetailsSection(),
                  const SizedBox(height: 16),
                  PaymentMethodDropdown(
                    value: checkoutViewModel.gateway,
                    selectedOfflineId:
                        checkoutViewModel.selectedOfflineGateway?['id'],
                    onChanged: (gw, offline) =>
                        checkoutViewModel.setSelection(g: gw, offline: offline),
                  ),
                  const SizedBox(height: 16),
                  OrderSummarySection(
                    eventTitle: eventTitle,
                    eventDateText: eventDateText,
                    eventPlaceText: eventPlaceText,
                    items: items
                        .whereType<Map>()
                        .map((e) => Map<String, dynamic>.from(e))
                        .toList(),
                    total: total,
                    couponDiscount: checkoutViewModel.couponDiscount,
                    verifySubTotal: verifySubTotal,
                    verifyTax: verifyTax,
                    verifyFees: verifyFees,
                    verifyGrandTotal: verifyGrandTotal,
                    currencySymbol:
                        (data['currencySymbol'] ?? data['base_currency_symbol'])
                            ?.toString(),
                    currencySymbolPosition:
                        (data['currencySymbolPosition'] ??
                                data['base_currency_symbol_position'])
                            ?.toString(),
                    taxPercent: verifyTaxPercent,
                  ),
                  const SizedBox(height: 16),
                  CouponSection(
                    controller: TextEditingController(),
                    applying: false,
                    total: total,
                    data: data,
                    onDiscount: (d) => checkoutViewModel.setCoupon(
                      d,
                      checkoutViewModel.couponMessage,
                    ),
                    onMessage: (m) => checkoutViewModel.setCoupon(
                      checkoutViewModel.couponDiscount,
                      m,
                    ),
                  ),
                  if (checkoutViewModel.couponMessage != null) ...[
                    const SizedBox(height: 6),
                    Text(
                      checkoutViewModel.couponMessage!,
                      style: AppTextStyles.bodySmall.copyWith(
                        color: checkoutViewModel.couponDiscount > 0
                            ? Colors.green
                            : Colors.red,
                      ),
                    ),
                  ],
                  const SizedBox(height: 16),
                  SubmitBookingButton(
                    disabled:
                        checkoutViewModel.submitting ||
                        checkoutViewModel.initializingPayment ||
                        checkoutViewModel.bookingStarted,
                    submitting: checkoutViewModel.submitting,
                    initializingPayment: checkoutViewModel.initializingPayment,
                    autoPaid: autoPaid,
                    autoSubmitTriggered: checkoutViewModel.autoSubmitTriggered,
                    bookingStarted: checkoutViewModel.bookingStarted,
                    pgw: checkoutViewModel.gateway,
                    onPressed: () => _onSubmit(context, checkoutViewModel),
                  ),
                  if (checkoutViewModel.error != null) ...[
                    const SizedBox(height: 12),
                    Text(
                      checkoutViewModel.error!,
                      style: AppTextStyles.bodySmall.copyWith(
                        color: Colors.red,
                      ),
                    ),
                  ],
                  const SizedBox(height: 24),
                ],
              ),
            ),
            LoadingOverlay(
              visible:
                  checkoutViewModel.submitting ||
                  checkoutViewModel.initializingPayment ||
                  checkoutViewModel.bookingStarted,
              initializingPayment: checkoutViewModel.initializingPayment,
              bookingStarted: checkoutViewModel.bookingStarted,
              error: checkoutViewModel.error,
            ),
          ],
        ),
      ),
    );
  }
}

class _CheckoutScreenHost extends StatelessWidget {
  final Map<String, dynamic> data;
  final List items;
  final double total;
  const _CheckoutScreenHost({
    required this.data,
    required this.items,
    required this.total,
  });

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(
          create: (ctx) {
            final vm = CheckoutProvider();
            final auth = ctx.read<AuthProvider>();
            final bookings = ctx.read<BookingsProvider>();
            vm.init(
              token: auth.token ?? '',
              customer: auth.customerModel,
              onRefreshBookings: (t) => bookings.refresh(t),
              data: data,
              items: items,
              total: total,
            );
            vm.maybeAutoSubmit();
            return vm;
          },
        ),
      ],
      child: const CheckoutScreen(),
    );
  }
}
