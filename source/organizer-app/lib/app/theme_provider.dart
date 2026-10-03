import 'package:booktkit_organizer/utils/app_logger.dart';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// A provider that manages the app's theme mode (light/dark) state.
///
/// This class extends [ChangeNotifier] to notify listeners when the theme changes.
/// It also persists the user's theme preference using SharedPreferences.
class ThemeProvider extends ChangeNotifier {
  ThemeMode _themeMode = ThemeMode.light;
  static const String _themePrefKey = 'theme_mode';

  ThemeProvider._internal();

  /// Factory constructor that returns a pre-initialized instance
  factory ThemeProvider() => _instance;

  static final ThemeProvider _instance = ThemeProvider._internal();

  /// Static method to initialize the theme provider before app starts
  static Future<ThemeProvider> initialize() async {
    await _instance._loadThemeFromPrefs();
    return _instance;
  }

  /// Returns the current theme mode
  ThemeMode get themeMode => _themeMode;

  /// Returns true if the current theme is dark mode
  bool get isDarkMode => _themeMode == ThemeMode.dark;

  /// Toggles between light and dark theme
  void toggleTheme() {
    _themeMode = _themeMode == ThemeMode.light
        ? ThemeMode.dark
        : ThemeMode.light;
    _saveThemeToPrefs();
    notifyListeners();
  }

  /// Sets the theme mode explicitly
  void setThemeMode(ThemeMode mode) {
    _themeMode = mode;
    _saveThemeToPrefs();
    notifyListeners();
  }

  /// Loads the saved theme preference from SharedPreferences
  Future<void> _loadThemeFromPrefs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final savedTheme = prefs.getString(_themePrefKey);
      AppLogger.info('🎨 Loading theme from prefs: $savedTheme');
      if (savedTheme != null) {
        _themeMode = savedTheme == 'dark' ? ThemeMode.dark : ThemeMode.light;
        AppLogger.info('🎨 Theme loaded: $_themeMode');
      } else {
        // No saved preference, default to light mode
        _themeMode = ThemeMode.light;
        AppLogger.info('🎨 No saved theme, using default light mode');
      }
    } catch (e) {
      // If loading fails, just use the default light theme
      _themeMode = ThemeMode.light;
      AppLogger.info('❌ Error loading theme preference: $e');
    }
  }

  /// Saves the current theme preference to SharedPreferences
  Future<void> _saveThemeToPrefs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final themeString = _themeMode == ThemeMode.dark ? 'dark' : 'light';
      await prefs.setString(_themePrefKey, themeString);
      AppLogger.info('💾 Theme saved: $themeString');
    } catch (e) {
      AppLogger.info('❌ Error saving theme preference: $e');
    }
  }
}

