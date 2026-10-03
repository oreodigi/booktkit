import 'add_event_init_model.dart' show CurrencyInfo;

/// Model for the full event-edit API response.
class EventDetailResponse {
  final EventDetail event;
  final List<EventContent> eventContents;
  final List<EventImage> eventImages;
  final List<LanguageInfo> languages;
  final List<EventDate> eventDates;
  final CurrencyInfo? currencyInfo;

  EventDetailResponse({
    required this.event,
    required this.eventContents,
    required this.eventImages,
    required this.languages,
    required this.eventDates,
    this.currencyInfo,
  });

  factory EventDetailResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? {};
    return EventDetailResponse(
      event: EventDetail.fromJson(data['event'] as Map<String, dynamic>? ?? {}),
      eventContents: (data['event_contents'] as List<dynamic>? ?? [])
          .map((e) => EventContent.fromJson(e as Map<String, dynamic>))
          .toList(),
      eventImages: (data['event_images'] as List<dynamic>? ?? [])
          .map((e) => EventImage.fromJson(e as Map<String, dynamic>))
          .toList(),
      languages: (data['languages'] as List<dynamic>? ?? [])
          .map((e) => LanguageInfo.fromJson(e as Map<String, dynamic>))
          .toList(),
      eventDates: (data['event_dates'] as List<dynamic>? ?? [])
          .map((e) => EventDate.fromJson(e as Map<String, dynamic>))
          .toList(),
      currencyInfo: data['getCurrencyInfo'] != null
          ? CurrencyInfo.fromJson(
              data['getCurrencyInfo'] as Map<String, dynamic>,
            )
          : null,
    );
  }
}

class EventDetail {
  final int id;
  final String organizerId;
  final String? thumbnail;
  final String status;
  final String dateType;
  final String countdownStatus;
  final String? startDate;
  final String? startTime;
  final String? duration;
  final String? endDate;
  final String? endTime;
  final String eventType;
  final String isFeatured;
  final String? latitude;
  final String? longitude;
  final String? instructions;
  final String? meetingUrl;
  final EventTicket? ticket;

  EventDetail({
    required this.id,
    required this.organizerId,
    this.thumbnail,
    required this.status,
    required this.dateType,
    required this.countdownStatus,
    this.startDate,
    this.startTime,
    this.duration,
    this.endDate,
    this.endTime,
    required this.eventType,
    required this.isFeatured,
    this.latitude,
    this.longitude,
    this.instructions,
    this.meetingUrl,
    this.ticket,
  });

  factory EventDetail.fromJson(Map<String, dynamic> json) {
    return EventDetail(
      id: json['id'] as int? ?? 0,
      organizerId: json['organizer_id']?.toString() ?? '',
      thumbnail: json['thumbnail']?.toString(),
      status: json['status']?.toString() ?? '0',
      dateType: json['date_type']?.toString() ?? 'single',
      countdownStatus: json['countdown_status']?.toString() ?? '0',
      startDate: json['start_date']?.toString(),
      startTime: json['start_time']?.toString(),
      duration: json['duration']?.toString(),
      endDate: json['end_date']?.toString(),
      endTime: json['end_time']?.toString(),
      eventType: json['event_type']?.toString() ?? 'venue',
      isFeatured: json['is_featured']?.toString() ?? 'no',
      latitude: json['latitude']?.toString(),
      longitude: json['longitude']?.toString(),
      instructions: json['instructions']?.toString(),
      meetingUrl: json['meeting_url']?.toString(),
      ticket: json['ticket'] != null
          ? EventTicket.fromJson(json['ticket'] as Map<String, dynamic>)
          : null,
    );
  }

  bool get isActive => status == '1';
  bool get isFeaturedBool => isFeatured.toLowerCase() == 'yes';
  bool get isSingleDate => dateType == 'single';
  bool get isCountdownActive => countdownStatus == '1';
  bool get isVenue => eventType.toLowerCase() == 'venue';
}

class EventTicket {
  final int id;
  final String eventId;
  final String eventType;
  final String? title;
  final String ticketAvailableType;
  final String? ticketAvailable;
  final String maxTicketBuyType;
  final String? maxBuyTicket;
  final String? description;
  final String pricingType;
  final String price;
  final String fPrice;
  final String earlyBirdDiscount;
  final String? earlyBirdDiscountAmount;
  final String? earlyBirdDiscountType;
  final String? earlyBirdDiscountDate;
  final String? earlyBirdDiscountTime;

  EventTicket({
    required this.id,
    required this.eventId,
    required this.eventType,
    this.title,
    required this.ticketAvailableType,
    this.ticketAvailable,
    required this.maxTicketBuyType,
    this.maxBuyTicket,
    this.description,
    required this.pricingType,
    required this.price,
    required this.fPrice,
    required this.earlyBirdDiscount,
    this.earlyBirdDiscountAmount,
    this.earlyBirdDiscountType,
    this.earlyBirdDiscountDate,
    this.earlyBirdDiscountTime,
  });

