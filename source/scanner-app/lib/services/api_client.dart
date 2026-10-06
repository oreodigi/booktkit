import 'dart:convert';
import 'package:http/http.dart' as http;

import '../home/models/dashboard_models.dart';

const String apiBaseUrl = 'https://booktkit.com';

enum UserRole { admin, organizer, staff }

class CheckQrResponse {
  final String alertType;
  final String message;
  final String? bookingId;

  const CheckQrResponse({
    required this.alertType,
    required this.message,
    this.bookingId,
  });

  bool get isSuccess => alertType.toLowerCase() == 'success';

  factory CheckQrResponse.fromJson(Map<String, dynamic> json) {
    return CheckQrResponse(
      alertType: (json['alert_type'] ?? json['type'] ?? 'error').toString(),
      message: (json['message'] ?? '').toString(),
      bookingId: json['booking_id']?.toString(),
    );
  }

  Map<String, dynamic> toJson() => {
    'alert_type': alertType,
    'message': message,
    if (bookingId != null) 'booking_id': bookingId,
  };
}

class UserProfile {
  final UserRole role;
  final int id;
  final String username;
  final String? firstName;
  final String? lastName;
  final String? email;
  final String? phone;
  final String? photoUrl;

  UserProfile({
    required this.role,
    required this.id,
    required this.username,
    this.firstName,
    this.lastName,
    this.email,
    this.phone,
    this.photoUrl,
  });

  Map<String, dynamic> toJson() => {
    'role': role.name,
    'id': id,
    'username': username,
    'firstName': firstName,
    'lastName': lastName,
    'email': email,
    'phone': phone,
    'photoUrl': photoUrl,
  };

  factory UserProfile.fromJson(Map<String, dynamic> json) {
    final role = json['role'] == 'admin' ? UserRole.admin : (json['role'] == 'staff' ? UserRole.staff : UserRole.organizer);
    return UserProfile(
      role: role,
      id: (json['id'] as num).toInt(),
      username: json['username'] ?? '',
      firstName: json['firstName'],
      lastName: json['lastName'],
      email: json['email'],
      phone: json['phone'],
      photoUrl: json['photoUrl'],
    );
  }

  String get displayName {
    if (role == UserRole.admin) {
      final hasNames =
          (firstName?.isNotEmpty == true) || (lastName?.isNotEmpty == true);
      return hasNames
          ? [
              firstName,
              lastName,
            ].whereType<String>().where((e) => e.isNotEmpty).join(' ')
          : username;
    } else {
      return username.toUpperCase();
    }
  }
}

class LoginSuccess {
  final String token;
  final UserProfile profile;
  LoginSuccess(this.token, this.profile);
}

class ApiClient {
  final http.Client _client;
  ApiClient({http.Client? client}) : _client = client ?? http.Client();

  Uri _buildUri(String path, Map<String, String> query) {
    final base = apiBaseUrl.replaceAll(RegExp(r"/+$"), '');
    return Uri.parse('$base$path').replace(queryParameters: query);
  }

  Future<LoginSuccess> loginUnified({
    required String username,
    required String password,
    required String deviceName,
    int? organizerId,
  }) async {
    // Try admin first, then organizer.
    final admin = await _tryLogin(
      path: '/api/scanner/admin/login/submit',
      username: username,
      password: password,
      deviceName: deviceName,
      role: UserRole.admin,
    );
    if (admin != null) return admin;

    final organizer = await _tryLogin(
      path: '/api/scanner/organizer/login/submit',
      username: username,
      password: password,
      deviceName: deviceName,
      role: UserRole.organizer,
    );
    if (organizer != null) return organizer;

    if (organizerId != null) {
      final staff = await _tryStaffLogin(organizerId: organizerId, username: username, password: password, deviceName: deviceName);
      if (staff != null) return staff;
    }
    throw Exception('Invalid credentials');
  }

  Future<LoginSuccess?> _tryStaffLogin({required int organizerId,required String username,required String password,required String deviceName}) async {
    final uri=_buildUri('/api/scanner/staff/login/submit',const {});
    final resp=await _client.post(uri,headers:const {'Accept':'application/json'},body:{'organizer_id':organizerId.toString(),'username':username,'password':password,'device_name':deviceName});
    if(resp.statusCode!=200)return null; final data=json.decode(resp.body) as Map<String,dynamic>; final s=data['staff'] as Map<String,dynamic>; return LoginSuccess(data['token'].toString(),UserProfile(role:UserRole.staff,id:(s['id'] as num).toInt(),username:(s['name']??username).toString()));
  }

