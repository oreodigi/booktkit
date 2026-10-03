class SupportTicketResponse {
  final bool success;
  final SupportTicketPagination data;

  SupportTicketResponse({required this.success, required this.data});

  factory SupportTicketResponse.fromJson(Map<String, dynamic> json) {
    return SupportTicketResponse(
      success: json['success'] ?? false,
      data: SupportTicketPagination.fromJson(json['data']),
    );
  }
}

class CreateTicketResponse {
  final bool success;
  final String message;

  CreateTicketResponse({required this.success, required this.message});

  factory CreateTicketResponse.fromJson(Map<String, dynamic> json) {
    return CreateTicketResponse(
      success: json['success'] ?? false,
      message: json['message'] ?? '',
    );
  }
}

class SupportTicketPagination {
  final int currentPage;
  final List<SupportTicket> tickets;
  final String? nextPageUrl;
  final String? prevPageUrl;
  final int lastPage;
  final int perPage;
  final int total;
  final List<PaginationLink> links;

  SupportTicketPagination({
    required this.currentPage,
    required this.tickets,
    required this.nextPageUrl,
    required this.prevPageUrl,
    required this.lastPage,
    required this.perPage,
    required this.total,
    required this.links,
  });

  bool get hasNextPage => nextPageUrl != null;

  factory SupportTicketPagination.fromJson(Map<String, dynamic> json) {
    return SupportTicketPagination(
      currentPage: json['current_page'] ?? 1,
      tickets: (json['data'] as List<dynamic>)
          .map((e) => SupportTicket.fromJson(e))
          .toList(),
      nextPageUrl: json['next_page_url'],
      prevPageUrl: json['prev_page_url'],
      lastPage: json['last_page'] ?? 1,
      perPage: int.tryParse(json['per_page'].toString()) ?? 10,
      total: json['total'] ?? 0,
      links: (json['links'] as List<dynamic>)
          .map((e) => PaginationLink.fromJson(e))
          .toList(),
    );
  }
}

class SupportTicket {
  final int id;
  final String userId;
  final String userType;
  final String? adminId;
  final String? ticketNumber;
  final String email;
  final String subject;
  final String description;
  final String? attachment;
  final String status;
  final DateTime createdAt;
  final DateTime updatedAt;
  final DateTime? lastMessage;

  SupportTicket({
    required this.id,
    required this.userId,
    required this.userType,
    this.adminId,
    this.ticketNumber,
    required this.email,
    required this.subject,
    required this.description,
    this.attachment,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
    this.lastMessage,
  });

  factory SupportTicket.fromJson(Map<String, dynamic> json) {
    return SupportTicket(
      id: json['id'],
      userId: json['user_id'],
      userType: json['user_type'],
      adminId: json['admin_id']?.toString(),
      ticketNumber: json['ticket_number']?.toString(),
      email: json['email'],
      subject: json['subject'],
      description: json['description'],
      attachment: json['attachment'],
      status: json['status'].toString(),
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
      lastMessage: json['last_message'] != null
          ? DateTime.tryParse(json['last_message'])
          : null,
    );
  }

  /// Optional helper
  bool get isClosed => status == "2";
  bool get isOpen => status == "1";
}

class PaginationLink {
  final String? url;
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
      label: json['label'],
      active: json['active'] ?? false,
    );
  }
}

// ─────────────────────────── Ticket Detail Models ────────────────────────────

class TicketDetailResponse {
  final bool success;
  final TicketDetail ticket;

  TicketDetailResponse({required this.success, required this.ticket});

  factory TicketDetailResponse.fromJson(Map<String, dynamic> json) {
    return TicketDetailResponse(
      success: json['success'] ?? false,
      ticket: TicketDetail.fromJson(json['data']['ticket']),
    );
  }
}

class TicketDetail {
  final int id;
  final String userId;
  final String userType;
  final String? adminId;
  final String? ticketNumber;
  final String email;
  final String subject;
  final String description;
  final String? attachment;
  final String status;
  final DateTime createdAt;
  final DateTime updatedAt;
  final DateTime? lastMessage;
  final List<TicketMessage> messages;

  TicketDetail({
    required this.id,
    required this.userId,
    required this.userType,
    this.adminId,
    this.ticketNumber,
    required this.email,
    required this.subject,
    required this.description,
    this.attachment,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
    this.lastMessage,
    required this.messages,
  });

  bool get isClosed => status == '2';
  bool get isOpen => status == '1';

  factory TicketDetail.fromJson(Map<String, dynamic> json) {
    return TicketDetail(
      id: json['id'],
      userId: json['user_id'].toString(),
      userType: json['user_type'],
      adminId: json['admin_id']?.toString(),
      ticketNumber: json['ticket_number']?.toString(),
      email: json['email'],
      subject: json['subject'],
      description: json['description'],
      attachment: json['attachment'],
      status: json['status'].toString(),
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
      lastMessage: json['last_message'] != null
          ? DateTime.tryParse(json['last_message'])
          : null,
      messages: (json['messages'] as List<dynamic>)
          .map((e) => TicketMessage.fromJson(e))
          .toList(),
    );
  }
}

class TicketMessage {
  final int id;
  final String userId;

  /// type "2" = admin/support, "3" = organizer
  final String type;
  final String supportTicketId;
  final String reply;
  final String? file;
  final DateTime createdAt;
  final DateTime updatedAt;

  TicketMessage({
    required this.id,
    required this.userId,
    required this.type,
    required this.supportTicketId,
    required this.reply,
    this.file,
    required this.createdAt,
    required this.updatedAt,
  });

  /// true when the message was sent by admin/support
  bool get isAdmin => type == '2';

  factory TicketMessage.fromJson(Map<String, dynamic> json) {
    return TicketMessage(
      id: json['id'],
      userId: json['user_id'].toString(),
      type: json['type'].toString(),
      supportTicketId: json['support_ticket_id'].toString(),
      reply: json['reply'] ?? '',
      file: json['file'],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }
}
