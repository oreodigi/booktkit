import 'package:flutter/foundation.dart';

import '../../services/api_client.dart';
import '../models/dashboard_models.dart';

class DashboardProvider extends ChangeNotifier {
  final ApiClient _api = ApiClient();

  DashboardData? _dashboardData;
  bool _loading = false;
  String? _error;
  String? _selectedEventId;
  int _selectedTab = 0;
  bool _hasLoadedOnce = false;
  int _displayedTicketsCount = 10; // Start with 10 tickets
  bool _loadingMore = false;

  DashboardData? get dashboardData => _dashboardData;
  bool get loading => _loading;
  String? get error => _error;
  String? get selectedEventId => _selectedEventId;
  int get selectedTab => _selectedTab;

  bool get hasData => _dashboardData != null;
  bool get hasLoadedOnce => _hasLoadedOnce;
  bool get loadingMore => _loadingMore;
  int get displayedTicketsCount => _displayedTicketsCount;

  // Get event start date and time
  DateTime? getEventStartDateTime(EventData event) {
    // Try dates array first
    if (event.dates != null && event.dates!.isNotEmpty) {
      try {
        return DateTime.parse(
          '${event.dates!.first.startDate} ${event.dates!.first.startTime}',
        );
      } catch (_) {}
    }

    // Use event.date and time
    try {
      if (event.time != null && event.time!.isNotEmpty) {
        return DateTime.parse('${event.date} ${event.time}');
      }
      return DateTime.parse(event.date);
    } catch (_) {
      return null;
    }
  }

  // Get event end date and time
  DateTime? getEventEndDateTime(EventData event) {
    // Try dates array first
    if (event.dates != null && event.dates!.isNotEmpty) {
      try {
        return DateTime.parse(
          '${event.dates!.last.endDate} ${event.dates!.last.endTime}',
        );
      } catch (_) {}
    }

    // Calculate from event.date + duration
    try {
      final startDate = DateTime.parse(event.date);
      return _calculateEndDate(startDate, event.duration);
    } catch (_) {
      return null;
    }
  }

  // Get time remaining until event ends (for running events)
  String getTimeRemaining(EventData event) {
    final endDate = getEventEndDateTime(event);
    if (endDate == null) return '';

    final now = DateTime.now();
    if (now.isAfter(endDate)) return 'Ended';

    final difference = endDate.difference(now);

    final days = difference.inDays;
    final hours = difference.inHours % 24;
    final minutes = difference.inMinutes % 60;

    if (days > 0) {
      return '$days day${days > 1 ? 's' : ''} ${hours}h left';
    } else if (hours > 0) {
      return '${hours}h ${minutes}m left';
    } else {
      return '${minutes}m left';
    }
  }

  // Get time until event starts (for upcoming events)
  String getTimeUntilStart(EventData event) {
    final startDate = getEventStartDateTime(event);
    if (startDate == null) return '';

    final now = DateTime.now();
    if (now.isAfter(startDate)) return 'Started';

    final difference = startDate.difference(now);

    final days = difference.inDays;
    final hours = difference.inHours % 24;

    if (days > 30) {
      final months = (days / 30).floor();
      return 'In $months month${months > 1 ? 's' : ''}';
    } else if (days > 0) {
      return 'In $days day${days > 1 ? 's' : ''}';
    } else if (hours > 0) {
      return 'In ${hours}h';
    } else {
      return 'Starting soon';
    }
  }

  // Get currently running event
  EventData? get currentRunningEvent {
    if (_dashboardData == null || _dashboardData!.events.isEmpty) return null;

    final now = DateTime.now();

    for (var event in _dashboardData!.events) {
      // Try dates array first
      if (event.dates != null && event.dates!.isNotEmpty) {
        for (var eventDate in event.dates!) {
          try {
            final startDateTime = DateTime.parse(
              '${eventDate.startDate} ${eventDate.startTime}',
            );
            final endDateTime = DateTime.parse(
              '${eventDate.endDate} ${eventDate.endTime}',
            );

            if (now.isAfter(startDateTime) && now.isBefore(endDateTime)) {
              return event;
            }
          } catch (_) {}
        }
      }

      // Check event.date + duration
      try {
        final eventStartDate = DateTime.parse(event.date);
        final eventEndDate = _calculateEndDate(eventStartDate, event.duration);

        if (now.isAfter(eventStartDate) && now.isBefore(eventEndDate)) {
          return event;
        }
      } catch (_) {}
    }

    return null;
  }

