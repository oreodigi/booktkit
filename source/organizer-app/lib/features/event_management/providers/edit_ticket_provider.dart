import 'package:booktkit_organizer/features/event_management/data/models/edit_ticket_model.dart';
import 'package:booktkit_organizer/features/event_management/data/models/update_ticket_request.dart';
import 'package:booktkit_organizer/services/event_management_service.dart';
import 'package:flutter/material.dart';

class EditTicketProvider extends ChangeNotifier {
  final EventManagementService _service = EventManagementService();

  bool _isLoading = false;
  String? _error;
  EditTicketResponse? _response;

  bool get isLoading => _isLoading;
  String? get error => _error;
  EditTicketResponse? get response => _response;

  EditTicketItem? get ticket => _response?.ticket;
  List<EditTicketLanguage> get languages => _response?.languages ?? [];
  EditTicketCurrency? get currencyInfo => _response?.currencyInfo;

  Future<void> fetchEditTicket({
    required int eventId,
    required String eventType,
    required int ticketId,
  }) async {
    _isLoading = true;
    _error = null;
    _response = null;
    notifyListeners();

    try {
      _response = await _service.getEditTicket(
        eventId: eventId,
        eventType: eventType,
        ticketId: ticketId,
      );
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
    }
  }

  bool _isUpdatingTicket = false;
  String? _updateTicketError;

  bool get isUpdatingTicket => _isUpdatingTicket;
  String? get updateTicketError => _updateTicketError;

  Future<bool> updateTicket(UpdateTicketRequest request) async {
    _isUpdatingTicket = true;
    _updateTicketError = null;
    notifyListeners();

    try {
      await _service.updateTicket(request);
      _isUpdatingTicket = false;
      notifyListeners();
      return true;
    } catch (e) {
      _isUpdatingTicket = false;
      _updateTicketError = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  void clear() {
    _response = null;
    _error = null;
    _updateTicketError = null;
    notifyListeners();
  }
}
