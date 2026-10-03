import 'package:dio/dio.dart';

/// Request model for POST /organizer/event-management/event/store-ticket
///
/// Language-dependent fields (title, description, variation names) are stored
/// as [Map<langCode, value>] so the payload keys become dynamic:
///   en_title, ar_title, bn_title … depending on the app's configured languages.
///
/// Use the named factories to construct the correct shape:
///   [StoreTicketRequest.free] / [StoreTicketRequest.normal] / [StoreTicketRequest.variation]
class StoreTicketRequest {
  const StoreTicketRequest._({
    required this.eventId,
    required this.pricingType,
    this.price,
    required this.ticketAvailableType,
    this.ticketAvailable,
    required this.maxTicketBuyType,
    this.maxBuyTicket,
    required this.earlyBirdEnabled,
    this.discountType,
    this.earlyBirdDiscountAmount,
    this.earlyBirdDiscountDate,
    this.earlyBirdDiscountTime,
    required this.titles,
    required this.descriptions,
    this.variationPrice,
    this.vTicketAvailableType,
    this.vTicketAvailable,
    this.vMaxTicketBuyType,
    this.vMaxTicketBuy,
    this.variationNames,
  });

  final int eventId;
  final String pricingType; // 'free' | 'normal' | 'variation'

  // Normal only
  final double? price;

  // Ticket limits
  final String ticketAvailableType; // 'unlimited' | 'limited'
  final int? ticketAvailable;
  final String maxTicketBuyType; // 'unlimited' | 'limited'
  final int? maxBuyTicket;

  // Early bird
  final bool earlyBirdEnabled;
  final String? discountType; // 'percentage' | 'fixed'
  final double? earlyBirdDiscountAmount;
  final String? earlyBirdDiscountDate; // yyyy-MM-dd
  final String? earlyBirdDiscountTime; // HH:mm

  /// Map of langCode → title,  e.g. {'en': 'My Ticket', 'ar': 'تذكرتي'}
  final Map<String, String> titles;

  /// Map of langCode → description
  final Map<String, String> descriptions;

  // Variation arrays
  final List<double>? variationPrice;
  final List<String>? vTicketAvailableType;
  final List<int?>? vTicketAvailable;
  final List<String>? vMaxTicketBuyType;
  final List<int?>? vMaxTicketBuy;

  /// Map of langCode → list of variation names (one per variation card)
  final Map<String, List<String>>? variationNames;

  // ─────────────────────────────────────────────────────────────
  // Named factories
  // ─────────────────────────────────────────────────────────────

  factory StoreTicketRequest.free({
    required int eventId,
    required bool isLimitedAvailable,
    int? ticketAvailable,
    required bool isLimitedMax,
    int? maxBuyTicket,
    required bool earlyBirdEnabled,
    required Map<String, String> titles,
    required Map<String, String> descriptions,
  }) {
    return StoreTicketRequest._(
      eventId: eventId,
      pricingType: 'free',
      ticketAvailableType: isLimitedAvailable ? 'limited' : 'unlimited',
      ticketAvailable: isLimitedAvailable ? ticketAvailable : null,
      maxTicketBuyType: isLimitedMax ? 'limited' : 'unlimited',
      maxBuyTicket: isLimitedMax ? maxBuyTicket : null,
      earlyBirdEnabled: earlyBirdEnabled,
      titles: titles,
      descriptions: descriptions,
    );
  }

  factory StoreTicketRequest.normal({
    required int eventId,
    required double price,
    required bool isLimitedAvailable,
    int? ticketAvailable,
    required bool isLimitedMax,
    int? maxBuyTicket,
    required bool earlyBirdEnabled,
    String? discountType,
    double? earlyBirdDiscountAmount,
    String? earlyBirdDiscountDate,
    String? earlyBirdDiscountTime,
    required Map<String, String> titles,
    required Map<String, String> descriptions,
  }) {
    return StoreTicketRequest._(
      eventId: eventId,
      pricingType: 'normal',
      price: price,
      ticketAvailableType: isLimitedAvailable ? 'limited' : 'unlimited',
      ticketAvailable: isLimitedAvailable ? ticketAvailable : null,
      maxTicketBuyType: isLimitedMax ? 'limited' : 'unlimited',
      maxBuyTicket: isLimitedMax ? maxBuyTicket : null,
      earlyBirdEnabled: earlyBirdEnabled,
      discountType: earlyBirdEnabled ? discountType : null,
      earlyBirdDiscountAmount: earlyBirdEnabled
          ? earlyBirdDiscountAmount
          : null,
      earlyBirdDiscountDate: earlyBirdEnabled ? earlyBirdDiscountDate : null,
      earlyBirdDiscountTime: earlyBirdEnabled ? earlyBirdDiscountTime : null,
      titles: titles,
      descriptions: descriptions,
    );
  }

