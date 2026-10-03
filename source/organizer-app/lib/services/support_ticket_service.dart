import 'package:dio/dio.dart';
import 'package:booktkit_organizer/app/urls.dart';
import 'package:booktkit_organizer/features/support_tickets/data/models/support_ticket_model.dart';
import 'package:booktkit_organizer/services/api_client.dart';

/// Support Tickets Service
class SupportTicketsService {
  final ApiClient _apiClient = ApiClient();

  /// Get support tickets with optional status filter
  Future<SupportTicketResponse> getTickets({
    String? status,
    int page = 1,
  }) async {
    try {
      final queryParams = {
        'page': page.toString(),
        'per_page': '10',
        'status': ?status,
      };
      final response = await _apiClient.get(
        Urls.getTickets,
        queryParameters: queryParams,
      );
      return SupportTicketResponse.fromJson(response.data);
    } catch (e) {
      throw Exception('Failed to fetch support tickets: $e');
    }
  }

  /// Get ticket detail with messages by ticket ID
  Future<TicketDetailResponse> getTicketDetail(int ticketId) async {
    try {
      final response = await _apiClient.get(
        '${Urls.getTicketDetail}/$ticketId',
      );
      return TicketDetailResponse.fromJson(response.data);
    } catch (e) {
      throw Exception('Failed to fetch ticket details: $e');
    }
  }

  /// Reply to a ticket (multipart: reply + optional file)
  Future<Map<String, dynamic>> replyToTicket({
    required int ticketId,
    required String reply,
    String? filePath,
  }) async {
    try {
      final formData = FormData.fromMap({
        'reply': reply,
        if (filePath != null)
          'file': await MultipartFile.fromFile(
            filePath,
            filename: filePath.split(RegExp(r'[/\\]')).last,
          ),
      });

      final response = await _apiClient.post(
        '${Urls.ticketReply}/$ticketId',
        data: formData,
      );
      return response.data;
    } catch (e) {
      throw Exception('Failed to send reply: $e');
    }
  }

  /// Create a new support ticket
  Future<CreateTicketResponse> createTicket({
    required String email,
    required String subject,
    required String message,
    String? attachmentPath,
    String? priority,
  }) async {
    try {
      FormData formData = FormData.fromMap({
        'email': email,
        'subject': subject,
        'user_type': 'organizer',
        'description': message,
        'priority': ?priority,
      });

      // Add file if provided
      if (attachmentPath != null) {
        formData.files.add(
          MapEntry(
            'attachment',
            await MultipartFile.fromFile(
              attachmentPath,
              filename: attachmentPath.split(RegExp(r'[/\\]')).last,
            ),
          ),
        );
      }

      final response = await _apiClient.post(Urls.storeSupportTicket, data: formData);

      if (response.statusCode == 200 || response.statusCode == 201) {
        return CreateTicketResponse.fromJson(response.data);
      } else {
        throw Exception('Failed to create ticket: ${response.statusMessage}');
      }
    } on DioException catch (e) {
      if (e.response != null) {
        throw Exception(
          e.response?.data['message'] ?? 'Failed to create ticket',
        );
      } else {
        throw Exception('Network error: ${e.message}');
      }
    } catch (e) {
      throw Exception('Failed to create ticket: $e');
    }
  }

  /// Delete ticket
  Future<Map<String, dynamic>> deleteTicket(int ticketId) async {
    try {
      final response = await _apiClient.post('${Urls.deleteTicket}/$ticketId');
      return response.data;
    } on DioException catch (e) {
      if (e.response != null) {
        throw Exception(
          e.response?.data['message'] ?? 'Failed to delete ticket',
        );
      } else {
        throw Exception('Network error: ${e.message}');
      }
    } catch (e) {
      throw Exception('Failed to delete ticket: $e');
    }
  }
}
