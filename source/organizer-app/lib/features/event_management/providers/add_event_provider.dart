import 'package:booktkit_organizer/utils/app_logger.dart';
import 'package:booktkit_organizer/features/event_management/data/models/add_event_init_model.dart';
import 'package:booktkit_organizer/features/event_management/data/models/store_event_request.dart';
import 'package:booktkit_organizer/features/event_management/data/models/update_event_request.dart';
import 'package:booktkit_organizer/services/event_management_service.dart';
import 'package:flutter/material.dart';

/// Provider for the Add Event form.
/// Handles: init data (languages, currency, settings),
/// cascading location (countries → states/cities → cities).
class AddEventProvider extends ChangeNotifier {
  final EventManagementService _service = EventManagementService();

  // ── Init data ────────────────────────────────────────────────────────────
  bool _isLoading = false;
  String? _error;
  AddEventInitModel? _initData;

  bool get isLoading => _isLoading;
  String? get error => _error;
  AddEventInitModel? get initData => _initData;

  List<LanguageItem> get languages => _initData?.languages ?? [];
  CurrencyInfo? get currencyInfo => _initData?.currencyInfo;
  BasicSettings? get basicSettings => _initData?.basicSettings;

  // Returns the default language ID (used as langId for category/country calls)
  int get defaultLangId {
    if (_initData == null) return 8; // fallback
    final def = _initData!.languages.firstWhere(
      (l) => l.isDefault,
      orElse: () => _initData!.languages.first,
    );
    return def.id;
  }

  // ── Location ─────────────────────────────────────────────────────────────
  bool _locationLoading = false;
  bool get locationLoading => _locationLoading;

  List<LocationItem> _countries = [];
  List<LocationItem> _states = [];
  List<LocationItem> _cities = [];

  List<LocationItem> get countries => _countries;
  List<LocationItem> get states => _states;
  List<LocationItem> get cities => _cities;

  // Selected IDs (as strings to match API field types)
  String? selectedCountryId;
  String? selectedStateId;
  String? selectedCityId;
  String? selectedLanguageId;
  String? selectedCategoryId;

  // ── Event creation ───────────────────────────────────────────────────────
  bool _isCreating = false;
  String? _createError;

  bool get isCreating => _isCreating;
  String? get createError => _createError;

  // ────────────────────────────────────────────────────────────────────────
  /// Fetch the init data (languages, currency, basic settings), then countries.
  Future<void> fetchInitData() async {
    if (_isLoading) return;
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      _initData = await _service.getAddEventInit();
      // Set default language selection
      if (_initData!.languages.isNotEmpty) {
        final def = _initData!.languages.firstWhere(
          (l) => l.isDefault,
          orElse: () => _initData!.languages.first,
        );
        selectedLanguageId = def.id.toString();
      }
      _isLoading = false;
      notifyListeners();
      // Load countries only
      await _loadCountries();
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Load only the countries list.
  Future<void> _loadCountries() async {
    _locationLoading = true;
    notifyListeners();
    try {
      _countries = await _service.getAllCountries(defaultLangId);
      AppLogger.info('[Provider] countries loaded: ${_countries.length}');
    } catch (e) {
      AppLogger.info('[Provider] _loadCountries error: $e');
    }
    _locationLoading = false;
    notifyListeners();
  }

  /// Called when the user picks a country — cascades to load states & cities.
  Future<void> onCountryChanged(String? countryId) async {
    selectedCountryId = countryId;
    selectedStateId = null;
    selectedCityId = null;
    _states = [];
    _cities = [];
    notifyListeners();

    if (countryId == null) return;
    final id = int.tryParse(countryId);
    if (id == null) return;

    _locationLoading = true;
    notifyListeners();
    try {
      final result = await _service.getCountryWiseStateCity(id);
      _states = result.states;
      _cities = result.cities;
      AppLogger.info(
        '[Provider] states: ${_states.length}, cities: ${_cities.length}',
      );
    } catch (e) {
      AppLogger.info('[Provider] onCountryChanged error: $e');
    }
    _locationLoading = false;
    notifyListeners();
  }

  /// Called when the user picks a state — loads state-filtered cities.
  Future<void> onStateChanged(String? stateId) async {
    selectedStateId = stateId;
    selectedCityId = null;
    _cities = [];
    notifyListeners();

    if (stateId == null) return;
    final id = int.tryParse(stateId);
    if (id == null) return;

    _locationLoading = true;
    notifyListeners();
    try {
      _cities = await _service.getStateWiseCity(id);
      AppLogger.info('[Provider] cities from state: ${_cities.length}');
    } catch (e) {
      AppLogger.info('[Provider] onStateChanged error: $e');
    }
    _locationLoading = false;
    notifyListeners();
  }

  void reset() {
    _initData = null;
    _countries = [];
    _states = [];
    _cities = [];
    selectedCountryId = null;
    selectedStateId = null;
    selectedCityId = null;
    selectedLanguageId = null;
    selectedCategoryId = null;
    _error = null;
    _createError = null;
    notifyListeners();
  }

  /// Create a new event with the given request data
  Future<bool> createEvent(StoreEventRequest request) async {
    _isCreating = true;
    _createError = null;
    notifyListeners();

    try {
      await _service.storeEvent(request);
      _isCreating = false;
      notifyListeners();
      return true;
    } catch (e) {
      _createError = e.toString().replaceAll('Exception: ', '');
      _isCreating = false;
      notifyListeners();
      AppLogger.e('[AddEventProvider] createEvent error: $e');
      return false;
    }
  }

  /// Update an existing event with the given request data
  Future<bool> updateEvent(UpdateEventRequest request) async {
    _isCreating = true;
    _createError = null;
    notifyListeners();

    try {
      await _service.updateEvent(request);
      _isCreating = false;
      notifyListeners();
      return true;
    } catch (e) {
      _createError = e.toString().replaceAll('Exception: ', '');
      _isCreating = false;
      notifyListeners();
      AppLogger.e('[AddEventProvider] updateEvent error: $e');
      return false;
    }
  }
}
