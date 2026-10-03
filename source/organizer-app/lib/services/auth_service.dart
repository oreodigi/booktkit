import 'package:dio/dio.dart';
import 'package:booktkit_organizer/app/urls.dart';
import 'package:booktkit_organizer/features/auth/data/models/edit_profile_response.dart';
import 'package:booktkit_organizer/features/auth/data/models/login_request.dart';
import 'package:booktkit_organizer/features/auth/data/models/login_response.dart';
import 'package:booktkit_organizer/features/auth/data/models/signup_request.dart';
import 'package:booktkit_organizer/features/auth/data/models/signup_response.dart';
import 'package:booktkit_organizer/services/api_client.dart';

/// Auth Service for handling authentication API calls
class AuthService {
  final ApiClient _apiClient = ApiClient();

  /// Login Organizer
  Future<LoginResponse> login(LoginRequest request) async {
    try {
      final response = await _apiClient.post(
        Urls.orgLogin,
        data: request.toJson(),
      );

      if (response.statusCode == 200 || response.statusCode == 201) {
        return LoginResponse.fromJson(response.data);
      } else {
        throw Exception('Login failed: ${response.statusMessage}');
      }
    } on DioException catch (e) {
      if (e.response != null) {
        throw Exception(e.response?.data['message'] ?? 'Login failed');
      } else {
        throw Exception('Network error: ${e.message}');
      }
    } catch (e) {
      throw Exception('Unexpected error: $e');
    }
  }

  /// Signup vendor
  Future<SignupResponse> signup(SignupRequest request) async {
    try {
      final response = await _apiClient.post(
        Urls.orgSignup,
        data: request.toJson(),
      );

      if (response.statusCode == 200 || response.statusCode == 201) {
        return SignupResponse.fromJson(response.data);
      } else {
        throw Exception('Signup failed: ${response.statusMessage}');
      }
    } on DioException catch (e) {
      if (e.response != null) {
        throw Exception(e.response?.data['message'] ?? 'Signup failed');
      } else {
        throw Exception('Network error: ${e.message}');
      }
    } catch (e) {
      throw Exception('Unexpected error: $e');
    }
  }

  /// Logout vendor
  Future<void> logout() async {
    try {
      await _apiClient.post(Urls.orgLogout);
      _apiClient.removeToken();
    } catch (e) {
      throw Exception('Logout failed: $e');
    }
  }

  /// Change / Update password
  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
    required String newPasswordConfirmation,
  }) async {
    try {
      final response = await _apiClient.post(
        Urls.updatePassword,
        data: {
          'current_password': currentPassword,
          'new_password': newPassword,
          'new_password_confirmation': newPasswordConfirmation,
        },
      );
      final isSuccess = response.data['success'] == true;
      if (!isSuccess) {
        throw Exception(
          response.data['message']?.toString() ?? 'Failed to update password',
        );
      }
    } on DioException catch (e) {
      String message = 'Failed to update password';
      if (e.response != null && e.response?.data != null) {
        message = e.response?.data['message']?.toString() ?? message;
      }
      throw Exception(message);
    }
  }

  /// Update organizer profile
  Future<void> updateProfile({
    required String email,
    required String phone,
    required String username,
    String? facebook,
    String? twitter,
    String? linkedin,
    // English fields
    String? enName,
    String? enDesignation,
    String? enCountry,
    String? enCity,
    String? enState,
    String? enZipCode,
    String? enAddress,
    String? enDetails,
    // Arabic fields
    String? arName,
    String? arDesignation,
    String? arCountry,
    String? arCity,
    String? arState,
    String? arZipCode,
    String? arAddress,
    String? arDetails,
    // Profile photo
    String? photoPath,
  }) async {
    try {
      final Map<String, dynamic> fields = {
        'email': email,
        'phone': phone,
        'username': username,
        if (facebook != null && facebook.isNotEmpty) 'facebook': facebook,
        if (twitter != null && twitter.isNotEmpty) 'twitter': twitter,
        if (linkedin != null && linkedin.isNotEmpty) 'linkedin': linkedin,
        'en_name': ?enName,
        'en_designation': ?enDesignation,
        'en_country': ?enCountry,
        'en_city': ?enCity,
        'en_state': ?enState,
        'en_zip_code': ?enZipCode,
        'en_address': ?enAddress,
        'en_details': ?enDetails,
        'ar_name': ?arName,
        'ar_designation': ?arDesignation,
        'ar_country': ?arCountry,
        'ar_city': ?arCity,
        'ar_state': ?arState,
        'ar_zip_code': ?arZipCode,
        'ar_address': ?arAddress,
        'ar_details': ?arDetails,
        if (photoPath != null)
          'photo': await MultipartFile.fromFile(
            photoPath,
            filename: 'photo.jpg',
          ),
      };

      final formData = FormData.fromMap(fields);
      final response = await _apiClient.post(
        Urls.updateProfile,
        data: formData,
      );

      final isSuccess = response.data['success'] == true;
      if (!isSuccess) {
        throw Exception(
          response.data['message']?.toString() ?? 'Failed to update profile',
        );
      }
    } on DioException catch (e) {
      String message = 'Failed to update profile';
      if (e.response != null && e.response?.data != null) {
        message = e.response?.data['message']?.toString() ?? message;
      }
      throw Exception(message);
    }
  }

  /// Get Edit Profile Data
  Future<EditProfileResponse> getEditProfile() async {
    try {
      final response = await _apiClient.get(Urls.editProfile);

      if (response.statusCode == 200) {
        return EditProfileResponse.fromJson(response.data);
      } else {
        throw Exception('Failed to fetch profile: ${response.statusMessage}');
      }
    } on DioException catch (e) {
      if (e.response != null) {
        throw Exception(
          e.response?.data['message'] ?? 'Failed to fetch profile',
        );
      } else {
        throw Exception('Network error: ${e.message}');
      }
    } catch (e) {
      throw Exception('Unexpected error: $e');
    }
  }
}
