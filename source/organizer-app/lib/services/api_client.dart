import 'dart:convert';

import 'package:booktkit_organizer/app/urls.dart';
import 'package:booktkit_organizer/features/auth/ui/screens/login_screen.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_snackbar.dart';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../app/app.dart';
import '../utils/app_logger.dart';
import 'package:dio/dio.dart';

/// API Client using Dio for all network requests
class ApiClient {
  static final ApiClient _instance = ApiClient._internal();
  late final Dio _dio;
  String _acceptLanguage = 'en'; // Default language

  factory ApiClient() {
    return _instance;
  }

  ApiClient._internal() {
    _dio = Dio(
      BaseOptions(
        baseUrl: Urls.baseUrl,
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 30),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Accept-Language': _acceptLanguage,
        },
      ),
    );

    // Add interceptors for logging and error handling
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          // Only set Accept-Language from prefs if the caller hasn't
          // already supplied a per-request override.
          if (!options.headers.containsKey('Accept-Language')) {
            try {
              final prefs = await SharedPreferences.getInstance();
              final localeString = prefs.getString('app_locale');
              if (localeString != null) {
                final parts = localeString.split('_');
                if (parts.isNotEmpty) {
                  _acceptLanguage = parts[0];
                  options.headers['Accept-Language'] = _acceptLanguage;
                }
              }
            } catch (e) {
              AppLogger.e('Error loading language for API: $e');
            }
          }

          AppLogger.info('🌐 REQUEST[${options.method}] => PATH: ${options.path}');
          AppLogger.d('🌐 HEADERS: ${options.headers}');
          AppLogger.d('🌐 DATA: ${options.data}');
          return handler.next(options);
        },
        onResponse: (response, handler) {
          AppLogger.info(
            '✅ RESPONSE[${response.statusCode}] => PATH: ${response.requestOptions.path}',
          );
          AppLogger.d('✅ DATA: ${response.data}');
          return handler.next(response);
        },
        onError: (DioException e, handler) async {
          AppLogger.e(
            '❌ ERROR[${e.response?.statusCode}] => PATH: ${e.requestOptions.path}',
          );
          AppLogger.e('❌ ERROR: ${e.message}');
          if (e.response?.data != null) {
            try {
              AppLogger.e(
                '❌ RESPONSE DATA: ${jsonEncode(e.response?.data)}',
              );
            } catch (_) {
              AppLogger.e('❌ RESPONSE DATA: ${e.response?.data}');
            }
          }

          // Handle 401 Unauthorized - Token expired
          if (e.response?.statusCode == 401) {
            await _handleTokenExpiration();
          }

          return handler.next(e);
        },
      ),
    );
  }

  Dio get dio => _dio;

  /// Handle token expiration (401 error)
  Future<void> _handleTokenExpiration() async {
    AppLogger.w('🔒 Token expired! Logging out and redirecting to login...');

    // Clear only auth-related data, preserve app config cache
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('token');
      await prefs.remove('user_id');
      await prefs.remove('username');
      await prefs.remove('email');
      await prefs.remove('is_logged_in');
    } catch (e) {
      AppLogger.e('Error clearing auth data: $e');
    }

    // Remove token from API client
    removeToken();

    // Navigate to login screen
    final context = navigatorKey.currentContext;
    if (context != null) {
      // Clear all routes and navigate to login
      if (!context.mounted) return;

      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (_) => const LoginScreen()),
        (route) => false,
      );

      // Show message to user
      CustomSnackBar.show(
        context: context,
        message: 'Your session has expired. Please login again.',
        type: SnackBarType.warning,
      );
    }
  }

  /// Initialize token from storage on app start
  Future<void> initializeToken() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      if (token != null && token.isNotEmpty) {
        _dio.options.headers['Authorization'] = 'Bearer $token';
        AppLogger.info('🔑 Token loaded from storage');
      }
    } catch (e) {
      AppLogger.e('❌ Error loading token: $e');
    }
  }

  /// Set authorization token
  void setToken(String token) {
    _dio.options.headers['Authorization'] = 'Bearer $token';
  }

  /// Remove authorization token
  void removeToken() {
    _dio.options.headers.remove('Authorization');
  }

  /// Set Accept-Language header
  void setLanguage(String languageHeader) {
    _acceptLanguage = languageHeader;
    _dio.options.headers['Accept-Language'] = languageHeader;
    AppLogger.info('🌍 Language changed to: $languageHeader');
  }

  /// GET request
  Future<Response> get(
    String path, {
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      return await _dio.get(
        path,
        queryParameters: queryParameters,
        options: options,
      );
    } catch (e) {
      rethrow;
    }
  }

  /// POST request
  Future<Response> post(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      return await _dio.post(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
      );
    } catch (e) {
      rethrow;
    }
  }

  /// PUT request
  Future<Response> put(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      return await _dio.put(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
      );
    } catch (e) {
      rethrow;
    }
  }

  /// DELETE request
  Future<Response> delete(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      return await _dio.delete(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
      );
    } catch (e) {
      rethrow;
    }
  }
}
