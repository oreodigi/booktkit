import 'package:booktkit_customer/features/home/data/models/home_data_model.dart';
import 'package:booktkit_customer/network_services/core/home_services.dart';
import 'package:booktkit_customer/features/home/providers/locale_provider.dart';
import 'package:provider/provider.dart';
import 'package:flutter/widgets.dart';

class HomeProvider extends ChangeNotifier {
  final TextEditingController searchController = TextEditingController();
  String _searchText = '';
  bool _loading = false;
  HomeDataModel? _data;
  int? _selectedCategoryId;
  bool _initialized = false;
  bool _fetching = false;
  bool _failed = false;

  bool get loading => _loading;
  HomeDataModel? get data => _data;
  int? get selectedCategoryId => _selectedCategoryId;
  String get searchText => _searchText;
  bool get failed => _failed;

  Future<void> fetch([BuildContext? context, bool forceRemote = false]) async {
    if (_fetching) return;
    _fetching = true;
    _setLoading(true);
    try {
      String? lang;
      if (context != null) {
        try {
          lang = context.read<LocaleProvider>().locale.languageCode;
        } catch (_) {}
      }
      _data = await HomeServices.fetchHome(
        languageCode: lang,
        forceRemote: true,
      );
      _initialized = true;
      _failed = false;
    } catch (e) {
      // Keep existing data on failure to avoid blank UI after a transient error
      _initialized = true;
      _failed = true;
    } finally {
      _setLoading(false);
      _fetching = false;
    }
  }

  bool get initialized => _initialized;

  Future<void> ensureFetched([BuildContext? context]) async {
    if (_initialized) return;
    await fetch(context, true);
    // Removed icon cache warm-up
  }

  Future<void> refresh([BuildContext? context]) async {
    await fetch(context, true);
    notifyListeners();
  }

  void selectCategory(int? id) {
    _selectedCategoryId = id;
    notifyListeners();
  }

  void setSearchText(String v) {
    final t = v.trimLeft();
    if (_searchText == t) return;
    _searchText = t;
    notifyListeners();
  }

  void clearSearchText() {
    _searchText = '';
    try {
      if (searchController.text.isNotEmpty) searchController.clear();
    } catch (_) {}
    notifyListeners();
  }

  void _setLoading(bool v) {
    _loading = v;
    notifyListeners();
  }

  @override
  void dispose() {
    try {
      searchController.dispose();
    } catch (_) {}
    super.dispose();
  }
}