  factory StoreTicketRequest.variation({
    required int eventId,
    required bool isLimitedAvailable,
    int? ticketAvailable,
    required bool isLimitedMax,
    int? maxBuyTicket,
    required bool earlyBirdEnabled,
    String? discountType,
    double? earlyBirdDiscountAmount,
    String? earlyBirdDiscountDate,
    String? earlyBirdDiscountTime,
    required Map<String, String> titles,
    required Map<String, String> descriptions,
    required List<double> variationPrice,
    required List<String> vTicketAvailableType,
    required List<int?> vTicketAvailable,
    required List<String> vMaxTicketBuyType,
    required List<int?> vMaxTicketBuy,
    required Map<String, List<String>> variationNames,
  }) {
    return StoreTicketRequest._(
      eventId: eventId,
      pricingType: 'variation',
      ticketAvailableType: isLimitedAvailable ? 'limited' : 'unlimited',
      ticketAvailable: isLimitedAvailable ? ticketAvailable : null,
      maxTicketBuyType: isLimitedMax ? 'limited' : 'unlimited',
      maxBuyTicket: isLimitedMax ? maxBuyTicket : null,
      earlyBirdEnabled: earlyBirdEnabled,
      discountType: earlyBirdEnabled ? discountType : null,
      earlyBirdDiscountAmount: earlyBirdEnabled
          ? earlyBirdDiscountAmount
          : null,
      earlyBirdDiscountDate: earlyBirdEnabled ? earlyBirdDiscountDate : null,
      earlyBirdDiscountTime: earlyBirdEnabled ? earlyBirdDiscountTime : null,
      titles: titles,
      descriptions: descriptions,
      variationPrice: variationPrice,
      vTicketAvailableType: vTicketAvailableType,
      vTicketAvailable: vTicketAvailable,
      vMaxTicketBuyType: vMaxTicketBuyType,
      vMaxTicketBuy: vMaxTicketBuy,
      variationNames: variationNames,
    );
  }

  // ─────────────────────────────────────────────────────────────
  // FormData serialization
  // ─────────────────────────────────────────────────────────────

  /// Converts to multipart [FormData].
  /// - Scalar fields → single entry
  /// - Map language fields → `{langCode}_title`, `{langCode}_description` entries
  /// - List fields → repeated `key[]` entries (Laravel convention)
  FormData toFormData() {
    final fd = FormData();

    void add(String key, dynamic value) {
      if (value == null) return;
      fd.fields.add(MapEntry(key, value.toString()));
    }

    void addList(String key, List<dynamic>? list) {
      if (list == null) return;
      for (final item in list) {
        final val = item == null ? '' : item.toString();
        fd.fields.add(MapEntry('$key[]', val));
      }
    }

    add('event_id', eventId);
    add('pricing_type_2', pricingType);

    if (pricingType == 'normal') add('price', price);

    add('ticket_available_type', ticketAvailableType);
    if (ticketAvailableType == 'limited') {
      add('ticket_available', ticketAvailable);
    }

    add('max_ticket_buy_type', maxTicketBuyType);
    if (maxTicketBuyType == 'limited') add('max_buy_ticket', maxBuyTicket);

    add('early_bird_discount_type', earlyBirdEnabled ? 'enable' : 'disable');
    if (earlyBirdEnabled) {
      add('discount_type', discountType);
      add('early_bird_discount_amount', earlyBirdDiscountAmount);
      add('early_bird_discount_date', earlyBirdDiscountDate);
      add('early_bird_discount_time', earlyBirdDiscountTime);
    }

    // Dynamic language fields: {code}_title, {code}_description
    for (final entry in titles.entries) {
      add('${entry.key}_title', entry.value);
    }
    for (final entry in descriptions.entries) {
      add('${entry.key}_description', entry.value);
    }

    if (pricingType == 'variation') {
      addList('variation_price', variationPrice);
      addList('v_ticket_available_type', vTicketAvailableType);
      addList('v_ticket_available', vTicketAvailable);
      addList('v_max_ticket_buy_type', vMaxTicketBuyType);
      addList('v_max_ticket_buy', vMaxTicketBuy);

      // Dynamic variation name arrays: {code}_variation_name[]
      if (variationNames != null) {
        for (final entry in variationNames!.entries) {
          for (final name in entry.value) {
            fd.fields.add(MapEntry('${entry.key}_variation_name[]', name));
          }
        }
      }
    }

    return fd;
  }
}
