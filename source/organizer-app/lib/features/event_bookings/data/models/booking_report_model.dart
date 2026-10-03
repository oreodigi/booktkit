class BookingReportResponse {
  final List<BookingReportItem> bookings;
  final int currentPage;
  final int lastPage;
  final String? nextPageUrl;
  final List<PaymentMethodItem> onPms;
  final List<PaymentMethodItem> offPms;

  BookingReportResponse({
    required this.bookings,
    required this.currentPage,
    required this.lastPage,
    this.nextPageUrl,
    required this.onPms,
    required this.offPms,
  });

  factory BookingReportResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] ?? {};
    final bookingsRaw = data['bookings'];

    // API returns [] (empty List) when there are no results,
    // and a pagination Map when there are results.
    final Map<String, dynamic> pagination =
        bookingsRaw is Map<String, dynamic> ? bookingsRaw : {};

    final items = (pagination['data'] as List? ?? [])
        .map((e) => BookingReportItem.fromJson(e as Map<String, dynamic>))
        .toList();

    final on = (data['onPms'] as List? ?? [])
        .map((e) => PaymentMethodItem.fromJson(e as Map<String, dynamic>))
        .toList();
    final off = (data['offPms'] as List? ?? [])
        .map((e) => PaymentMethodItem.fromJson(e as Map<String, dynamic>))
        .toList();

    return BookingReportResponse(
      bookings: items,
      currentPage: pagination['current_page'] as int? ?? 1,
      lastPage: pagination['last_page'] as int? ?? 1,
      nextPageUrl: pagination['next_page_url'] as String?,
      onPms: on,
      offPms: off,
    );
  }
}

class BookingReportItem {
  final int id;
  final String bookingId;
  final String? title;
  final String? fname;
  final String? lname;
  final String? email;
  final String? phone;
  final String? city;
  final String? state;
  final String? country;
  final String? zipCode;
  final String? address;
  final String? paymentMethod;
  final String? gatewayType;
  final String paymentStatus;
  final String? currencySymbol;
  final String price;
  final String tax;
  final String commission;
  final String discount;
  final String earlyBirdDiscount;
  final String quantity;
  final String? eventDate;
  final String? createdAt;

  BookingReportItem({
    required this.id,
    required this.bookingId,
    this.title,
    this.fname,
    this.lname,
    this.email,
    this.phone,
    this.city,
    this.state,
    this.country,
    this.zipCode,
    this.address,
    this.paymentMethod,
    this.gatewayType,
    required this.paymentStatus,
    this.currencySymbol,
    required this.price,
    required this.tax,
    required this.commission,
    required this.discount,
    required this.earlyBirdDiscount,
    required this.quantity,
    this.eventDate,
    this.createdAt,
  });

  factory BookingReportItem.fromJson(Map<String, dynamic> json) {
    return BookingReportItem(
      id: json['id'] ?? 0,
      bookingId: json['booking_id']?.toString() ?? '',
      title: json['title'],
      fname: json['fname'],
      lname: json['lname'],
      email: json['email'],
      phone: json['phone'],
      city: json['city'],
      state: json['state'],
      country: json['country'],
      zipCode: json['zip_code'],
      address: json['address'],
      paymentMethod: json['paymentMethod'],
      gatewayType: json['gatewayType'],
      paymentStatus: json['paymentStatus']?.toString() ?? '',
      currencySymbol: json['currencySymbol'],
      price: json['price']?.toString() ?? '0.00',
      tax: json['tax']?.toString() ?? '0.00',
      commission: json['commission']?.toString() ?? '0.00',
      discount: json['discount']?.toString() ?? '0',
      earlyBirdDiscount: json['early_bird_discount']?.toString() ?? '0',
      quantity: json['quantity']?.toString() ?? '0',
      eventDate: json['event_date'],
      createdAt: json['created_at'],
    );
  }

  String get customerName {
    final f = fname ?? '';
    final l = lname ?? '';
    final full = '$f $l'.trim();
    return full.isNotEmpty ? full : 'Guest User';
  }

  String get formattedTotal {
    final sym = currencySymbol ?? '';
    final p = double.tryParse(price) ?? 0;
    final t = double.tryParse(tax) ?? 0;
    final d = double.tryParse(discount) ?? 0;
    final eb = double.tryParse(earlyBirdDiscount) ?? 0;
    final total = p + t - d - eb;
    return '$sym${total.toStringAsFixed(2)}';
  }

  String get formattedDiscount {
    final d = double.tryParse(discount) ?? 0;
    final eb = double.tryParse(earlyBirdDiscount) ?? 0;
    return (d + eb).toStringAsFixed(2);
  }
}

class PaymentMethodItem {
  final int id;
  final String name;
  final String? keyword;

  PaymentMethodItem({required this.id, required this.name, this.keyword});

  factory PaymentMethodItem.fromJson(Map<String, dynamic> json) {
    return PaymentMethodItem(
      id: json['id'] ?? 0,
      name: json['name']?.toString() ?? '',
      keyword: json['keyword'],
    );
  }
}