  factory EventTicket.fromJson(Map<String, dynamic> json) {
    return EventTicket(
      id: json['id'] as int? ?? 0,
      eventId: json['event_id']?.toString() ?? '',
      eventType: json['event_type']?.toString() ?? '',
      title: json['title']?.toString(),
      ticketAvailableType: json['ticket_available_type']?.toString() ?? 'unlimited',
      ticketAvailable: json['ticket_available']?.toString(),
      maxTicketBuyType: json['max_ticket_buy_type']?.toString() ?? 'unlimited',
      maxBuyTicket: json['max_buy_ticket']?.toString(),
      description: json['description']?.toString(),
      pricingType: json['pricing_type']?.toString() ?? 'free',
      price: json['price']?.toString() ?? '0',
      fPrice: json['f_price']?.toString() ?? '0',
      earlyBirdDiscount: json['early_bird_discount']?.toString() ?? 'disable',
      earlyBirdDiscountAmount: json['early_bird_discount_amount']?.toString(),
      earlyBirdDiscountType: json['early_bird_discount_type']?.toString(),
      earlyBirdDiscountDate: json['early_bird_discount_date']?.toString(),
      earlyBirdDiscountTime: json['early_bird_discount_time']?.toString(),
    );
  }

  bool get isUnlimitedTickets => ticketAvailableType == 'unlimited';
  bool get isUnlimitedPerCustomer => maxTicketBuyType == 'unlimited';
  bool get isEarlyBirdEnabled => earlyBirdDiscount == 'enable';
  bool get isFree => pricingType == 'free';
}

class EventContent {
  final int id;
  final String eventId;
  final String languageId;
  final String? eventCategoryId;
  final String title;
  final String slug;
  final String? description;
  final String? address;
  final String? countryId;
  final String? stateId;
  final String? cityId;
  final String? zipCode;
  final String? metaKeywords;
  final String? metaDescription;
  final String? refundPolicy;

  EventContent({
    required this.id,
    required this.eventId,
    required this.languageId,
    this.eventCategoryId,
    required this.title,
    required this.slug,
    this.description,
    this.address,
    this.countryId,
    this.stateId,
    this.cityId,
    this.zipCode,
    this.metaKeywords,
    this.metaDescription,
    this.refundPolicy,
  });

  factory EventContent.fromJson(Map<String, dynamic> json) {
    return EventContent(
      id: json['id'] as int? ?? 0,
      eventId: json['event_id']?.toString() ?? '',
      languageId: json['language_id']?.toString() ?? '',
      eventCategoryId: json['event_category_id']?.toString(),
      title: json['title']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
      description: json['description']?.toString(),
      address: json['address']?.toString(),
      countryId: json['country_id']?.toString(),
      stateId: json['state_id']?.toString(),
      cityId: json['city_id']?.toString(),
      zipCode: json['zip_code']?.toString(),
      metaKeywords: json['meta_keywords']?.toString(),
      metaDescription: json['meta_description']?.toString(),
      refundPolicy: json['refund_policy']?.toString(),
    );
  }
}

class EventImage {
  final int id;
  final String image;

  EventImage({required this.id, required this.image});

  factory EventImage.fromJson(Map<String, dynamic> json) {
    return EventImage(
      id: json['id'] as int? ?? 0,
      image: json['image']?.toString() ?? '',
    );
  }
}

class EventDate {
  final int id;
  final String eventId;
  final String? startDate;
  final String? startTime;
  final String? endDate;
  final String? endTime;
  final String? duration;

  EventDate({
    required this.id,
    required this.eventId,
    this.startDate,
    this.startTime,
    this.endDate,
    this.endTime,
    this.duration,
  });

  factory EventDate.fromJson(Map<String, dynamic> json) {
    return EventDate(
      id: json['id'] as int? ?? 0,
      eventId: json['event_id']?.toString() ?? '',
      startDate: json['start_date']?.toString(),
      startTime: json['start_time']?.toString(),
      endDate: json['end_date']?.toString(),
      endTime: json['end_time']?.toString(),
      duration: json['duration']?.toString(),
    );
  }
}

class LanguageInfo {
  final int id;
  final String name;
  final String code;
  final String direction; // "0" = ltr, "1" = rtl
  final String isDefault;

  LanguageInfo({
    required this.id,
    required this.name,
    required this.code,
    required this.direction,
    required this.isDefault,
  });

  factory LanguageInfo.fromJson(Map<String, dynamic> json) {
    return LanguageInfo(
      id: json['id'] as int? ?? 0,
      name: json['name']?.toString() ?? '',
      code: json['code']?.toString() ?? '',
      direction: json['direction']?.toString() ?? '0',
      isDefault: json['is_default']?.toString() ?? '0',
    );
  }

  bool get isRtl => direction == '1';
  bool get isDefaultLang => isDefault == '1';
}
