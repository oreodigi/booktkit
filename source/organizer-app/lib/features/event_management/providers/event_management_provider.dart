import 'package:booktkit_organizer/features/event_management/data/models/add_event_init_model.dart'
    show CurrencyInfo;
import 'package:booktkit_organizer/features/event_management/data/models/event_detail_model.dart';
import 'package:booktkit_organizer/features/event_management/data/models/events_model.dart';
import 'package:booktkit_organizer/services/event_management_service.dart';
import 'package:flutter/material.dart';

class EventManagementProvider extends ChangeNotifier {
  final EventManagementService _service = EventManagementService();

  // ── Events list ──────────────────────────────
  bool _isLoading = false;
  bool _isLoadingMore = false;
  String? _errorMessage;
  List<EventItem> _events = [];

  int _currentPage = 1;
  bool _hasMore = true;

  String? _titleFilter;
  String? _eventTypeFilter;

  // ── Language ─────────────────────────────────
  List<LangItem> _langs = [];
  // Tracks the language code for the current screen session.
  // Updated only when a screen explicitly passes updateLanguage: true.
  // Used by fetchNextPage, setTitleFilter, setEventTypeFilter to stay consistent.
  String? _currentLanguageCode;

  bool get isLoading => _isLoading;
  bool get isLoadingMore => _isLoadingMore;
  String? get errorMessage => _errorMessage;
  List<EventItem> get events => _events;
  bool get hasMore => _hasMore;
  String? get eventTypeFilter => _eventTypeFilter;
  List<LangItem> get langs => _langs;

  /// The language the API treats as default (isDefault == true), or the first
  /// item in the list. Used by screens to pre-select a lang without persisting.
  LangItem? get defaultLang =>
      _langs.where((l) => l.isDefault).firstOrNull ??
      (_langs.isNotEmpty ? _langs.first : null);

  // ── Event detail ─────────────────────────────
  bool _isDetailLoading = false;
  String? _detailError;
  EventDetailResponse? _eventDetail;

  bool get isDetailLoading => _isDetailLoading;
  String? get detailError => _detailError;
  EventDetailResponse? get eventDetail => _eventDetail;

  /// Currency info from the last fetched event detail (from getCurrencyInfo in API).
  CurrencyInfo? get currencyInfo => _eventDetail?.currencyInfo;

  // ── Events list methods ──────────────────────
  /// [languageCode] — the language to query with.
  /// [updateLanguage] — when true, stores [languageCode] as the session language
  ///   (used by pagination and filter calls that don't re-supply a language).
  ///   Pass `updateLanguage: true` from initState (to reset) or on lang selection.
  Future<void> fetchEvents({
    String? title,
    String? eventType,
    bool refresh = false,
    String? languageCode,
    bool updateLanguage = false,
  }) async {
    if (updateLanguage) _currentLanguageCode = languageCode;
    if (refresh) {
      _currentPage = 1;
      _events = [];
      _hasMore = true;
    }

    if (title != null) _titleFilter = title;
    if (eventType != null) _eventTypeFilter = eventType;

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _service.getEvents(
        title: _titleFilter,
        eventType: _eventTypeFilter,
        page: _currentPage,
        languageCode: _currentLanguageCode,
      );

      if (refresh) {
        _events = response.events;
      } else {
        _events.addAll(response.events);
      }

      _hasMore = response.hasNextPage;

      // Update langs list from API response (first page only)
      if (response.langs.isNotEmpty) {
        _langs = response.langs;
      }

      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchNextPage() async {
    if (_isLoadingMore || !_hasMore) return;

    _isLoadingMore = true;
    notifyListeners();

    try {
      _currentPage++;
      final response = await _service.getEvents(
        title: _titleFilter,
        eventType: _eventTypeFilter,
        page: _currentPage,
        languageCode: _currentLanguageCode,
      );

      _events.addAll(response.events);
      _hasMore = response.hasNextPage;
      _isLoadingMore = false;
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _currentPage--;
      _isLoadingMore = false;
      notifyListeners();
    }
  }

  void setTitleFilter(String query) {
    if (_titleFilter == query) return;
    _titleFilter = query;
    fetchEvents(refresh: true);
  }

  void setEventTypeFilter(String? type) {
    if (_eventTypeFilter == type) return;
    _eventTypeFilter = type;
    fetchEvents(refresh: true);
  }

  // ── Event detail methods ─────────────────────
  Future<void> fetchEventDetail(int eventId) async {
    _isDetailLoading = true;
    _detailError = null;
    _eventDetail = null;
    notifyListeners();

    try {
      _eventDetail = await _service.getEventDetail(eventId);
      _isDetailLoading = false;
      notifyListeners();
    } catch (e) {
      _detailError = e.toString().replaceAll('Exception: ', '');
      _isDetailLoading = false;
      notifyListeners();
    }
  }

  void clearEventDetail() {
    _eventDetail = null;
    _detailError = null;
    notifyListeners();
  }

  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }

  // ── Delete event ──────────────────────────────
  bool _isDeleting = false;
  String? _deleteError;

  bool get isDeleting => _isDeleting;
  String? get deleteError => _deleteError;

  Future<bool> deleteEvent(int eventId) async {
    if (_isDeleting) return false;
    _isDeleting = true;
    _deleteError = null;
    notifyListeners();

    try {
      await _service.deleteEvent(eventId);
      // Remove from local list immediately
      _events.removeWhere((e) => int.tryParse(e.eventId) == eventId);
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

  // ── Update event status ───────────────────────
  String? _statusError;
  String? get statusError => _statusError;

  /// Optimistically toggle isActive then confirm with API.
  /// Returns true on success, false on failure (reverts local state).
  Future<bool> updateEventStatus(int eventId, bool newActive) async {
    final idx = _events.indexWhere(
      (e) => int.tryParse(e.eventId) == eventId,
    );
    if (idx == -1) return false;

    // Optimistic update
    _events[idx] = _events[idx].copyWith(isActive: newActive);
    _statusError = null;
    notifyListeners();

    try {
      await _service.updateEventStatus(
        eventId: eventId,
        status: newActive ? 1 : 0,
      );
      return true;
    } catch (e) {
      // Revert on failure
      _events[idx] = _events[idx].copyWith(isActive: !newActive);
      _statusError = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  // ── Update event featured ─────────────────────
  String? _featuredError;
  String? get featuredError => _featuredError;

  Future<bool> updateEventFeatured(int eventId, bool newFeatured) async {
    final idx = _events.indexWhere(
      (e) => int.tryParse(e.eventId) == eventId,
    );
    if (idx == -1) return false;

    // Optimistic update
    _events[idx] = _events[idx].copyWith(isFeatured: newFeatured);
    _featuredError = null;
    notifyListeners();

    try {
      await _service.updateEventFeatured(
        eventId: eventId,
        isFeatured: newFeatured,
      );
      return true;
    } catch (e) {
      // Revert on failure
      _events[idx] = _events[idx].copyWith(isFeatured: !newFeatured);
      _featuredError = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }
}
