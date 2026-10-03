import 'package:booktkit_organizer/utils/app_logger.dart';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// A provider that manages the app's locale (language and text direction) state.
///
/// This class extends [ChangeNotifier] to notify listeners when the locale changes.
/// It also persists the user's language preference using SharedPreferences.
class LocaleProvider extends ChangeNotifier {
  Locale _locale = const Locale('en', 'US');
  static const String _localePrefKey = 'app_locale';

  LocaleProvider._internal();

  /// Factory constructor that returns a pre-initialized instance
  factory LocaleProvider() => _instance;

  static final LocaleProvider _instance = LocaleProvider._internal();

  /// Static method to initialize the locale provider before app starts
  static Future<LocaleProvider> initialize() async {
    await _instance._loadLocaleFromPrefs();
    return _instance;
  }

  /// Returns the current locale
  Locale get locale => _locale;

  /// Returns true if the current locale is RTL (Arabic)
  bool get isRTL => _locale.languageCode == 'ar';

  /// Returns the current language name
  String get currentLanguageName {
    switch (_locale.languageCode) {
      case 'ar':
        return 'Arabic';
      case 'es':
        return 'Spanish';
      case 'en':
      default:
        return 'English';
    }
  }

  /// Sets the locale explicitly
  void setLocale(String languageName) {
    Locale newLocale;
    switch (languageName) {
      case 'Arabic':
        newLocale = const Locale('ar', 'SA');
        break;
      case 'Spanish':
        newLocale = const Locale('es', 'ES');
        break;
      case 'English':
      default:
        newLocale = const Locale('en', 'US');
        break;
    }

    if (_locale != newLocale) {
      _locale = newLocale;
      _saveLocaleToPrefs();
      notifyListeners();
      AppLogger.info('🌍 Locale changed to: ${_locale.languageCode} (RTL: $isRTL)');
    }
  }

  /// Loads the saved locale preference from SharedPreferences
  Future<void> _loadLocaleFromPrefs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final savedLanguageCode = prefs.getString(_localePrefKey);
      AppLogger.info('🌍 Loading locale from prefs: $savedLanguageCode');

      if (savedLanguageCode != null) {
        switch (savedLanguageCode) {
          case 'ar':
            _locale = const Locale('ar', 'SA');
            break;
          case 'es':
            _locale = const Locale('es', 'ES');
            break;
          case 'en':
          default:
            _locale = const Locale('en', 'US');
            break;
        }
        AppLogger.info('🌍 Locale loaded: ${_locale.languageCode}');
      } else {
        // No saved preference, default to English
        _locale = const Locale('en', 'US');
        AppLogger.info('🌍 No saved locale, using default English');
      }
    } catch (e) {
      // If loading fails, just use the default English
      _locale = const Locale('en', 'US');
      AppLogger.info('❌ Error loading locale preference: $e');
    }
  }

  /// Saves the current locale preference to SharedPreferences
  Future<void> _saveLocaleToPrefs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_localePrefKey, _locale.languageCode);
      AppLogger.info('💾 Locale saved: ${_locale.languageCode}');
    } catch (e) {
      AppLogger.info('❌ Error saving locale preference: $e');
    }
  }
}