  // Get next upcoming event
  EventData? get nextUpcomingEvent {
    if (_dashboardData == null || _dashboardData!.events.isEmpty) return null;

    final now = DateTime.now();
    EventData? upcomingEvent;
    DateTime? closestDate;

    for (var event in _dashboardData!.events) {
      DateTime? eventStartDate;

      // Try dates array first
      if (event.dates != null && event.dates!.isNotEmpty) {
        for (var eventDate in event.dates!) {
          try {
            final startDateTime = DateTime.parse(
              '${eventDate.startDate} ${eventDate.startTime}',
            );

            if (startDateTime.isAfter(now)) {
              if (closestDate == null || startDateTime.isBefore(closestDate)) {
                closestDate = startDateTime;
                upcomingEvent = event;
              }
            }
          } catch (_) {}
        }
      }

      // Check event.date
      try {
        eventStartDate = DateTime.parse(event.date);

        if (eventStartDate.isAfter(now)) {
          if (closestDate == null || eventStartDate.isBefore(closestDate)) {
            closestDate = eventStartDate;
            upcomingEvent = event;
          }
        }
      } catch (_) {}
        }

    return upcomingEvent;
  }

  // Get currently running event or next upcoming event (backward compatibility)
  EventData? get currentOrUpcomingEvent {
    if (_dashboardData == null || _dashboardData!.events.isEmpty) return null;

    final now = DateTime.now();

    // Check for currently running event
    for (var event in _dashboardData!.events) {
      DateTime? eventStartDate;
      DateTime? eventEndDate;

      // Try to parse from event dates array first
      if (event.dates != null && event.dates!.isNotEmpty) {
        for (var eventDate in event.dates!) {
          try {
            final startDateTime = DateTime.parse(
              '${eventDate.startDate} ${eventDate.startTime}',
            );
            final endDateTime = DateTime.parse(
              '${eventDate.endDate} ${eventDate.endTime}',
            );

            // Check if currently running
            if (now.isAfter(startDateTime) && now.isBefore(endDateTime)) {
              return event;
            }

            // Track earliest start date
            if (eventStartDate == null ||
                startDateTime.isBefore(eventStartDate)) {
              eventStartDate = startDateTime;
            }
          } catch (_) {}
        }
      }

      // If no dates array, use event.date + duration
      if (eventStartDate == null) {
        try {
          // Parse event date
          eventStartDate = DateTime.parse(event.date);

          // Calculate end date based on duration
          eventEndDate = _calculateEndDate(eventStartDate, event.duration);

          // Check if currently running
          if (now.isAfter(eventStartDate) && now.isBefore(eventEndDate)) {
            return event;
          }
        } catch (_) {
          // Skip if date parsing fails
        }
      }
    }

    // No running event, find next upcoming
    EventData? upcomingEvent;
    DateTime? closestDate;

    for (var event in _dashboardData!.events) {
      DateTime? eventStartDate;

      // Try dates array first
      if (event.dates != null && event.dates!.isNotEmpty) {
        for (var eventDate in event.dates!) {
          try {
            final startDateTime = DateTime.parse(
              '${eventDate.startDate} ${eventDate.startTime}',
            );

            if (startDateTime.isAfter(now)) {
              if (closestDate == null || startDateTime.isBefore(closestDate)) {
                closestDate = startDateTime;
                upcomingEvent = event;
              }
            }
          } catch (_) {}
        }
      }

      // If no dates array, use event.date
      try {
        eventStartDate = DateTime.parse(event.date);

        if (eventStartDate.isAfter(now)) {
          if (closestDate == null || eventStartDate.isBefore(closestDate)) {
            closestDate = eventStartDate;
            upcomingEvent = event;
          }
        }
      } catch (_) {}
    }

    return upcomingEvent ?? _dashboardData!.events.first;
  }

