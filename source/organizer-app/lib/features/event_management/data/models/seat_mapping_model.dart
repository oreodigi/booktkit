/// Response from /organizer/seat-mapping/slot/all
class SeatMappingResponse {
  final bool success;
  final SeatMappingData? data;

  SeatMappingResponse({required this.success, this.data});

  factory SeatMappingResponse.fromJson(Map<String, dynamic> json) {
    return SeatMappingResponse(
      success: json['success'] as bool? ?? false,
      data: json['data'] != null
          ? SeatMappingData.fromJson(json['data'] as Map<String, dynamic>)
          : null,
    );
  }
}

class SeatMappingData {
  final String eventId;
  final String ticketId;
  final String eventType;
  final String slotUniqueId;
  final List<SlotItem> slots;
  final EventContentData? eventContents;
  final TicketContentData? ticketContents;
  final String pricingType;
  final String? coverImage;

  SeatMappingData({
    required this.eventId,
    required this.ticketId,
    required this.eventType,
    required this.slotUniqueId,
    required this.slots,
    this.eventContents,
    this.ticketContents,
    required this.pricingType,
    this.coverImage,
  });

  factory SeatMappingData.fromJson(Map<String, dynamic> json) {
    return SeatMappingData(
      eventId: json['event_id']?.toString() ?? '',
      ticketId: json['ticket_id']?.toString() ?? '',
      eventType: json['event_type']?.toString() ?? '',
      slotUniqueId: json['slot_unique_id']?.toString() ?? '',
      slots: (json['slots'] as List<dynamic>? ?? [])
          .map((e) => SlotItem.fromJson(e as Map<String, dynamic>))
          .toList(),
      eventContents: json['event_contents'] != null
          ? EventContentData.fromJson(
              json['event_contents'] as Map<String, dynamic>,
            )
          : null,
      ticketContents: json['ticket_contents'] != null
          ? TicketContentData.fromJson(
              json['ticket_contents'] as Map<String, dynamic>,
            )
          : null,
      pricingType: json['pricing_type']?.toString() ?? '',
      coverImage: json['cover_image']?.toString(),
    );
  }
}

class SlotItem {
  final int id;
  final String eventId;
  final String ticketId;
  final String pricingType;
  final String slotEnable;
  final String slotUniqueId;
  final String type;
  final String numberOfSeat;
  final String posX;
  final String posY;
  final String width;
  final String height;
  final String round;
  final String price;
  final String name;
  final String rotate;
  final String backgroundColor;
  final String? borderColor;
  final String fontSize;
  final String isDeactive;
  final int isBooked;
  final String createdAt;
  final String updatedAt;
  final String slotType;
  final String slotName;
  final List<SeatItem> filteredSeats;
  final List<SeatItem> seats;

  SlotItem({
    required this.id,
    required this.eventId,
    required this.ticketId,
    required this.pricingType,
    required this.slotEnable,
    required this.slotUniqueId,
    required this.type,
    required this.numberOfSeat,
    required this.posX,
    required this.posY,
    required this.width,
    required this.height,
    required this.round,
    required this.price,
    required this.name,
    required this.rotate,
    required this.backgroundColor,
    this.borderColor,
    required this.fontSize,
    required this.isDeactive,
    required this.isBooked,
    required this.createdAt,
    required this.updatedAt,
    required this.slotType,
    required this.slotName,
    required this.filteredSeats,
    required this.seats,
  });

