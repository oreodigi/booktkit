import 'package:booktkit_organizer/features/auth/data/models/edit_profile_response.dart';
import 'package:booktkit_organizer/services/auth_service.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

class EditProfileProvider extends ChangeNotifier {
  final AuthService _authService = AuthService();
  final ImagePicker _picker = ImagePicker();

  // ── Fetch state ────────────────────────────────────────────────────────────
  bool _isLoading = false;
  String? _fetchError;
  EditProfileData? _profileData;

  bool get isLoading => _isLoading;
  String? get fetchError => _fetchError;
  EditProfileData? get profileData => _profileData;

  // ── Save state ─────────────────────────────────────────────────────────────
  bool _isSaving = false;
  String? _saveError;

  bool get isSaving => _isSaving;
  String? get saveError => _saveError;

  // ── Photo state ────────────────────────────────────────────────────────────
  XFile? _photo;
  bool _hasPhoto = false;
  String? _existingPhotoUrl;

  XFile? get photo => _photo;
  bool get hasPhoto => _hasPhoto;
  String? get existingPhotoUrl => _existingPhotoUrl;

  // ── Actions ────────────────────────────────────────────────────────────────

  /// Resets state so the screen starts fresh each visit.
  void reset() {
    _isLoading = false;
    _fetchError = null;
    _profileData = null;
    _isSaving = false;
    _saveError = null;
    _photo = null;
    _hasPhoto = false;
    _existingPhotoUrl = null;
    notifyListeners();
  }

  Future<void> fetchProfile() async {
    _isLoading = true;
    _fetchError = null;
    notifyListeners();

    try {
      final response = await _authService.getEditProfile();
      _profileData = response.data;
      _existingPhotoUrl = response.data.organizer.photo;
      _hasPhoto = (_existingPhotoUrl ?? '').isNotEmpty;
    } catch (e) {
      _fetchError = e.toString().replaceFirst('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> saveProfile({
    required String email,
    required String phone,
    required String username,
    String? facebook,
    String? twitter,
    String? linkedin,
    String? enName,
    String? enDesignation,
    String? enCountry,
    String? enCity,
    String? enState,
    String? enZipCode,
    String? enAddress,
    String? enDetails,
    String? arName,
    String? arDesignation,
    String? arCountry,
    String? arCity,
    String? arState,
    String? arZipCode,
    String? arAddress,
    String? arDetails,
  }) async {
    _isSaving = true;
    _saveError = null;
    notifyListeners();

    try {
      await _authService.updateProfile(
        email: email,
        phone: phone,
        username: username,
        facebook: facebook,
        twitter: twitter,
        linkedin: linkedin,
        enName: enName,
        enDesignation: enDesignation,
        enCountry: enCountry,
        enCity: enCity,
        enState: enState,
        enZipCode: enZipCode,
        enAddress: enAddress,
        enDetails: enDetails,
        arName: arName,
        arDesignation: arDesignation,
        arCountry: arCountry,
        arCity: arCity,
        arState: arState,
        arZipCode: arZipCode,
        arAddress: arAddress,
        arDetails: arDetails,
        photoPath: _photo?.path,
      );
      _isSaving = false;
      notifyListeners();
      return true;
    } catch (e) {
      _saveError = e.toString().replaceFirst('Exception: ', '');
      _isSaving = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> pickPhoto() async {
    try {
      final image = await _picker.pickImage(source: ImageSource.gallery);
      if (image != null) {
        _photo = image;
        _hasPhoto = true;
        notifyListeners();
      }
    } catch (_) {}
  }

  void removePhoto() {
    _photo = null;
    _hasPhoto = false;
    notifyListeners();
  }
}
