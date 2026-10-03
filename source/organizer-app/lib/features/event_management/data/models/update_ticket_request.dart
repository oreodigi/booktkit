import 'package:dio/dio.dart';

/// Request model for POST /organizer/event-management/event/update-ticket
class UpdateTicketRequest {
  const UpdateTicketRequest._({
    required this.eventId,
    required this.ticketId,
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
    
    // Normal / Free Slot Enable
    this.freeTicketeSlotEnable,
    this.freeTicketeSlotUniqueId,
    this.slotEnableNoVaidation,
    this.slotUniqueIdNoVaidation,

    // Variation arrays
    this.variationPrice,
    this.vTicketAvailableType,
    this.vTicketAvailable,
    this.vMaxTicketBuyType,
    this.vMaxTicketBuy,
    this.slotEnableInput,
    this.slotUniqueIdInput,
    this.slotSeatMinPrice,
    this.variationNames,
  });

  final int eventId;
  final int ticketId;
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

  /// Map of langCode → title
  final Map<String, String> titles;

  /// Map of langCode → description
  final Map<String, String> descriptions;

  // Free Slots
  final int? freeTicketeSlotEnable;
  final int? freeTicketeSlotUniqueId;

  // Normal Slots
  final int? slotEnableNoVaidation;
  final int? slotUniqueIdNoVaidation;

  // Variation arrays
  final List<double>? variationPrice;
  final List<String>? vTicketAvailableType;
  final List<int?>? vTicketAvailable;
  final List<String>? vMaxTicketBuyType;
  final List<int?>? vMaxTicketBuy;
  final List<int>? slotEnableInput;
  final List<int>? slotUniqueIdInput;
  final List<double>? slotSeatMinPrice;

  /// Map of langCode → list of variation names
  final Map<String, List<String>>? variationNames;

  // ─────────────────────────────────────────────────────────────
  // Named factories
  // ─────────────────────────────────────────────────────────────

  factory UpdateTicketRequest.free({
    required int eventId,
    required int ticketId,
    required bool isLimitedAvailable,
    int? ticketAvailable,
    required bool isLimitedMax,
    int? maxBuyTicket,
    required bool earlyBirdEnabled,
    required Map<String, String> titles,
    required Map<String, String> descriptions,
    required int freeTicketeSlotEnable,
    int? freeTicketeSlotUniqueId,
  }) {
    return UpdateTicketRequest._(
      eventId: eventId,
      ticketId: ticketId,
      pricingType: 'free',
      ticketAvailableType: isLimitedAvailable ? 'limited' : 'unlimited',
      ticketAvailable: isLimitedAvailable ? ticketAvailable : null,
      maxTicketBuyType: isLimitedMax ? 'limited' : 'unlimited',
      maxBuyTicket: isLimitedMax ? maxBuyTicket : null,
      earlyBirdEnabled: earlyBirdEnabled,
      titles: titles,
      descriptions: descriptions,
      freeTicketeSlotEnable: freeTicketeSlotEnable,
      freeTicketeSlotUniqueId: freeTicketeSlotUniqueId,
    );
  }

  factory UpdateTicketRequest.normal({
    required int eventId,
    required int ticketId,
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
    required int slotEnableNoVaidation,
    int? slotUniqueIdNoVaidation,
  }) {
    return UpdateTicketRequest._(
      eventId: eventId,
      ticketId: ticketId,
      pricingType: 'normal',
      price: price,
      ticketAvailableType: isLimitedAvailable ? 'limited' : 'unlimited',
      ticketAvailable: isLimitedAvailable ? ticketAvailable : null,
      maxTicketBuyType: isLimitedMax ? 'limited' : 'unlimited',
      maxBuyTicket: isLimitedMax ? maxBuyTicket : null,
      earlyBirdEnabled: earlyBirdEnabled,
      discountType: earlyBirdEnabled ? discountType : null,
      earlyBirdDiscountAmount: earlyBirdEnabled ? earlyBirdDiscountAmount : null,
      earlyBirdDiscountDate: earlyBirdEnabled ? earlyBirdDiscountDate : null,
      earlyBirdDiscountTime: earlyBirdEnabled ? earlyBirdDiscountTime : null,
      titles: titles,
      descriptions: descriptions,
      slotEnableNoVaidation: slotEnableNoVaidation,
      slotUniqueIdNoVaidation: slotUniqueIdNoVaidation,
    );
  }

  factory UpdateTicketRequest.variation({
    required int eventId,
    required int ticketId,
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
    required List<int> slotEnableInput,
    required List<int> slotUniqueIdInput,
    required List<double> slotSeatMinPrice,
    required Map<String, List<String>> variationNames,
  }) {
    return UpdateTicketRequest._(
      eventId: eventId,
      ticketId: ticketId,
      pricingType: 'variation',
      ticketAvailableType: isLimitedAvailable ? 'limited' : 'unlimited',
      ticketAvailable: isLimitedAvailable ? ticketAvailable : null,
      maxTicketBuyType: isLimitedMax ? 'limited' : 'unlimited',
      maxBuyTicket: isLimitedMax ? maxBuyTicket : null,
      earlyBirdEnabled: earlyBirdEnabled,
      discountType: earlyBirdEnabled ? discountType : null,
      earlyBirdDiscountAmount: earlyBirdEnabled ? earlyBirdDiscountAmount : null,
      earlyBirdDiscountDate: earlyBirdEnabled ? earlyBirdDiscountDate : null,
      earlyBirdDiscountTime: earlyBirdEnabled ? earlyBirdDiscountTime : null,
      titles: titles,
      descriptions: descriptions,
      variationPrice: variationPrice,
      vTicketAvailableType: vTicketAvailableType,
      vTicketAvailable: vTicketAvailable,
      vMaxTicketBuyType: vMaxTicketBuyType,
      vMaxTicketBuy: vMaxTicketBuy,
      slotEnableInput: slotEnableInput,
      slotUniqueIdInput: slotUniqueIdInput,
      slotSeatMinPrice: slotSeatMinPrice,
      variationNames: variationNames,
    );
  }

  // ─────────────────────────────────────────────────────────────
  // FormData serialization
  // ─────────────────────────────────────────────────────────────

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
    add('ticket_id', ticketId);
    add('pricing_type_2', pricingType);

    if (pricingType == 'normal') {
      add('price', price);
      add('slot_enable_no_vaidation', slotEnableNoVaidation);
      add('slot_unique_id_no_vaidation', slotUniqueIdNoVaidation);
    }
    
    if (pricingType == 'free') {
      add('free_tickete_slot_enable', freeTicketeSlotEnable);
      add('free_tickete_slot_unique_id', freeTicketeSlotUniqueId);
    }

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
      addList('slot_enable_input', slotEnableInput);
      addList('slot_unique_id_input', slotUniqueIdInput);
      addList('slot_seat_min_price', slotSeatMinPrice);

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
