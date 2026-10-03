import 'dart:convert';

/// Per-language ticket title/description from the `ticket_contents` array.
class EditTicketContent {
  final int id;
  final int languageId;
  final int ticketId;
  final String? title;
  final String? description;

  EditTicketContent({
    required this.id,
    required this.languageId,
    required this.ticketId,
    this.title,
    this.description,
  });

  factory EditTicketContent.fromJson(Map<String, dynamic> json) {
    return EditTicketContent(
      id: json['id'] as int? ?? 0,
      languageId: int.tryParse(json['language_id']?.toString() ?? '0') ?? 0,
      ticketId: int.tryParse(json['ticket_id']?.toString() ?? '0') ?? 0,
      title: json['title']?.toString(),
      description: json['description']?.toString(),
    );
  }
}

/// Response model for GET /api/organizer/event-management/event/edit-ticket
class EditTicketResponse {
  final List<EditTicketLanguage> languages;
  final EditTicketLanguage language;
  final EditTicketItem ticket;
  final List<EditTicketVariation> variations;
  final EditTicketCurrency currencyInfo;
  final String eventId;
  final int ticketId;

  /// Per-language title/description entries from `ticket_contents`.
  final List<EditTicketContent> ticketContents;

  EditTicketResponse({
    required this.languages,
    required this.language,
    required this.ticket,
    required this.variations,
    required this.currencyInfo,
    required this.eventId,
    required this.ticketId,
    this.ticketContents = const [],
  });

