import 'dart:io';
import 'package:device_info_plus/device_info_plus.dart';
import 'package:booktkit_organizer/features/auth/data/models/login_request.dart';
import 'package:booktkit_organizer/features/auth/data/models/login_response.dart';
import 'package:booktkit_organizer/features/auth/data/models/signup_request.dart';
import 'package:booktkit_organizer/features/auth/data/models/signup_response.dart';
import 'package:booktkit_organizer/services/api_client.dart';
import 'package:booktkit_organizer/services/auth_service.dart';
import 'package:booktkit_organizer/utils/app_logger.dart';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Auth Provider for managing authentication state
class AuthProvider with ChangeNotifier {
  final AuthService _authService = AuthService();

  bool _isLoading = false;
  String? _errorMessage;
  LoginResponse? _loginResponse;
  Organizer? _organizer;

  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  LoginResponse? get loginResponse => _loginResponse;
  Organizer? get org => _organizer;
  bool get isLoggedIn => _organizer != null;

  /// Login method
  Future<bool> login(String username, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      // Get the real device name
      String deviceName = 'mobile';
      try {
        final info = DeviceInfoPlugin();
        if (Platform.isAndroid) {
          final android = await info.androidInfo;
          deviceName = android.model;
        } else if (Platform.isIOS) {
          final ios = await info.iosInfo;
          deviceName = ios.name;
        }
      } catch (_) {
        // keep default 'mobile' if device info fails
      }

      final request = LoginRequest(
        username: username,
        password: password,
        deviceName: deviceName,
      );

      _loginResponse = await _authService.login(request);
      _organizer = _loginResponse?.organizer;

      // Save token and user data
      if (_loginResponse != null) {
        await _saveUserData(_loginResponse!);
        ApiClient().setToken(_loginResponse!.token);
      }

      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  /// Signup method
  Future<SignupResponse?> signup({
    required String username,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String name,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final request = SignupRequest(
        username: username,
        email: email,
        password: password,
        passwordConfirmation: passwordConfirmation,
        name: name,
      );

      final response = await _authService.signup(request);

      _isLoading = false;
      notifyListeners();
      return response;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return null;
    }
  }

  /// Save user data to local storage
  Future<void> _saveUserData(LoginResponse response) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('token', response.token);
    await prefs.setInt('user_id', response.organizer.id);
    await prefs.setString('username', response.organizer.username);
    await prefs.setString('email', response.organizer.email);
    await prefs.setString('photo', response.organizer.photo);
    await prefs.setString('phone', response.organizer.phone);
    await prefs.setBool('is_logged_in', true);
  }

  /// Load user data from local storage
  Future<bool> loadUserData() async {
    final prefs = await SharedPreferences.getInstance();
    final isLoggedIn = prefs.getBool('is_logged_in') ?? false;

    if (isLoggedIn) {
      final token = prefs.getString('token');
      final userId = prefs.getInt('user_id');
      final username = prefs.getString('username');
      final email = prefs.getString('email');
      final photo = prefs.getString('photo') ?? '';
      final phone = prefs.getString('phone') ?? '';

      if (token != null &&
          userId != null &&
          username != null &&
          email != null) {
        // Set token in API client
        ApiClient().setToken(token);
        AppLogger.info('🔑 Session restored - Token loaded for user: $username');

        // Recreate basic user object from saved data
        _organizer = Organizer(
          id: userId,
          username: username,
          email: email,
          photo: photo,
          phone: phone,
          password: '',
          status: '1',
          amount: '0.00',
          createdAt: '',
          updatedAt: '',
          facebook: '',
          twitter: '',
          linkedin: '',
          themeVersion: '',
        );

        notifyListeners();
        return true;
      }
    }

    notifyListeners();
    return false;
  }

  /// Logout method
  Future<void> logout() async {
    try {
      await _authService.logout();
      await _clearUserData();
      _organizer = null;
      _loginResponse = null;
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
    }
  }

  /// Clear user data from local storage
  Future<void> _clearUserData() async {
    final prefs = await SharedPreferences.getInstance();
    // Only remove auth-related keys, preserve app config cache
    await prefs.remove('token');
    await prefs.remove('user_id');
    await prefs.remove('username');
    await prefs.remove('email');
    await prefs.remove('photo');
    await prefs.remove('phone');
    await prefs.remove('is_logged_in');
  }

  /// Clear error message
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }
}
