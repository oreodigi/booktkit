/// Model for the GET /add-event init data response.
class AddEventInitModel {
  final CurrencyInfo currencyInfo;
  final List<LanguageItem> languages;
  final BasicSettings basicSettings;

  AddEventInitModel({
    required this.currencyInfo,
    required this.languages,
    required this.basicSettings,
  });

  factory AddEventInitModel.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? {};
    return AddEventInitModel(
      currencyInfo: CurrencyInfo.fromJson(
        data['getCurrencyInfo'] as Map<String, dynamic>? ?? {},
      ),
      languages: (data['languages'] as List<dynamic>? ?? [])
          .map((e) => LanguageItem.fromJson(e as Map<String, dynamic>))
          .toList(),
      basicSettings: BasicSettings.fromJson(
        data['basic_settings'] as Map<String, dynamic>? ?? {},
      ),
    );
  }
}

class CurrencyInfo {
  final String symbol;
  final String symbolPosition;
  final String text;
  final String textPosition;
  final String rate;

  CurrencyInfo({
    required this.symbol,
    required this.symbolPosition,
    required this.text,
    required this.textPosition,
    required this.rate,
  });

  factory CurrencyInfo.fromJson(Map<String, dynamic> json) {
    return CurrencyInfo(
      symbol: json['base_currency_symbol']?.toString() ?? '',
      symbolPosition:
          json['base_currency_symbol_position']?.toString() ?? 'left',
      text: json['base_currency_text']?.toString() ?? '',
      textPosition:
          json['base_currency_text_position']?.toString() ?? 'right',
      rate: json['base_currency_rate']?.toString() ?? '1.00',
    );
  }
}

class LanguageItem {
  final int id;
  final String name;
  final String code;
  final bool isRtl;
  final bool isDefault;

  LanguageItem({
    required this.id,
    required this.name,
    required this.code,
    required this.isRtl,
    required this.isDefault,
  });

  factory LanguageItem.fromJson(Map<String, dynamic> json) {
    return LanguageItem(
      id: json['id'] as int? ?? 0,
      name: json['name']?.toString() ?? '',
      code: json['code']?.toString() ?? '',
      isRtl: json['direction']?.toString() == '1',
      isDefault: json['is_default']?.toString() == '1',
    );
  }
}

class BasicSettings {
  final bool countryEnabled;
  final bool stateEnabled;
  final bool guestCheckoutEnabled;

  BasicSettings({
    required this.countryEnabled,
    required this.stateEnabled,
    required this.guestCheckoutEnabled,
  });

  factory BasicSettings.fromJson(Map<String, dynamic> json) {
    return BasicSettings(
      countryEnabled: json['event_country_status']?.toString() == '1',
      stateEnabled: json['event_state_status']?.toString() == '1',
      guestCheckoutEnabled:
          json['event_guest_checkout_status']?.toString() == '1',
    );
  }
}

/// Reusable model for a location item (country / state / city).
class LocationItem {
  final int id;
  final String name;
  final String slug;

  LocationItem({required this.id, required this.name, required this.slug});

  factory LocationItem.fromJson(Map<String, dynamic> json) {
    return LocationItem(
      id: json['id'] as int? ?? 0,
      name: json['name']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
    );
  }
}

class LocationListResponse {
  final List<LocationItem> countries;
  final List<LocationItem> states;
  final List<LocationItem> cities;

  LocationListResponse({
    this.countries = const [],
    this.states = const [],
    this.cities = const [],
  });

  factory LocationListResponse.fromCountriesJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? {};
    return LocationListResponse(
      countries: (data['countries'] as List<dynamic>? ?? [])
          .map((e) => LocationItem.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  factory LocationListResponse.fromStatesJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? {};
    return LocationListResponse(
      states: (data['states'] as List<dynamic>? ?? [])
          .map((e) => LocationItem.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  factory LocationListResponse.fromCitiesJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? {};
    return LocationListResponse(
      cities: (data['cities'] as List<dynamic>? ?? [])
          .map((e) => LocationItem.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  factory LocationListResponse.fromCountryWiseJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? {};
    return LocationListResponse(
      states: (data['states'] as List<dynamic>? ?? [])
          .map((e) => LocationItem.fromJson(e as Map<String, dynamic>))
          .toList(),
      cities: (data['cities'] as List<dynamic>? ?? [])
          .map((e) => LocationItem.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  factory LocationListResponse.fromStateWiseCitiesJson(
    Map<String, dynamic> json,
  ) {
    final data = json['data'] as Map<String, dynamic>? ?? {};
    return LocationListResponse(
      cities: (data['cities'] as List<dynamic>? ?? [])
          .map((e) => LocationItem.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}
