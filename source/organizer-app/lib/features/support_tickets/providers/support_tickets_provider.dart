import 'package:booktkit_organizer/features/support_tickets/data/models/support_ticket_model.dart';
import 'package:booktkit_organizer/services/support_ticket_service.dart';
import 'package:booktkit_organizer/utils/app_logger.dart';
import 'package:flutter/foundation.dart';

/// Support Tickets Provider
class SupportTicketsProvider with ChangeNotifier {
  final SupportTicketsService _ticketsService = SupportTicketsService();

  bool _isLoading = false;
  bool _isLoadingMore = false;
  String? _errorMessage;
  List<SupportTicket> _tickets = [];

  // Pagination state
  int _currentPage = 1;
  bool _hasMore = true;

  // Detail state
  TicketDetail? _ticketDetail;
  bool _isDetailLoading = false;
  String? _detailError;

  bool _isCreating = false;

  bool get isLoading => _isLoading;
  bool get isLoadingMore => _isLoadingMore;
  bool get isCreating => _isCreating;
  String? get errorMessage => _errorMessage;
  List<SupportTicket> get tickets => _tickets;
  bool get hasMore => _hasMore;

  TicketDetail? get ticketDetail => _ticketDetail;
  bool get isDetailLoading => _isDetailLoading;
  String? get detailError => _detailError;

  /// Fetch support tickets with optional status filter
  Future<void> fetchTickets({String? status, bool refresh = false}) async {
    if (refresh) {
      _currentPage = 1;
      _tickets = [];
      _hasMore = true;
    }

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _ticketsService.getTickets(
        status: status,
        page: _currentPage,
      );

      if (refresh) {
        _tickets = response.data.tickets;
      } else {
        _tickets.addAll(response.data.tickets);
      }

      _hasMore = response.data.hasNextPage;

      if (kDebugMode) {
        AppLogger.info(
          '📄 Loaded page $_currentPage: ${response.data.tickets.length} tickets',
        );
        AppLogger.info('📊 Total tickets now: ${_tickets.length}');
        AppLogger.info('🔄 Has more pages: $_hasMore');
      }

      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Fetch next page of tickets
  Future<void> fetchNextPage({String? status}) async {
    if (_isLoadingMore || !_hasMore) {
      if (kDebugMode) {
        AppLogger.w(
          '⚠️ Skipping fetchNextPage: isLoadingMore=$_isLoadingMore, hasMore=$_hasMore',
        );
      }
      return;
    }

    _isLoadingMore = true;
    notifyListeners();

    try {
      _currentPage++;
      if (kDebugMode) {
        AppLogger.info('⬇️ Fetching next page: $_currentPage');
      }

      final response = await _ticketsService.getTickets(
        status: status,
        page: _currentPage,
      );
      _tickets.addAll(response.data.tickets);
      _hasMore = response.data.hasNextPage;

      if (kDebugMode) {
        AppLogger.info(
          '✅ Page $_currentPage loaded: ${response.data.tickets.length} new tickets',
        );
        AppLogger.info('📊 Total tickets now: ${_tickets.length}');
        AppLogger.info('🔄 Has more pages: $_hasMore');
      }

      _isLoadingMore = false;
      notifyListeners();
    } catch (e) {
      if (kDebugMode) {
        AppLogger.e('❌ Error loading page $_currentPage: $e');
      }
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _currentPage--;
      _isLoadingMore = false;
      notifyListeners();
    }
  }

  /// Create a new support ticket
  Future<CreateTicketResponse?> createTicket({
    required String email,
    required String subject,
    required String message,
    String? attachmentPath,
  }) async {
    _isCreating = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _ticketsService.createTicket(
        email: email,
        subject: subject,
        message: message,
        attachmentPath: attachmentPath,
      );

      _isCreating = false;
      notifyListeners();

      // Refresh tickets list after successful creation
      if (response.success) {
        await fetchTickets(refresh: true);
      }

      return response;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isCreating = false;
      notifyListeners();
      return null;
    }
  }

  /// Delete ticket
  Future<bool> deleteTicket(int ticketId) async {
    _isLoading = true; // Use global loading state so the list indicates loading
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _ticketsService.deleteTicket(ticketId);

      if (response['success'] == true) {
        // Refresh tickets list
        await fetchTickets(refresh: true);
        return true;
      }
      return false;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  /// Clear error message
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }

  /// Fetch full ticket detail (with messages) by ID
  Future<void> fetchTicketDetail(int ticketId) async {
    _isDetailLoading = true;
    _detailError = null;
    _ticketDetail = null;
    notifyListeners();

    try {
      final response = await _ticketsService.getTicketDetail(ticketId);
      _ticketDetail = response.ticket;
    } catch (e) {
      _detailError = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isDetailLoading = false;
      notifyListeners();
    }
  }

  // Reply state
  bool _isSending = false;
  String? _sendError;

  bool get isSending => _isSending;
  String? get sendError => _sendError;

  /// Send a reply to a ticket (with optional file attachment)
  Future<bool> replyToTicket({
    required int ticketId,
    required String reply,
    String? filePath,
  }) async {
    _isSending = true;
    _sendError = null;
    notifyListeners();

    try {
      await _ticketsService.replyToTicket(
        ticketId: ticketId,
        reply: reply,
        filePath: filePath,
      );

      // Refresh ticket detail to show the new message
      await fetchTicketDetail(ticketId);

      _isSending = false;
      notifyListeners();
      return true;
    } catch (e) {
      _sendError = e.toString().replaceAll('Exception: ', '');
      _isSending = false;
      notifyListeners();
      return false;
    }
  }
}
