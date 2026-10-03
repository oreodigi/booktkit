class EventBookingResponse {
  final bool success;
  final EventBookingPagination data;

  EventBookingResponse({required this.success, required this.data});

  factory EventBookingResponse.fromJson(Map<String, dynamic> json) {
    return EventBookingResponse(
      success: json['success'] ?? false,
      data: EventBookingPagination.fromJson(json['data']['bookings']),
    );
  }
}

class EventBookingPagination {
  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;
  final String path;
  final String? prevPageUrl;
  final String? nextPageUrl;
  final List<EventBooking> bookings;

  EventBookingPagination({
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
    required this.path,
    this.prevPageUrl,
    this.nextPageUrl,
    required this.bookings,
  });

  factory EventBookingPagination.fromJson(Map<String, dynamic> json) {
    return EventBookingPagination(
      currentPage: json['current_page'] ?? 1,
      lastPage: json['last_page'] ?? 1,
      perPage: json['per_page'] ?? 10,
      total: json['total'] ?? 0,
      path: json['path'] ?? '',
      prevPageUrl: json['prev_page_url'],
      nextPageUrl: json['next_page_url'],
      bookings:
          (json['data'] as List?)
              ?.map((item) => EventBooking.fromJson(item))
              .toList() ??
          [],
    );
  }

  bool get hasNextPage => currentPage < lastPage;
}

class EventBooking {
  final int id;
  final String eventTitle;
  final String? customerId;
  final String? bookingIdString;
  final String? eventId;
  final String? organizerId;
  final String? fname;
  final String? lname;
  final String? email;
  final String? phone;
  final String? price;
  final String? quantity;
  final String paymentStatus;
  final String? gatewayType;
  final String? paymentMethod;
  final String? currencySymbol;
  final String? organizerReceived;
  final String? createdAt;
  final String? eventDate;
  final int scannedCount;
  final int totalTickets;

  // Additional fields for displaying
  final String? customerPaidStr;
  final String? orgReceivedStr;

  EventBooking({
    required this.id,
    required this.eventTitle,
    this.customerId,
    this.bookingIdString,
    this.eventId,
    this.organizerId,
    this.fname,
    this.lname,
    this.email,
    this.phone,
    this.price,
    this.quantity,
    required this.paymentStatus,
    this.gatewayType,
    this.paymentMethod,
    this.currencySymbol,
    this.organizerReceived,
    this.createdAt,
    this.eventDate,
    required this.scannedCount,
    required this.totalTickets,
    this.customerPaidStr,
    this.orgReceivedStr,
  });

  factory EventBooking.fromJson(Map<String, dynamic> json) {
    final qtyStr = json['quantity']?.toString() ?? '0';
    final q = int.tryParse(qtyStr) ?? 0;

    final scannedStr = json['scan_status']?.toString() ?? '0';
    final scanned = int.tryParse(scannedStr) ?? 0;

    final sym = json['currencySymbol']?.toString() ?? '';
    final priceStr = json['price'] ?? '0.00';
    final commissionStr = json['commission'] ?? '0.00';
    final taxStr = json['tax'] ?? '0.00';
    final discountStr = json['discount'] ?? '0.00';

    // Quick parse for basic numbers
    final priceVal = double.tryParse(priceStr) ?? 0.0;
    final commissionVal = double.tryParse(commissionStr) ?? 0.0;
    final taxVal = double.tryParse(taxStr) ?? 0.0;
    final discountVal = double.tryParse(discountStr) ?? 0.0;

    // Calculate customer paid
    final cp = priceVal + taxVal - discountVal;

    // Calculate organizer received
    final or = cp - commissionVal;

    return EventBooking(
      id: json['id'] ?? 0,
      eventTitle: json['title'] ?? '',
      customerId: json['customer_id']?.toString(),
      bookingIdString: json['booking_id']?.toString(),
      eventId: json['event_id']?.toString(),
      organizerId: json['organizer_id']?.toString(),
      fname: json['fname'],
      lname: json['lname'],
      email: json['email'],
      phone: json['phone']?.toString(),
      price: priceStr,
      quantity: qtyStr,
      paymentStatus: json['paymentStatus'] ?? 'pending',
      gatewayType: json['gatewayType'],
      paymentMethod: json['paymentMethod'],
      currencySymbol: sym,
      organizerReceived: json['commission']?.toString(),
      createdAt: json['created_at'],
      eventDate: json['event_date'],
      scannedCount: scanned,
      totalTickets: q,
      customerPaidStr: '$sym${cp.toStringAsFixed(2)}',
      orgReceivedStr: '$sym${or.toStringAsFixed(2)}',
    );
  }

  String get customerName {
    final first = fname ?? '';
    final last = lname ?? '';
    final full = '$first $last'.trim();
    return full.isNotEmpty ? full : 'Guest User';
  }
}
