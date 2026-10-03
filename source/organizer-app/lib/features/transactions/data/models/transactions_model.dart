/// Transactions Response Model
class TransactionsResponse {
  final bool success;
  final TransactionsData data;

  TransactionsResponse({
    required this.success,
    required this.data,
  });

  factory TransactionsResponse.fromJson(Map<String, dynamic> json) {
    return TransactionsResponse(
      success: json['success'] == true,
      data: TransactionsData.fromJson(json['data'] ?? {}),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'success': success,
      'data': data.toJson(),
    };
  }
}

class TransactionsData {
  final PaginatedTransactions transactions;

  TransactionsData({required this.transactions});

  factory TransactionsData.fromJson(Map<String, dynamic> json) {
    return TransactionsData(
      transactions: PaginatedTransactions.fromJson(json['transactions'] ?? {}),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'transactions': transactions.toJson(),
    };
  }
}

/// Pagination Wrapper
class PaginatedTransactions {
  final int currentPage;
  final List<TransactionItem> data;
  final String firstPageUrl;
  final int from;
  final int lastPage;
  final String lastPageUrl;
  final List<PaginationLink> links;
  final String? nextPageUrl;
  final String path;
  final int perPage;
  final String? prevPageUrl;
  final int to;
  final int total;

  PaginatedTransactions({
    required this.currentPage,
    required this.data,
    required this.firstPageUrl,
    required this.from,
    required this.lastPage,
    required this.lastPageUrl,
    required this.links,
    required this.nextPageUrl,
    required this.path,
    required this.perPage,
    required this.prevPageUrl,
    required this.to,
    required this.total,
  });

  factory PaginatedTransactions.fromJson(Map<String, dynamic> json) {
    return PaginatedTransactions(
      currentPage: json['current_page'] is int
          ? json['current_page']
          : int.tryParse(json['current_page']?.toString() ?? '') ?? 0,
      data: (json['data'] as List? ?? [])
          .map((e) => TransactionItem.fromJson(e as Map<String, dynamic>))
          .toList(),
      firstPageUrl: json['first_page_url'] ?? '',
      from: json['from'] is int
          ? json['from']
          : int.tryParse(json['from']?.toString() ?? '') ?? 0,
      lastPage: json['last_page'] is int
          ? json['last_page']
          : int.tryParse(json['last_page']?.toString() ?? '') ?? 0,
      lastPageUrl: json['last_page_url'] ?? '',
      links: (json['links'] as List? ?? [])
          .map((e) => PaginationLink.fromJson(e as Map<String, dynamic>))
          .toList(),
      nextPageUrl: json['next_page_url'],
      path: json['path'] ?? '',
      perPage: json['per_page'] is int
          ? json['per_page']
          : int.tryParse(json['per_page']?.toString() ?? '') ?? 0,
      prevPageUrl: json['prev_page_url'],
      to: json['to'] is int ? json['to'] : int.tryParse(json['to']?.toString() ?? '') ?? 0,
      total: json['total'] is int
          ? json['total']
          : int.tryParse(json['total']?.toString() ?? '') ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'current_page': currentPage,
      'data': data.map((e) => e.toJson()).toList(),
      'first_page_url': firstPageUrl,
      'from': from,
      'last_page': lastPage,
      'last_page_url': lastPageUrl,
      'links': links.map((e) => e.toJson()).toList(),
      'next_page_url': nextPageUrl,
      'path': path,
      'per_page': perPage,
      'prev_page_url': prevPageUrl,
      'to': to,
      'total': total,
    };
  }
  bool get hasNextPage => nextPageUrl != null;
}

/// Single Transaction Item
class TransactionItem {
  final int id;
  final String transcationId;
  final String bookingId;
  final String transcationType;
  final String? customerId; // nullable in response
  final String organizerId;
  final String paymentStatus;
  final String paymentMethod;
  final String grandTotal;
  final String commission;
  final String tax;
  final String preBalance;
  final String afterBalance;
  final String gatewayType;
  final String currencySymbol;
  final String currencySymbolPosition;
  final String createdAt;
  final String updatedAt;

  TransactionItem({
    required this.id,
    required this.transcationId,
    required this.bookingId,
    required this.transcationType,
    required this.customerId,
    required this.organizerId,
    required this.paymentStatus,
    required this.paymentMethod,
    required this.grandTotal,
    required this.commission,
    required this.tax,
    required this.preBalance,
    required this.afterBalance,
    required this.gatewayType,
    required this.currencySymbol,
    required this.currencySymbolPosition,
    required this.createdAt,
    required this.updatedAt,
  });

  factory TransactionItem.fromJson(Map<String, dynamic> json) {
    return TransactionItem(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '') ?? 0,
      transcationId: json['transcation_id'] ?? '',
      bookingId: json['booking_id']?.toString() ?? '',
      transcationType: json['transcation_type']?.toString() ?? '',
      customerId: json['customer_id']?.toString(), // can be null
      organizerId: json['organizer_id']?.toString() ?? '',
      paymentStatus: json['payment_status']?.toString() ?? '',
      paymentMethod: json['payment_method'] ?? '',
      grandTotal: json['grand_total']?.toString() ?? '0.00',
      commission: json['commission']?.toString() ?? '0.00',
      tax: json['tax']?.toString() ?? '0.00',
      preBalance: json['pre_balance']?.toString() ?? '0.00',
      afterBalance: json['after_balance']?.toString() ?? '0.00',
      gatewayType: json['gateway_type'] ?? '',
      currencySymbol: json['currency_symbol'] ?? '',
      currencySymbolPosition: json['currency_symbol_position'] ?? '',
      createdAt: json['created_at'] ?? '',
      updatedAt: json['updated_at'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'transcation_id': transcationId,
      'booking_id': bookingId,
      'transcation_type': transcationType,
      'customer_id': customerId,
      'organizer_id': organizerId,
      'payment_status': paymentStatus,
      'payment_method': paymentMethod,
      'grand_total': grandTotal,
      'commission': commission,
      'tax': tax,
      'pre_balance': preBalance,
      'after_balance': afterBalance,
      'gateway_type': gatewayType,
      'currency_symbol': currencySymbol,
      'currency_symbol_position': currencySymbolPosition,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }
}

/// Pagination Link Item
class PaginationLink {
  final String? url; // can be null for "Previous"
  final String label;
  final bool active;

  PaginationLink({
    required this.url,
    required this.label,
    required this.active,
  });

  factory PaginationLink.fromJson(Map<String, dynamic> json) {
    return PaginationLink(
      url: json['url'],
      label: json['label'] ?? '',
      active: json['active'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'url': url,
      'label': label,
      'active': active,
    };
  }

}
