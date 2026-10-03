import 'package:booktkit_organizer/features/event_management/data/models/categories_model.dart';
import 'package:booktkit_organizer/services/event_management_service.dart';
import 'package:flutter/material.dart';

/// Standalone provider for fetching categories by language ID.
class CategoriesProvider extends ChangeNotifier {
  final EventManagementService _service = EventManagementService();

  bool _isLoading = false;
  String? _error;
  List<CategoryItem> _categories = [];
  int? _lastFetchedLangId;

  bool get isLoading => _isLoading;
  String? get error => _error;
  List<CategoryItem> get categories => _categories;

  /// Fetches categories for the given language ID.
  /// Skips the network call if already loaded for the same language.
  Future<void> fetchCategories(int languageId, {bool force = false}) async {
    if (!force && _lastFetchedLangId == languageId && _categories.isNotEmpty) {
      return; // already loaded
    }

    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _service.getAllCategories(languageId);
      _categories = response.categories;
      _lastFetchedLangId = languageId;
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Returns the category name for a given ID string, or null if not found.
  String? nameForId(String? id) {
    if (id == null) return null;
    try {
      return _categories.firstWhere((c) => c.id.toString() == id).name;
    } catch (_) {
      return null;
    }
  }

  void clear() {
    _categories = [];
    _lastFetchedLangId = null;
    _error = null;
    notifyListeners();
  }
}
