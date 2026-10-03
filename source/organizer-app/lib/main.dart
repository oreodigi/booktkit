import 'package:booktkit_organizer/app/app.dart';
import 'package:booktkit_organizer/app/locale_provider.dart';
import 'package:booktkit_organizer/app/theme_provider.dart';
import 'package:booktkit_organizer/services/api_client.dart';
import 'package:booktkit_organizer/utils/app_logger.dart';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await _initializeApiClient();
  final themeProvider = await ThemeProvider.initialize();
  final localeProvider = await LocaleProvider.initialize();
  runApp(
    BooktkitOrganizer(
      themeProvider: themeProvider,
      localeProvider: localeProvider,
    ),
  );
}

Future<void> _initializeApiClient() async {
  try {
    final apiClient = ApiClient();
    final prefs = await SharedPreferences.getInstance();

    // Initialize token
    await apiClient.initializeToken();

    // Initialize language from preferences
    final localeString = prefs.getString('app_locale');
    if (localeString != null) {
      final parts = localeString.split('_');
      if (parts.isNotEmpty) {
        // Send only language code (e.g., 'en' instead of 'en-US')
        final languageCode = parts[0];
        apiClient.setLanguage(languageCode);
        AppLogger.info('🌍 Initialized API language: $languageCode');
      }
    }
  } catch (e) {
    AppLogger.e('❌ Error initializing API client: $e');
  }
}