  // Helper to calculate end date from start date + duration string
  DateTime _calculateEndDate(DateTime startDate, String duration) {
    // duration format: "3mo 93d 12h 1m" 
    // IGNORE months and years - only use days, hours, minutes, seconds
    DateTime endDate = startDate;

    // Parse each component (ignore mo and y)
    final daysMatch = RegExp(r'(\d+)d').firstMatch(duration);
    final hoursMatch = RegExp(r'(\d+)h').firstMatch(duration);
    final minutesMatch = RegExp(r'(\d+)m').firstMatch(duration);
    final secondsMatch = RegExp(r'(\d+)s').firstMatch(duration);

    // Add days
    if (daysMatch != null) {
      final days = int.parse(daysMatch.group(1)!);
      endDate = endDate.add(Duration(days: days));
    }

    // Add hours
    if (hoursMatch != null) {
      final hours = int.parse(hoursMatch.group(1)!);
      endDate = endDate.add(Duration(hours: hours));
    }

    // Add minutes
    if (minutesMatch != null) {
      final minutes = int.parse(minutesMatch.group(1)!);
      endDate = endDate.add(Duration(minutes: minutes));
    }

    // Add seconds
    if (secondsMatch != null) {
      final seconds = int.parse(secondsMatch.group(1)!);
      endDate = endDate.add(Duration(seconds: seconds));
    }

    // If no duration components found, default to 1 day
    if (daysMatch == null && hoursMatch == null && minutesMatch == null && secondsMatch == null) {
      endDate = endDate.add(Duration(days: 1));
    }

    return endDate;
  }

  bool get isEventRunning {
    if (_dashboardData == null || _dashboardData!.events.isEmpty) return false;

    final now = DateTime.now();

    for (var event in _dashboardData!.events) {
      // Check dates array first
      if (event.dates != null && event.dates!.isNotEmpty) {
        for (var eventDate in event.dates!) {
          try {
            final startDateTime = DateTime.parse(
              '${eventDate.startDate} ${eventDate.startTime}',
            );
            final endDateTime = DateTime.parse(
              '${eventDate.endDate} ${eventDate.endTime}',
            );

            if (now.isAfter(startDateTime) && now.isBefore(endDateTime)) {
              return true;
            }
          } catch (_) {}
        }
      }

      // Check event.date + duration
      try {
        final eventStartDate = DateTime.parse(event.date);
        final eventEndDate = _calculateEndDate(eventStartDate, event.duration);

        if (now.isAfter(eventStartDate) && now.isBefore(eventEndDate)) {
          return true;
        }
      } catch (_) {}
    }

    return false;
  }

  List<TicketData> get filteredTickets {
    if (_dashboardData == null) return [];

    List<TicketData> tickets;
    switch (_selectedTab) {
      case 1:
        tickets = _dashboardData!.scannedTickets;
        break;
      case 2:
        tickets = _dashboardData!.unscannedTickets;
        break;
      default:
        tickets = _dashboardData!.allTickets;
    }

    if (_selectedEventId != null) {
      tickets = tickets.where((t) => t.eventId == _selectedEventId).toList();
    }

    // Return only the displayed count
    return tickets.take(_displayedTicketsCount).toList();
  }

  int get totalTicketsCount {
    if (_dashboardData == null) return 0;

    List<TicketData> tickets;
    switch (_selectedTab) {
      case 1:
        tickets = _dashboardData!.scannedTickets;
        break;
      case 2:
        tickets = _dashboardData!.unscannedTickets;
        break;
      default:
        tickets = _dashboardData!.allTickets;
    }

    if (_selectedEventId != null) {
      tickets = tickets.where((t) => t.eventId == _selectedEventId).toList();
    }

    return tickets.length;
  }

  bool get hasMoreTickets => _displayedTicketsCount < totalTicketsCount;

  // Get filtered counts for tab buttons based on selected event
  int get filteredAllCount {
    if (_dashboardData == null) return 0;
    if (_selectedEventId == null) return _dashboardData!.allTickets.length;
    return _dashboardData!.allTickets
        .where((t) => t.eventId == _selectedEventId)
        .length;
  }

  int get filteredScannedCount {
    if (_dashboardData == null) return 0;
    if (_selectedEventId == null) return _dashboardData!.scannedTickets.length;
    return _dashboardData!.scannedTickets
        .where((t) => t.eventId == _selectedEventId)
        .length;
  }

  int get filteredUnscannedCount {
    if (_dashboardData == null) return 0;
    if (_selectedEventId == null) {
      return _dashboardData!.unscannedTickets.length;
    }
    return _dashboardData!.unscannedTickets
        .where((t) => t.eventId == _selectedEventId)
        .length;
  }

  @override
  void notifyListeners() {
    super.notifyListeners();
  }

  void setSelectedEventId(String? eventId) {
    _selectedEventId = eventId;
    _displayedTicketsCount = 10;
    notifyListeners();
  }

  void setSelectedTab(int tab) {
    _selectedTab = tab;
    _displayedTicketsCount = 10; // Reset to 10 when changing tabs
    notifyListeners();
  }

