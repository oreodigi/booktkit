import 'package:booktkit_organizer/features/event_management/data/models/ticket_settings_model.dart';
import 'package:booktkit_organizer/services/event_management_service.dart';
import 'package:flutter/material.dart';

/// Standalone provider for the ticket settings screen.
class TicketSettingsProvider extends ChangeNotifier {
  final EventManagementService _service = EventManagementService();

  bool _isLoading = false;
  bool _isSaving = false;
  String? _error;
  String? _saveError;
  bool _savedSuccessfully = false;
  TicketSettingsModel? _settings;

  bool get isLoading => _isLoading;
  bool get isSaving => _isSaving;
  String? get error => _error;
  String? get saveError => _saveError;
  bool get savedSuccessfully => _savedSuccessfully;
  TicketSettingsModel? get settings => _settings;

  Future<void> fetchSettings(int eventId) async {
    _isLoading = true;
    _error = null;
    _settings = null;
    notifyListeners();

    try {
      _settings = await _service.getTicketSettings(eventId);
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> saveSettings({
    required int eventId,
    String? ticketImagePath,
    String? ticketLogoPath,
    required String instructions,
  }) async {
    _isSaving = true;
    _saveError = null;
    _savedSuccessfully = false;
    notifyListeners();

    try {
      await _service.updateTicketSettings(
        eventId: eventId,
        ticketImagePath: ticketImagePath,
        ticketLogoPath: ticketLogoPath,
        instructions: instructions,
      );
      _isSaving = false;
      _savedSuccessfully = true;
      notifyListeners();
    } catch (e) {
      _saveError = e.toString().replaceAll('Exception: ', '');
      _isSaving = false;
      notifyListeners();
    }
  }

  void resetSaveState() {
    _savedSuccessfully = false;
    _saveError = null;
    notifyListeners();
  }

  void clear() {
    _settings = null;
    _error = null;
    notifyListeners();
  }
}
