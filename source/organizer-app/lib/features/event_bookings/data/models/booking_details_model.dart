import 'dart:convert';

class BookingDetailsResponse {
  final BookingDetail booking;
  final EventContent eventContent;

  BookingDetailsResponse({required this.booking, required this.eventContent});

  factory BookingDetailsResponse.fromJson(Map<String, dynamic> json) {
    // Use safe casts — the API may return data as null or a different type
    final raw = json['data'];
    final data = raw is Map<String, dynamic> ? raw : <String, dynamic>{};

    final bookingRaw = data['booking'];
    final eventRaw = data['eventContent'];

    return BookingDetailsResponse(
      booking: BookingDetail.fromJson(
          bookingRaw is Map<String, dynamic> ? bookingRaw : {}),
      eventContent: EventContent.fromJson(
          eventRaw is Map<String, dynamic> ? eventRaw : {}),
    );
  }
}

class BookingDetail {
  final int id;
  final String bookingId;
  final String? fname;
  final String? lname;
  final String? email;
  final String? phone;
  final String? country;
  final String? state;
  final String? city;
  final String? zipCode;
  final String? address;
  final String price;
  final String quantity;
  final String discount;
  final String tax;
  final String commission;
  final String earlyBirdDiscount;
  final String? currencyText;
  final String? currencySymbol;
  final String? paymentMethod;
  final String? gatewayType;
  final String paymentStatus;
  final String? invoice;
  final String? eventDate;
  final String? createdAt;
  final String? taxPercentage;
  final String? commissionPercentage;
  final String? scanStatus;
  final List<TicketVariation> variations;

  BookingDetail({
    required this.id,
    required this.bookingId,
    this.fname,
    this.lname,
    this.email,
    this.phone,
    this.country,
    this.state,
    this.city,
    this.zipCode,
    this.address,
    required this.price,
    required this.quantity,
    required this.discount,
    required this.tax,
    required this.commission,
    required this.earlyBirdDiscount,
    this.currencyText,
    this.currencySymbol,
    this.paymentMethod,
    this.gatewayType,
    required this.paymentStatus,
    this.invoice,
    this.eventDate,
    this.createdAt,
    this.taxPercentage,
    this.commissionPercentage,
    this.scanStatus,
    required this.variations,
  });

  factory BookingDetail.fromJson(Map<String, dynamic> json) {
    List<TicketVariation> vars = [];
    try {
      final raw = json['variation'];
      if (raw != null && raw is String && raw.isNotEmpty) {
        final decoded = jsonDecode(raw) as List;
        vars = decoded
            .map((e) => TicketVariation.fromJson(e as Map<String, dynamic>))
            .toList();
      }
    } catch (_) {}

    return BookingDetail(
      id: json['id'] as int? ?? 0,
      bookingId: json['booking_id']?.toString() ?? '',
      fname: json['fname'],
      lname: json['lname'],
      email: json['email'],
      phone: json['phone'],
      country: json['country'],
      state: json['state'],
      city: json['city'],
      zipCode: json['zip_code'],
      address: json['address'],
      price: json['price']?.toString() ?? '0',
      quantity: json['quantity']?.toString() ?? '0',
      discount: json['discount']?.toString() ?? '0',
      tax: json['tax']?.toString() ?? '0',
      commission: json['commission']?.toString() ?? '0',
      earlyBirdDiscount: json['early_bird_discount']?.toString() ?? '0',
      currencyText: json['currencyText'],
      currencySymbol: json['currencySymbol'],
      paymentMethod: json['paymentMethod'],
      gatewayType: json['gatewayType'],
      paymentStatus: json['paymentStatus']?.toString() ?? '',
      invoice: json['invoice'],
      eventDate: json['event_date'],
      createdAt: json['created_at'],
      taxPercentage: json['tax_percentage']?.toString(),
      commissionPercentage: json['commission_percentage']?.toString(),
      scanStatus: json['scan_status']?.toString(),
      variations: vars,
    );
  }

  String get customerName {
    final f = fname ?? '';
    final l = lname ?? '';
    final full = '$f $l'.trim();
    return full.isNotEmpty ? full : 'Guest';
  }

  String get sym => currencySymbol ?? '';
  String get cur => currencyText ?? '';

  double get priceD => double.tryParse(price) ?? 0;
  double get taxD => double.tryParse(tax) ?? 0;
  double get discountD => double.tryParse(discount) ?? 0;
  double get earlyBirdD => double.tryParse(earlyBirdDiscount) ?? 0;
  double get commissionD => double.tryParse(commission) ?? 0;

  double get customerPaid => priceD + taxD - discountD - earlyBirdD;
  double get receivedByOrg => customerPaid - commissionD;

  int get totalTickets => int.tryParse(quantity) ?? 0;
  int get scannedTickets => int.tryParse(scanStatus ?? '0') ?? 0;
}

class TicketVariation {
  final int ticketId;
  final String name;
  final int qty;
  final double price;
  final double earlyBirdDiscount;
  final int scanStatus;
  final String uniqueId;

  TicketVariation({
    required this.ticketId,
    required this.name,
    required this.qty,
    required this.price,
    required this.earlyBirdDiscount,
    required this.scanStatus,
    required this.uniqueId,
  });

  factory TicketVariation.fromJson(Map<String, dynamic> json) {
    return TicketVariation(
      ticketId: json['ticket_id'] as int? ?? 0,
      name: json['name']?.toString() ?? '',
      qty: json['qty'] as int? ?? 1,
      price: double.tryParse(json['price']?.toString() ?? '0') ?? 0,
      earlyBirdDiscount:
          double.tryParse(json['early_bird_dicount']?.toString() ?? '0') ?? 0,
      scanStatus: json['scan_status'] as int? ?? 0,
      uniqueId: json['unique_id']?.toString() ?? '',
    );
  }

  double get effectivePrice => price - earlyBirdDiscount;
}

class EventContent {
  final int id;
  final String? title;
  final String? description;
  final String? address;

  EventContent({required this.id, this.title, this.description, this.address});

  factory EventContent.fromJson(Map<String, dynamic> json) {
    return EventContent(
      id: json['id'] as int? ?? 0,
      title: json['title'],
      description: json['description'],
      address: json['address'],
    );
  }
}
