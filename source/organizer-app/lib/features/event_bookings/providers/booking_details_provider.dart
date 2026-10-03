import 'package:booktkit_organizer/features/event_bookings/data/models/booking_details_model.dart';
import 'package:booktkit_organizer/services/api_client.dart';
import 'package:booktkit_organizer/services/booking_details_service.dart';
import 'package:flutter/material.dart';

class BookingDetailsProvider extends ChangeNotifier {
  final BookingDetailsService _service;

  BookingDetailsProvider() : _service = BookingDetailsService(ApiClient());

  BookingDetailsResponse? _details;
  bool _isLoading = false;
  String? _errorMessage;

  BookingDetailsResponse? get details => _details;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  Future<void> fetchDetails(String bookingId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _details = await _service.getDetails(bookingId);
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}