  factory SlotItem.fromJson(Map<String, dynamic> json) {
    return SlotItem(
      id: json['id'] as int? ?? 0,
      eventId: json['event_id']?.toString() ?? '',
      ticketId: json['ticket_id']?.toString() ?? '',
      pricingType: json['pricing_type']?.toString() ?? '',
      slotEnable: json['slot_enable']?.toString() ?? '0',
      slotUniqueId: json['slot_unique_id']?.toString() ?? '',
      type: json['type']?.toString() ?? '',
      numberOfSeat: json['number_of_seat']?.toString() ?? '0',
      posX: json['pos_x']?.toString() ?? '0',
      posY: json['pos_y']?.toString() ?? '0',
      width: json['width']?.toString() ?? '0',
      height: json['height']?.toString() ?? '0',
      round: json['round']?.toString() ?? '0',
      price: json['price']?.toString() ?? '0.00',
      name: json['name']?.toString() ?? '',
      rotate: json['rotate']?.toString() ?? '0',
      backgroundColor: json['background_color']?.toString() ?? '#00e5b5',
      borderColor: json['border_color']?.toString(),
      fontSize: json['font_size']?.toString() ?? '14',
      isDeactive: json['is_deactive']?.toString() ?? '0',
      isBooked: int.tryParse(json['is_booked']?.toString() ?? '0') ?? 0,
      createdAt: json['created_at']?.toString() ?? '',
      updatedAt: json['updated_at']?.toString() ?? '',
      slotType: json['slot_type']?.toString() ?? '',
      slotName: json['slot_name']?.toString() ?? '',
      filteredSeats: (json['filtered_seats'] as List<dynamic>? ?? [])
          .map((e) => SeatItem.fromJson(e as Map<String, dynamic>))
          .toList(),
      seats: (json['seats'] as List<dynamic>? ?? [])
          .map((e) => SeatItem.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

class SeatItem {
  final int id;
  final String slotId;
  final String name;
  final String price;
  final String isDeactive;
  final int isBooked;

  SeatItem({
    required this.id,
    required this.slotId,
    required this.name,
    required this.price,
    required this.isDeactive,
    required this.isBooked,
  });

  factory SeatItem.fromJson(Map<String, dynamic> json) {
    return SeatItem(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      slotId: json['slot_id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      price: json['price']?.toString() ?? '0.00',
      isDeactive: json['is_deactive']?.toString() ?? '0',
      isBooked: int.tryParse(json['is_booked']?.toString() ?? '0') ?? 0,
    );
  }
}

class EventContentData {
  final int id;
  final String eventId;
  final String? countryId;
  final String? cityId;
  final String? stateId;
  final String languageId;
  final String eventCategoryId;
  final String title;
  final String slug;
  final String? description;
  final String? metaKeywords;
  final String? metaDescription;
  final String createdAt;
  final String updatedAt;
  final String? address;
  final String? zipCode;
  final String? refundPolicy;

  EventContentData({
    required this.id,
    required this.eventId,
    this.countryId,
    this.cityId,
    this.stateId,
    required this.languageId,
    required this.eventCategoryId,
    required this.title,
    required this.slug,
    this.description,
    this.metaKeywords,
    this.metaDescription,
    required this.createdAt,
    required this.updatedAt,
    this.address,
    this.zipCode,
    this.refundPolicy,
  });

  factory EventContentData.fromJson(Map<String, dynamic> json) {
    return EventContentData(
      id: json['id'] as int? ?? 0,
      eventId: json['event_id']?.toString() ?? '',
      countryId: json['country_id']?.toString(),
      cityId: json['city_id']?.toString(),
      stateId: json['state_id']?.toString(),
      languageId: json['language_id']?.toString() ?? '',
      eventCategoryId: json['event_category_id']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
      description: json['description']?.toString(),
      metaKeywords: json['meta_keywords']?.toString(),
      metaDescription: json['meta_description']?.toString(),
      createdAt: json['created_at']?.toString() ?? '',
      updatedAt: json['updated_at']?.toString() ?? '',
      address: json['address']?.toString(),
      zipCode: json['zip_code']?.toString(),
      refundPolicy: json['refund_policy']?.toString(),
    );
  }
}

class TicketContentData {
  final int id;
  final String languageId;
  final String ticketId;
  final String title;
  final String? description;
  final String createdAt;
  final String updatedAt;

  TicketContentData({
    required this.id,
    required this.languageId,
    required this.ticketId,
    required this.title,
    this.description,
    required this.createdAt,
    required this.updatedAt,
  });

  factory TicketContentData.fromJson(Map<String, dynamic> json) {
    return TicketContentData(
      id: json['id'] as int? ?? 0,
      languageId: json['language_id']?.toString() ?? '',
      ticketId: json['ticket_id']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      description: json['description']?.toString(),
      createdAt: json['created_at']?.toString() ?? '',
      updatedAt: json['updated_at']?.toString() ?? '',
    );
  }
}
