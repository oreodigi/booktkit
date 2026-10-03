import 'dart:convert';

/// Response from /organizer/event-management/event/tickets
class EventTicketsResponse {
  final List<TicketLanguage> langs;
  final TicketLanguage? language;
  final EventTicketContent event;
  final List<EventTicketItem> tickets;

  EventTicketsResponse({
    required this.langs,
    this.language,
    required this.event,
    required this.tickets,
  });

  factory EventTicketsResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? {};
    return EventTicketsResponse(
      langs: (data['langs'] as List<dynamic>? ?? [])
          .map((e) => TicketLanguage.fromJson(e as Map<String, dynamic>))
          .toList(),
      language: data['language'] != null
          ? TicketLanguage.fromJson(data['language'] as Map<String, dynamic>)
          : null,
      event: EventTicketContent.fromJson(
        data['event'] as Map<String, dynamic>? ?? {},
      ),
      tickets: (data['tickets'] as List<dynamic>? ?? [])
          .map((e) => EventTicketItem.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

class TicketLanguage {
  final int id;
  final String name;
  final String code;
  final String direction;
  final String isDefault;

  TicketLanguage({
    required this.id,
    required this.name,
    required this.code,
    required this.direction,
    required this.isDefault,
  });

  factory TicketLanguage.fromJson(Map<String, dynamic> json) {
    return TicketLanguage(
      id: json['id'] as int? ?? 0,
      name: json['name']?.toString() ?? '',
      code: json['code']?.toString() ?? '',
      direction: json['direction']?.toString() ?? '0',
      isDefault: json['is_default']?.toString() ?? '0',
    );
  }

  bool get isRtl => direction == '1';
}

class EventTicketContent {
  final int id;
  final String eventId;
  final String title;
  final String? description;
  final String? address;

  EventTicketContent({
    required this.id,
    required this.eventId,
    required this.title,
    this.description,
    this.address,
  });

  factory EventTicketContent.fromJson(Map<String, dynamic> json) {
    return EventTicketContent(
      id: json['id'] as int? ?? 0,
      eventId: json['event_id']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      description: json['description']?.toString(),
      address: json['address']?.toString(),
    );
  }
}

class EventTicketItem {
  final int id;
  final String eventId;
  final String eventType;
  final String? title;
  final String ticketAvailableType;
  final String? ticketAvailable;
  final String maxTicketBuyType;
  final String? maxBuyTicket;
  final String pricingType;
  final String price;
  final String earlyBirdDiscount;
  final String? earlyBirdDiscountAmount;
  final String? earlyBirdDiscountType;
  final String? earlyBirdDiscountDate;
  final String? earlyBirdDiscountTime;
  final List<TicketVariation> variations;

  EventTicketItem({
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
    required this.earlyBirdDiscount,
    this.earlyBirdDiscountAmount,
    this.earlyBirdDiscountType,
    this.earlyBirdDiscountDate,
    this.earlyBirdDiscountTime,
    required this.variations,
  });

  factory EventTicketItem.fromJson(Map<String, dynamic> json) {
    List<TicketVariation> parsedVariations = [];
    final rawVariations = json['variations'];
    if (rawVariations is String && rawVariations.isNotEmpty) {
      try {
        final decoded = jsonDecode(rawVariations) as List<dynamic>;
        parsedVariations = decoded
            .map((v) => TicketVariation.fromJson(v as Map<String, dynamic>))
            .toList();
      } catch (_) {}
    } else if (rawVariations is List) {
      parsedVariations = rawVariations
          .map((v) => TicketVariation.fromJson(v as Map<String, dynamic>))
          .toList();
    }

    return EventTicketItem(
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
      earlyBirdDiscount:
          json['early_bird_discount']?.toString() ?? 'disable',
      earlyBirdDiscountAmount: json['early_bird_discount_amount']?.toString(),
      earlyBirdDiscountType: json['early_bird_discount_type']?.toString(),
      earlyBirdDiscountDate: json['early_bird_discount_date']?.toString(),
      earlyBirdDiscountTime: json['early_bird_discount_time']?.toString(),
      variations: parsedVariations,
    );
  }

  bool get isFree => pricingType == 'free';
  bool get isVariation => pricingType == 'variation';
  bool get isUnlimited => ticketAvailableType == 'unlimited';
  bool get isEarlyBird => earlyBirdDiscount == 'enable';
}

class TicketVariation {
  final String name;
  final String price;
  final String ticketAvailableType;
  final String? ticketAvailable;
  final String maxTicketBuyType;
  final String? vMaxTicketBuy;

  TicketVariation({
    required this.name,
    required this.price,
    required this.ticketAvailableType,
    this.ticketAvailable,
    required this.maxTicketBuyType,
    this.vMaxTicketBuy,
  });

  factory TicketVariation.fromJson(Map<String, dynamic> json) {
    return TicketVariation(
      name: json['name']?.toString() ?? '',
      price: json['price']?.toString() ?? '0',
      ticketAvailableType:
          json['ticket_available_type']?.toString() ?? 'unlimited',
      ticketAvailable: json['ticket_available']?.toString(),
      maxTicketBuyType: json['max_ticket_buy_type']?.toString() ?? 'unlimited',
      vMaxTicketBuy: json['v_max_ticket_buy']?.toString(),
    );
  }

  bool get isUnlimited => ticketAvailableType == 'unlimited';
}
