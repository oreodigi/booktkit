import 'package:booktkit_organizer/features/event_management/data/models/seat_mapping_model.dart';
import 'package:booktkit_organizer/services/seat_mapping_service.dart';
import 'package:flutter/material.dart';

/// Provider for managing seat mapping state and operations
class SeatMappingProvider extends ChangeNotifier {
  final SeatMappingService _service = SeatMappingService();

  // ── State variables ──────────────────────────
  bool _isLoading = false;
  String? _errorMessage;
  SeatMappingData? _seatMappingData;

  // ── Getters ──────────────────────────────────
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  SeatMappingData? get seatMappingData => _seatMappingData;
  List<SlotItem> get slots => _seatMappingData?.slots ?? [];
  String? get coverImage => _seatMappingData?.coverImage;
  EventContentData? get eventContents => _seatMappingData?.eventContents;
  TicketContentData? get ticketContents => _seatMappingData?.ticketContents;

  // ── Methods ──────────────────────────────────

  /// Fetch seat mapping slots for a specific event and ticket
  Future<void> fetchSeatMappingSlots({
    required int eventId,
    required int ticketId,
    required String slotUniqueId,
    required String pricingType,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _service.getSeatMappingSlots(
        eventId: eventId,
        ticketId: ticketId,
        slotUniqueId: slotUniqueId,
        pricingType: pricingType,
      );

      if (response.success && response.data != null) {
        _seatMappingData = response.data;
        _errorMessage = null;
      } else {
        _errorMessage = 'Failed to load seat mapping data';
        _seatMappingData = null;
      }

      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _seatMappingData = null;
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Clear all data (useful when navigating away)
  void clearData() {
    _seatMappingData = null;
    _errorMessage = null;
    _isLoading = false;
    notifyListeners();
  }

  /// Get a specific slot by ID
  SlotItem? getSlotById(int slotId) {
    return slots.firstWhere(
      (slot) => slot.id == slotId,
      orElse: () => slots.first,
    );
  }

  /// Get all seats from all slots
  List<SeatItem> getAllSeats() {
    final allSeats = <SeatItem>[];
    for (final slot in slots) {
      allSeats.addAll(slot.seats);
    }
    return allSeats;
  }

  /// Get filtered seats (non-booked, active seats)
  List<SeatItem> getAvailableSeats() {
    final allSeats = getAllSeats();
    return allSeats
        .where((seat) => seat.isBooked == 0 && seat.isDeactive == '0')
        .toList();
  }

  /// Get booked seats count
  int getBookedSeatsCount() {
    return getAllSeats().where((seat) => seat.isBooked == 1).length;
  }

  /// Get total seats count
  int getTotalSeatsCount() {
    return getAllSeats().length;
  }
}