  Future<LoginSuccess?> _tryLogin({
    required String path,
    required String username,
    required String password,
    required String deviceName,
    required UserRole role,
  }) async {
    if (apiBaseUrl.contains('YOUR_API_BASE_URL_HERE')) {
      throw Exception('Please set apiBaseUrl in lib/services/api_client.dart');
    }

    final uri = _buildUri(path, {
      'username': username,
      'password': password,
      'device_name': deviceName,
    });

    final resp = await _client.post(
      uri,
      headers: const {'Accept': 'application/json'},
    );

    if (resp.statusCode != 200) return null;
    final Map<String, dynamic> data = json.decode(resp.body);
    if (data['status']?.toString().toLowerCase() != 'success') return null;

    final token = data['token']?.toString();
    if (token == null || token.isEmpty) return null;

    if (role == UserRole.admin && data['admin'] is Map<String, dynamic>) {
      final a = data['admin'] as Map<String, dynamic>;
      final profile = UserProfile(
        role: UserRole.admin,
        id: (a['id'] as num).toInt(),
        username: (a['username'] ?? '').toString(),
        firstName: a['first_name']?.toString(),
        lastName: a['last_name']?.toString(),
        email: a['email']?.toString(),
        phone: a['phone']?.toString(),
        photoUrl: a['image']?.toString(),
      );
      return LoginSuccess(token, profile);
    }

    if (role == UserRole.organizer &&
        data['organizer'] is Map<String, dynamic>) {
      final o = data['organizer'] as Map<String, dynamic>;
      final profile = UserProfile(
        role: UserRole.organizer,
        id: (o['id'] as num).toInt(),
        username: (o['username'] ?? '').toString(),
        firstName: null,
        lastName: null,
        email: o['email']?.toString(),
        phone: o['phone']?.toString(),
        photoUrl: o['photo']?.toString(),
      );
      return LoginSuccess(token, profile);
    }

    return null;
  }

  /// Check a scanned QR code against the backend.
  ///
  /// Sends POST form fields `{ booking_id: ... }` to either the admin or
  /// organizer endpoint based on the provided [role]. Returns the server's
  /// JSON payload mapped into [CheckQrResponse].
  Future<CheckQrResponse> checkQrCode({
    required String token,
    required UserRole role,
    required String bookingId,
    String direction = 'entry',
    int? gateId,
  }) async {
    if (apiBaseUrl.contains('YOUR_API_BASE_URL_HERE')) {
      throw Exception('Please set apiBaseUrl in lib/services/api_client.dart');
    }

    final path = role == UserRole.admin ? '/api/scanner/admin/check-qrcode' : (role == UserRole.staff ? '/api/scanner/staff/check-qrcode' : '/api/scanner/organizer/check-qrcode');
    final uri = _buildUri(path, const {});

    final resp = await _client.post(
      uri,
      headers: {
        'Accept': 'application/json',
        // If your backend expects a different token header, adjust here.
        'Authorization': 'Bearer $token',
      },
      body: {'booking_id': bookingId, 'direction': direction, if(gateId!=null) 'gate_id':gateId.toString()},
    );

    // Parse gracefully even on non-200s if server returns JSON
    Map<String, dynamic>? jsonBody;
    try {
      jsonBody = json.decode(resp.body) as Map<String, dynamic>;
    } catch (_) {}

    if (jsonBody != null && jsonBody.isNotEmpty) {
      return CheckQrResponse.fromJson(jsonBody);
    }

    // Fallback generic errors
    if (resp.statusCode >= 200 && resp.statusCode < 300) {
      return const CheckQrResponse(alertType: 'success', message: 'Verified');
    }
    return CheckQrResponse(
      alertType: 'error',
      message: 'Verification failed (${resp.statusCode})',
    );
  }

  /// Fetch dashboard data including events, tickets, and statistics.
  ///
  /// Returns dashboard data with events list, scanned/unscanned tickets,
  /// and various statistics for the authenticated user.
  Future<DashboardData> getDashboardData({
    required String token,
    required UserRole role,
  }) async {
    if (apiBaseUrl.contains('YOUR_API_BASE_URL_HERE')) {
      throw Exception('Please set apiBaseUrl in lib/services/api_client.dart');
    }

    final path = role == UserRole.admin
        ? '/api/scanner/admin/events'
        : '/api/scanner/organizer/events';
    final uri = _buildUri(path, const {});

    final resp = await _client.get(
      uri,
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );

    if (resp.statusCode != 200) {
      // Log full error for debugging
      throw Exception(
        'Failed to load dashboard data: ${resp.statusCode} - ${resp.body}',
      );
    }

    final Map<String, dynamic> data = json.decode(resp.body);

    if (data['status']?.toString().toLowerCase() != 'success') {
      throw Exception('Dashboard data fetch failed');
    }

    return DashboardData.fromJson(data);
  }

  /// Update ticket scan status.
  ///
  /// Changes the scan status of a ticket (scanned/unscanned).
  Future<bool> updateTicketStatus({
    required String token,
    required UserRole role,
    required String bookingId,
    required String ticketId,
    required String status,
  }) async {
    if (apiBaseUrl.contains('YOUR_API_BASE_URL_HERE')) {
      throw Exception('Please set apiBaseUrl in lib/services/api_client.dart');
    }

    final path = role == UserRole.admin
        ? '/api/scanner/admin/ticket/scanned-status-change'
        : '/api/scanner/organizer/ticket/scanned-status-change';
    final uri = _buildUri(path, {
      'booking_id': bookingId,
      'ticket_id': ticketId,
      'status': status,
    });

    final resp = await _client.post(
      uri,
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );

    if (resp.statusCode != 200) {
      throw Exception('Failed to update ticket status: ${resp.statusCode}');
    }

    final Map<String, dynamic> data = json.decode(resp.body);
    final success = data['status']?.toString().toLowerCase() == 'success';
    return success;
  }
}