  Future<void> loadMoreTickets() async {
    if (_loadingMore || !hasMoreTickets) return;

    _loadingMore = true;
    notifyListeners();

    // Simulate slight delay for smooth UX
    await Future.delayed(const Duration(milliseconds: 300));

    _displayedTicketsCount += 10;
    _loadingMore = false;
    notifyListeners();
  }

  Future<void> loadData({required String token, required UserRole role}) async {
    _loading = true;
    _error = null;
    notifyListeners();

    try {
      final data = await _api.getDashboardData(token: token, role: role);

      _dashboardData = data;
      _loading = false;
      _error = null;
      _hasLoadedOnce = true;
      notifyListeners();
    } catch (e) {
      _error = e.toString().replaceFirst('Exception: ', '');
      _loading = false;
      _hasLoadedOnce = true;
      notifyListeners();
    }
  }

  bool _updatingStatus = false;
  bool get updatingStatus => _updatingStatus;

  Future<bool> updateTicketStatus({
    required String token,
    required UserRole role,
    required String bookingId,
    required String ticketId,
    required String status,
  }) async {
    if (_updatingStatus) {
      return false;
    }

    _updatingStatus = true;
    notifyListeners();

    try {
      final success = await _api.updateTicketStatus(
        token: token,
        role: role,
        bookingId: bookingId,
        ticketId: ticketId,
        status: status,
      );

      if (success && _dashboardData != null) {
        _updateLocalTicketStatus(ticketId, status);
      } else {}

      return success;
    } catch (e) {
      return false;
    } finally {
      _updatingStatus = false;
      notifyListeners();
    }
  }

  void _updateLocalTicketStatus(String ticketId, String newStatus) {
    if (_dashboardData == null) return;

    // Find the ticket and get its old status
    TicketData? targetTicket;
    String? oldStatus;

    for (var ticket in _dashboardData!.allTickets) {
      if (ticket.ticketId == ticketId) {
        targetTicket = ticket;
        oldStatus = ticket.scanStatus.toLowerCase();
        break;
      }
    }

    if (targetTicket == null || oldStatus == null) return;

    final newStatusLower = newStatus.toLowerCase();

    // If status hasn't actually changed, do nothing
    if (oldStatus == newStatusLower) return;

    // Create updated ticket
    final updatedTicket = TicketData(
      bookingId: targetTicket.bookingId,
      eventId: targetTicket.eventId,
      eventName: targetTicket.eventName,
      ticketName: targetTicket.ticketName,
      ticketId: targetTicket.ticketId,
      customerPhone: targetTicket.customerPhone,
      paymentStatus: targetTicket.paymentStatus,
      scanStatus: newStatus,
    );

    // Update in allTickets
    final allTickets = List<TicketData>.from(_dashboardData!.allTickets);
    final allIndex = allTickets.indexWhere((t) => t.ticketId == ticketId);
    if (allIndex != -1) {
      allTickets[allIndex] = updatedTicket;
    }

    // Update scanned/unscanned lists and counts
    var scannedTickets = List<TicketData>.from(_dashboardData!.scannedTickets);
    var unscannedTickets = List<TicketData>.from(
      _dashboardData!.unscannedTickets,
    );
    var scannedCount = _dashboardData!.totalScannedTickets;
    var unscannedCount = _dashboardData!.totalUnscannedTickets;

    if (newStatusLower == 'scanned' && oldStatus == 'unscanned') {
      // Moving from unscanned to scanned
      unscannedTickets.removeWhere((t) => t.ticketId == ticketId);
      scannedTickets.add(updatedTicket);
      scannedCount++;
      unscannedCount--;
    } else if (newStatusLower == 'unscanned' && oldStatus == 'scanned') {
      // Moving from scanned to unscanned
      scannedTickets.removeWhere((t) => t.ticketId == ticketId);
      unscannedTickets.add(updatedTicket);
      scannedCount--;
      unscannedCount++;
    }

    // Update dashboard data
    _dashboardData = DashboardData(
      events: _dashboardData!.events,
      totalAttendeesTickets: _dashboardData!.totalAttendeesTickets,
      totalScannedTickets: scannedCount,
      totalUnscannedTickets: unscannedCount,
      scannedTickets: scannedTickets,
      unscannedTickets: unscannedTickets,
      allTickets: allTickets,
    );
  }

  void reset() {
    _dashboardData = null;
    _loading = false;
    _error = null;
    _selectedEventId = null;
    _selectedTab = 0;
    _hasLoadedOnce = false;
    _displayedTicketsCount = 10;
    _loadingMore = false;
    notifyListeners();
  }

  void clearData() => reset();
}
