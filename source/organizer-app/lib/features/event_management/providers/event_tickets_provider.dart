import 'package:booktkit_organizer/features/event_management/data/models/edit_ticket_model.dart';
import 'package:booktkit_organizer/features/event_management/data/models/event_tickets_model.dart';
import 'package:booktkit_organizer/features/event_management/data/models/store_ticket_request.dart';
import 'package:booktkit_organizer/services/event_management_service.dart';
import 'package:flutter/material.dart';

/// Separate provider solely responsible for fetching and holding
/// the tickets list for a specific event.
class EventTicketsProvider extends ChangeNotifier {
  final EventManagementService _service = EventManagementService();

  bool _isLoading = false;
  String? _error;
  EventTicketsResponse? _response;

  bool get isLoading => _isLoading;
  String? get error => _error;
  EventTicketsResponse? get response => _response;

  /// All tickets from the last successful fetch.
  List<EventTicketItem> get tickets => _response?.tickets ?? [];

  /// Search-filtered view of tickets.
  List<EventTicketItem> filteredTickets(String query) {
    if (query.trim().isEmpty) return tickets;
    final q = query.toLowerCase();
    return tickets
        .where((t) => (t.title ?? '').toLowerCase().contains(q))
        .toList();
  }

  Future<void> fetchTickets({
    required int eventId,
    required String eventType,
  }) async {
    _isLoading = true;
    _error = null;
    _response = null;
    notifyListeners();

    try {
      _response = await _service.getEventTickets(
        eventId: eventId,
        eventType: eventType,
      );
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
    }
  }

  void clear() {
    _response = null;
    _error = null;
    notifyListeners();
  }

  // ── Language list for add-ticket form ─────────────
  List<EditTicketLanguage> _languages = [];
  bool _isLoadingLanguages = false;

  List<EditTicketLanguage> get languages => _languages;
  bool get isLoadingLanguages => _isLoadingLanguages;

  Future<void> fetchTicketLanguages({
    required int eventId,
    required String eventType,
  }) async {
    if (_languages.isNotEmpty) return; // already loaded
    _isLoadingLanguages = true;
    notifyListeners();
    try {
      final initData = await _service.getAddEventInit();
      _languages = initData.languages.map((l) {
        return EditTicketLanguage(
          id: l.id,
          name: l.name,
          code: l.code,
          isRtl: l.isRtl,
          isDefault: l.isDefault,
        );
      }).toList();
    } catch (_) {
      // Fall back to en+ar if the endpoint fails
      _languages = [
        EditTicketLanguage(id: 1, name: 'English', code: 'en', isRtl: false, isDefault: true),
        EditTicketLanguage(id: 2, name: 'Arabic', code: 'ar', isRtl: true, isDefault: false),
      ];
    }
    _isLoadingLanguages = false;
    notifyListeners();
  }

  // ── Delete ticket ─────────────────────────────
  bool _isDeleting = false;
  String? _deleteError;

  bool get isDeleting => _isDeleting;
  String? get deleteError => _deleteError;

  Future<bool> deleteTicket(int ticketId) async {
    if (_isDeleting) return false;
    _isDeleting = true;
    _deleteError = null;
    notifyListeners();

    try {
      await _service.deleteEventTicket(ticketId);
      // Remove from local list immediately
      _response?.tickets.removeWhere((t) => t.id == ticketId);
      _isDeleting = false;
      notifyListeners();
      return true;
    } catch (e) {
      _deleteError = e.toString().replaceAll('Exception: ', '');
      _isDeleting = false;
      notifyListeners();
      return false;
    }
  }

  // ── Store (add) ticket ────────────────────────
  bool _isSavingTicket = false;
  String? _saveTicketError;

  bool get isSavingTicket => _isSavingTicket;
  String? get saveTicketError => _saveTicketError;

  Future<bool> storeEventTicket(StoreTicketRequest request) async {
    if (_isSavingTicket) return false;
    _isSavingTicket = true;
    _saveTicketError = null;
    notifyListeners();

    try {
      await _service.storeTicket(request);
      _isSavingTicket = false;
      notifyListeners();
      return true;
    } catch (e) {
      _saveTicketError = e.toString().replaceAll('Exception: ', '');
      _isSavingTicket = false;
      notifyListeners();
      return false;
    }
  }
}
