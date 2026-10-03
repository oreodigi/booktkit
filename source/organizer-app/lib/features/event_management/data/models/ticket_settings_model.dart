class TicketSettingsModel {
  final String ticketImage;
  final String ticketLogo;
  final String instructions;

  TicketSettingsModel({
    required this.ticketImage,
    required this.ticketLogo,
    required this.instructions,
  });

  factory TicketSettingsModel.fromJson(Map<String, dynamic> json) {
    final event =
        (json['data'] as Map<String, dynamic>?)?['event']
            as Map<String, dynamic>? ??
        {};
    return TicketSettingsModel(
      ticketImage: event['ticket_image']?.toString() ?? '',
      ticketLogo: event['ticket_logo']?.toString() ?? '',
      instructions: event['instructions']?.toString() ?? '',
    );
  }
}
