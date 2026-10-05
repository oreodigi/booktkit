class Urls {
  static const String baseUrl = 'https://booktkit.com';

  // Auth Endpoints
  static const String orgLogin = '/api/organizer/login/submit';
  static const String orgSignup = '/api/organizer/signup/submit';
  static const String orgLogout = '/api/organizer/logout';
  static const String organizerScannerCheck = '/api/organizer/check-qrcode';
  static const String staffScannerLogin = '/api/staff-scanner/login';
  static const String staffScannerEvents = '/api/staff-scanner/events';
  static const String staffScannerScan = '/api/staff-scanner/scan';
  static const String staffScannerLogout = '/api/staff-scanner/logout';
  static const String orgDashboard = '/api/organizer/dashboard';
  static const String orgTransaction = '/api/organizer/transcation';
  static const String orgIncome = '/api/organizer/monthly-income';
  static const String orgWithdraw = '/api/organizer/withdraw';
  static const String withdrawDelete = '/api/organizer/withdraw/delete';
  static const String withdrawSubmit = '/api/organizer/withdraw/send-request';
  static const String withdrawCreate = '/api/organizer/withdraw/create';
  static const String getMethodInput =
      '/api/organizer/withdraw/get-method-input';
  static const String withdrawCalculation =
      '/api/organizer/withdraw/calculation';
  static const String getTickets = '/api/organizer/support-ticket';
  static const String getTicketDetail = '/api/organizer/support-ticket/message';
  static const String ticketReply = '/api/organizer/support-ticket/reply';
  static const String storeSupportTicket =
      '/api/organizer/support-ticket/store';
  static const String deleteTicket = '/api/organizer/support-ticket/delete';
  static const String getBookings = '/api/organizer/event-booking';
  static const String getBookingReport = '/api/organizer/event-booking/report';
  static const String getBookingDetails =
      '/api/organizer/event-booking/details';
  static const String deleteBooking = '/api/organizer/event-booking/delete';
  static const String getEvents = '/api/organizer/event-management/events';
  static const String getEventEdit =
      '/api/organizer/event-management/event-edit';
  static const String getEventTickets =
      '/api/organizer/event-management/event/tickets';
  static const String getTicketSettings =
      '/api/organizer/event-management/edit-ticket-setting';
  static const String updateTicketSetting =
      '/api/organizer/event-management/update-ticket-setting';
  static const String getAllCategories =
      '/api/organizer/event-management/all-categories';
  static const String addEvent = '/api/organizer/event-management/add-event';
  static const String storeEvent =
      '/api/organizer/event-management/event-store';
  static const String updateEvent =
      '/api/organizer/event-management/event-update';
  static const String allCountries =
      '/api/organizer/event-management/all-countries';
  static const String allStates = '/api/organizer/event-management/all-states';
  static const String allCities = '/api/organizer/event-management/all-cities';
  static const String countryWiseStateCity =
      '/api/organizer/event-management/country-wise-state-city';
  static const String stateWiseCity =
      '/api/organizer/event-management/state-wise-city';
  static const String eventUpdateStatus =
      '/api/organizer/event-management/event-update-status';
  static const String eventUpdateFeatured =
      '/api/organizer/event-management/event-update-featured';
  static const String deleteEventImage =
      '/api/organizer/event-management/event-img-delete';
  static const String deleteEventDate =
      '/api/organizer/event-management/event-delete-date';
  static const String deleteEventTicket =
      '/api/organizer/event-management/event/delete-ticket';
  static const String storeEventTicket =
      '/api/organizer/event-management/event/store-ticket';
  static const String eventDelete =
      '/api/organizer/event-management/event-delete';
  static const String editTicket =
      '/api/organizer/event-management/event/edit-ticket';
  static const String updateEventTicket =
      '/api/organizer/event-management/event/update-ticket';
  static const String getSeatMappingSlots =
      '/api/organizer/seat-mapping/slot/all';
  static const String getSlotSeats = '/api/organizer/seat-mapping/slot/seats';
  static const String updateSeatMapBackgroundImage =
      '/api/organizer/seat-mapping/slot/update-background-image';
  static const String deleteSlot = '/api/organizer/seat-mapping/slot/delete';
  static const String updateSeats =
      '/api/organizer/seat-mapping/slot/seats/update';
  static const String storeUpdateSlot =
      '/api/organizer/seat-mapping/slot/update-store';
  static const String dragDropSlot =
      '/api/organizer/seat-mapping/slot/drag-drop';
  static const String updatePassword = '/api/organizer/updated-password';
  static const String updateProfile = '/api/organizer/updated-profile';
  static const String editProfile = '/api/organizer/edit-profile';
  static const String getCurrency = '/api/get-currency';
}
