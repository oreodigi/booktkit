import 'dart:convert';
import 'package:booktkit_customer/network_services/core/auth_services.dart';
import 'package:booktkit_customer/features/account/data/models/customer_model.dart';
import 'package:booktkit_customer/network_services/core/navigation_service.dart';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:booktkit_customer/features/auth/ui/screens/login_screen.dart';
import 'package:booktkit_customer/features/common/ui/widgets/custom_snack_bar_widget.dart';

class AuthProvider extends ChangeNotifier {
  final TextEditingController emailController = TextEditingController();
  final TextEditingController passwordController = TextEditingController();
  final TextEditingController firstNameController = TextEditingController();
  final TextEditingController lastNameController = TextEditingController();
  final TextEditingController usernameController = TextEditingController();
  final TextEditingController confirmPasswordController =
      TextEditingController();

  bool _obscurePassword = true;
  bool _isLoading = false;
  String? _errorMessage;
  String? _token;
  Map<String, dynamic>? _customer;
  RouteSettings? _pendingRedirect;
  bool _navigatingToLogin = false;

  bool get obscurePassword => _obscurePassword;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  String? get token => _token;
  Map<String, dynamic>? get customer => _customer;

  CustomerModel? get customerModel =>
      _customer == null ? null : CustomerModel.fromJson(_customer!);
  RouteSettings? get pendingRedirect => _pendingRedirect;
  bool get navigatingToLogin => _navigatingToLogin;

  void togglePasswordVisibility() {
    _obscurePassword = !_obscurePassword;
    notifyListeners();
  }

  Future<void> tryLoadSession() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('auth_token');
    final customerJson = prefs.getString('auth_customer');
    if (customerJson != null) {
      _customer = json.decode(customerJson) as Map<String, dynamic>;
    }
    notifyListeners();
  }

  Future<bool> login() async {
    final usernameOrEmail = emailController.text.trim();
    final password = passwordController.text;
    if (usernameOrEmail.isEmpty || password.isEmpty) {
      _errorMessage = 'Please enter username and password';
      notifyListeners();
      return false;
    }
    _setLoading(true);
    _errorMessage = null;
    try {
      final res = await AuthServices.login(
        username: usernameOrEmail,
        password: password,
      );
      final token = res['token']?.toString();
      final customer = res['customer'] as Map<String, dynamic>?;
      if (token == null || customer == null) {
        _errorMessage = 'Invalid response from server';
        _setLoading(false);
        return false;
      }
      _token = token;
      _customer = customer;
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('auth_token', token);
      await prefs.setString('auth_customer', json.encode(customer));
      _navigatingToLogin = false;
      _setLoading(false);
      return true;
    } catch (e) {
      _errorMessage = 'Login failed';
      _setLoading(false);
      return false;
    }
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('auth_customer');
    _token = null;
    _customer = null;
    notifyListeners();
  }

  void setPendingRedirect(RouteSettings? settings) {
    _pendingRedirect = settings;
  }

  void clearPendingRedirect() {
    _pendingRedirect = null;
  }

  Future<void> onAuthExpired({RouteSettings? from, String? message}) async {
    await logout();
    setPendingRedirect(from);
    final nav = NavigationService.navigator;
    if (nav == null) return;
    if (_navigatingToLogin) return;
    if (message != null) {
      final ctx = nav.context;
      if (ctx.mounted) {
        CustomSnackBar.show(ctx, message);
      }
    }
    _navigatingToLogin = true;
    NavigationService.pushAnimated(
      const LoginScreen(redirectToHome: false),
    ).whenComplete(() {
      _navigatingToLogin = false;
    });
  }

  Future<void> setCustomer(Map<String, dynamic>? customer) async {
    _customer = customer;
    final prefs = await SharedPreferences.getInstance();
    if (customer == null) {
      await prefs.remove('auth_customer');
    } else {
      await prefs.setString('auth_customer', json.encode(customer));
    }
    notifyListeners();
  }

  Future<void> setCustomerModel(CustomerModel? model) async {
    if (model == null) return await setCustomer(null);
    await setCustomer(model.toJson());
  }

  Future<bool> signup() async {
    final f = firstNameController.text.trim();
    final l = lastNameController.text.trim();
    final u = usernameController.text.trim();
    final e = emailController.text.trim();
    final p = passwordController.text;
    final pc = confirmPasswordController.text;
    if ([f, u, e, p, pc].any((v) => v.isEmpty)) {
      _errorMessage = 'Please fill all required fields';
      notifyListeners();
      return false;
    }
    if (p != pc) {
      _errorMessage = 'Passwords do not match';
      notifyListeners();
      return false;
    }
    _setLoading(true);
    _errorMessage = null;
    try {
      final res = await AuthServices.signup(
        firstName: f,
        lastName: l,
        username: u,
        email: e,
        password: p,
        confirmPassword: pc,
      );
      final status = (res['status'] ?? '').toString().toLowerCase();
      if (status != 'success') {
        String? msg;
        if (res['message'] is String) msg = res['message'];
        if (msg == null && res['errors'] is Map) {
          final errors = res['errors'] as Map;
          if (errors.isNotEmpty) {
            final firstVal = errors.values.first;
            if (firstVal is List && firstVal.isNotEmpty) {
              msg = firstVal.first.toString();
            } else if (firstVal is String) {
              msg = firstVal;
            }
          }
        }
        _errorMessage = msg ?? 'Signup failed';
        _setLoading(false);
        return false;
      }
      _setLoading(false);
      return true;
    } catch (e) {
      _errorMessage = 'Signup failed';
      _setLoading(false);
      return false;
    }
  }

  void _setLoading(bool v) {
    _isLoading = v;
    notifyListeners();
  }
}