  factory EditTicketResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? {};
    // Variations: either a top-level list from the API, or parsed from ticket.variations string
    List<EditTicketVariation> vars = [];
    final rawVars = data['variations'];
    if (rawVars is List && rawVars.isNotEmpty) {
      vars = rawVars
          .map((v) => EditTicketVariation.fromJson(v as Map<String, dynamic>))
          .toList();
    }
    return EditTicketResponse(
      languages: (data['languages'] as List<dynamic>? ?? [])
          .map((e) => EditTicketLanguage.fromJson(e as Map<String, dynamic>))
          .toList(),
      language: EditTicketLanguage.fromJson(
        data['language'] as Map<String, dynamic>? ?? {},
      ),
      ticket: EditTicketItem.fromJson(
        data['ticket'] as Map<String, dynamic>? ?? {},
      ),
      variations: vars,
      currencyInfo: EditTicketCurrency.fromJson(
        data['getCurrencyInfo'] as Map<String, dynamic>? ?? {},
      ),
      eventId: data['event_id']?.toString() ?? '',
      ticketId: data['ticket_id'] as int? ?? 0,
      ticketContents: (data['ticket_contents'] as List<dynamic>? ?? [])
          .map((e) => EditTicketContent.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

class EditTicketLanguage {
  final int id;
  final String name;
  final String code;
  final bool isRtl;
  final bool isDefault;

  EditTicketLanguage({
    required this.id,
    required this.name,
    required this.code,
    required this.isRtl,
    required this.isDefault,
  });

  factory EditTicketLanguage.fromJson(Map<String, dynamic> json) {
    return EditTicketLanguage(
      id: json['id'] as int? ?? 0,
      name: json['name']?.toString() ?? '',
      code: json['code']?.toString() ?? '',
      isRtl: json['direction']?.toString() == '1',
      isDefault: json['is_default']?.toString() == '1',
    );
  }
}

class EditTicketCurrency {
  final String symbol;
  final String text;

  EditTicketCurrency({required this.symbol, required this.text});

  factory EditTicketCurrency.fromJson(Map<String, dynamic> json) {
    return EditTicketCurrency(
      symbol: json['base_currency_symbol']?.toString() ?? '',
      text: json['base_currency_text']?.toString() ?? '',
    );
  }
}

class EditTicketItem {
  final int id;
  final String eventId;
  final String eventType;
  final String? title;
  final String ticketAvailableType; // unlimited / limited
  final String? ticketAvailable;
  final String maxTicketBuyType; // unlimited / limited
  final String? maxBuyTicket;
  final String pricingType; // free / normal / variation
  final String price;
  final String? fPrice;
  final String earlyBirdDiscount; // disable / enable
  final String? earlyBirdDiscountAmount;
  final String earlyBirdDiscountType; // fixed / percentage
  final String? earlyBirdDiscountDate;
  final String? earlyBirdDiscountTime;
  final List<EditTicketVariation> variations;
  final bool normalTicketSlotEnable;
  final bool freeTicketSlotEnable;

  /// Slot unique ID for normal/variation ticket seat map (normal_ticket_slot_unique_id)
  final String? normalTicketSlotUniqueId;

  /// Slot unique ID for free ticket seat map (free_tickete_slot_unique_id)
  final String? freeTicketSlotUniqueId;

  /// Returns the appropriate slot unique ID based on [pricingType].
  String? get slotUniqueId =>
      isFree ? freeTicketSlotUniqueId : normalTicketSlotUniqueId;

  EditTicketItem({
    required this.id,
    required this.eventId,
    required this.eventType,
    this.title,
    required this.ticketAvailableType,
    this.ticketAvailable,
    required this.maxTicketBuyType,
    this.maxBuyTicket,
    required this.pricingType,
    required this.price,
    this.fPrice,
    required this.earlyBirdDiscount,
    this.earlyBirdDiscountAmount,
    required this.earlyBirdDiscountType,
    this.earlyBirdDiscountDate,
    this.earlyBirdDiscountTime,
    required this.variations,
    this.normalTicketSlotEnable = false,
    this.freeTicketSlotEnable = false,
    this.normalTicketSlotUniqueId,
    this.freeTicketSlotUniqueId,
  });

  factory EditTicketItem.fromJson(Map<String, dynamic> json) {
    List<EditTicketVariation> vars = [];
    final rawVars = json['variations'];
    if (rawVars is String && rawVars.isNotEmpty) {
      try {
        final decoded = jsonDecode(rawVars) as List<dynamic>;
        vars = decoded
            .map((v) => EditTicketVariation.fromJson(v as Map<String, dynamic>))
            .toList();
      } catch (_) {}
    } else if (rawVars is List) {
      vars = rawVars
          .map((v) => EditTicketVariation.fromJson(v as Map<String, dynamic>))
          .toList();
    }
    return EditTicketItem(
      id: json['id'] as int? ?? 0,
      eventId: json['event_id']?.toString() ?? '',
      eventType: json['event_type']?.toString() ?? '',
      title: json['title']?.toString(),
      ticketAvailableType:
          json['ticket_available_type']?.toString() ?? 'unlimited',
      ticketAvailable: json['ticket_available']?.toString(),
      maxTicketBuyType: json['max_ticket_buy_type']?.toString() ?? 'unlimited',
      maxBuyTicket: json['max_buy_ticket']?.toString(),
      pricingType: json['pricing_type']?.toString() ?? 'free',
      price: json['price']?.toString() ?? '0',
      fPrice: json['f_price']?.toString(),
      earlyBirdDiscount: json['early_bird_discount']?.toString() ?? 'disable',
      earlyBirdDiscountAmount: json['early_bird_discount_amount']?.toString(),
      earlyBirdDiscountType:
          json['early_bird_discount_type']?.toString() ?? 'fixed',
      earlyBirdDiscountDate: json['early_bird_discount_date']?.toString(),
      earlyBirdDiscountTime: json['early_bird_discount_time']?.toString(),
      variations: vars,
      normalTicketSlotEnable:
          json['normal_ticket_slot_enable']?.toString() == '1',
      freeTicketSlotEnable: json['free_tickete_slot_enable']?.toString() == '1',
      normalTicketSlotUniqueId: json['normal_ticket_slot_unique_id']
          ?.toString(),
      freeTicketSlotUniqueId: json['free_tickete_slot_unique_id']?.toString(),
    );
  }

  bool get isFree => pricingType == 'free';
  bool get isNormal => pricingType == 'normal';
  bool get isVariation => pricingType == 'variation';
  bool get isAvailableLimited => ticketAvailableType == 'limited';
  bool get isMaxLimited => maxTicketBuyType == 'limited';
  bool get isEarlyBird => earlyBirdDiscount == 'enable';

  /// Price index for the toggle: 0=Free, 1=Variation, 2=Fixed(normal)
  int get priceTypeIndex {
    if (pricingType == 'free') return 0;
    if (pricingType == 'variation') return 1;
    return 2; // normal / fixed
  }
}

class EditTicketVariation {
  /// From the top-level variations array, name is per-language
  final String? enName;
  final String? arName;

  /// From the ticket.variations JSON string, name is just 'name'
  final String? name;
  final String price;
  final String ticketAvailableType;
  final String? ticketAvailable;
  final String maxTicketBuyType;
  final String? vMaxTicketBuy;
  final bool slotEnable;

  /// Slot unique ID for this variation's seat map
  final String? slotUniqueId;

  EditTicketVariation({
    this.enName,
    this.arName,
    this.name,
    required this.price,
    required this.ticketAvailableType,
    this.ticketAvailable,
    required this.maxTicketBuyType,
    this.vMaxTicketBuy,
    this.slotEnable = false,
    this.slotUniqueId,
  });

  factory EditTicketVariation.fromJson(Map<String, dynamic> json) {
    return EditTicketVariation(
      enName: json['en_name']?.toString() ?? json['name']?.toString(),
      arName: json['ar_name']?.toString(),
      name: json['name']?.toString(),
      price: json['price']?.toString() ?? '0',
      ticketAvailableType:
          json['ticket_available_type']?.toString() ?? 'unlimited',
      ticketAvailable: json['ticket_available']?.toString(),
      maxTicketBuyType: json['max_ticket_buy_type']?.toString() ?? 'unlimited',
      vMaxTicketBuy: json['v_max_ticket_buy']?.toString(),
      slotEnable:
          (json['slot_enable'] == 1 || json['slot_enable']?.toString() == '1'),
      slotUniqueId:
          json['slot_unique_id']?.toString() ??
          json['slot_unique_id_input']?.toString(),
    );
  }

  String get displayEnName => enName ?? name ?? '';
  String get displayArName => arName ?? '';
  bool get isAvailableLimited => ticketAvailableType == 'limited';
  bool get isMaxLimited => maxTicketBuyType == 'limited';
}
