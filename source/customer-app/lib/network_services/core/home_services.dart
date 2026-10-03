import 'dart:convert';
import 'package:booktkit_customer/features/categories/models/category_model.dart';
import 'package:booktkit_customer/features/events/data/models/event_item_model.dart';
import 'package:booktkit_customer/features/home/data/models/hero_section_model.dart';
import 'package:booktkit_customer/features/home/data/models/home_data_model.dart';
import 'package:booktkit_customer/features/home/data/models/section_titles_model.dart';
import 'package:booktkit_customer/app/urls.dart';
import 'package:booktkit_customer/utils/net_utils.dart';

class HomeServices {
  static Future<HomeDataModel> fetchHome({
    String? languageCode,
    bool forceRemote = false,
  }) async {
    // Always fetch from API; no caching
    final uri = Uri.parse(AppUrls.home);
    final response = await NetUtils.getWithRetry(
      uri,
      headers: {
        if (languageCode != null) 'Accept-Language': languageCode,
        'Accept': 'application/json',
      },
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to load home data: ${response.statusCode}');
    }
    Map<String, dynamic>? decoded;
    try {
      final bodyStr = response.body;
      final obj = json.decode(bodyStr);
      if (obj is Map<String, dynamic>) {
        decoded = obj;
      }
    } catch (_) {
      throw Exception('Failed to parse home data');
    }
    List<CategoryModel> categories = [];
    List<EventItemModel> eventsAll = [];
    List<EventItemModel> latestEvents = [];
    Map<int, List<EventItemModel>> eventsByCat = {};
    HeroSectionModel? hero;
    SectionTitlesModel? sectionTitles;
    if (decoded is Map<String, dynamic>) {
      final data = decoded['data'];
      if (data is Map<String, dynamic>) {
        final cats = data['categories'];
        if (cats is List) {
          categories = cats
              .whereType<Map<String, dynamic>>()
              .map(CategoryModel.fromJson)
              .toList();
        }
        // Some backends use 'lastest_events' while others may use 'latest_events'
        final latest = data['upcoming_events'] ?? data['upcoming_events'];
        if (latest is List) {
          latestEvents = latest
              .whereType<Map<String, dynamic>>()
              .map(EventItemModel.fromJson)
              .toList();
        }

        final events = data['events'];
        if (events is Map<String, dynamic>) {
          final all = events['all'];
          if (all is List) {
            eventsAll = all
                .whereType<Map<String, dynamic>>()
                .map(EventItemModel.fromJson)
                .toList();
          }
          final catMap = events['categories'];
          if (catMap is Map<String, dynamic>) {
            for (final entry in catMap.entries) {
              final list = entry.value;
              if (list is List) {
                final catId = int.tryParse(entry.key) ?? 0;
                eventsByCat[catId] = list.whereType<Map<String, dynamic>>().map((
                  m,
                ) {
                  final enriched = Map<String, dynamic>.from(m);
                  // Ensure category id is present for downstream filtering by name/id
                  enriched['category_id'] = catId;
                  return EventItemModel.fromJson(enriched);
                }).toList();
              }
            }
          }
        }
        final heroInfo = data['heroInfo'];
        if (heroInfo is Map<String, dynamic>) {
          hero = HeroSectionModel.fromJson(heroInfo);
        }
        final secTitles = data['secTitleInfo'];
        if (secTitles is Map<String, dynamic>) {
          sectionTitles = SectionTitlesModel.fromJson(secTitles);
        }
      }
    }
    return HomeDataModel(
      categories: categories,
      latestEvents: latestEvents,
      eventsAll: eventsAll,
      eventsByCategory: eventsByCat,
      hero: hero,
      sectionTitles: sectionTitles,
    );
  }
}
