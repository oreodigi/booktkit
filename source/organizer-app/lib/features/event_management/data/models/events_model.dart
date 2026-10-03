class LangItem {
  final int id;
  final String name;
  final String code;
  final bool isRtl; // direction "1" = RTL
  final bool isDefault;

  const LangItem({
    required this.id,
    required this.name,
    required this.code,
    required this.isRtl,
    required this.isDefault,
  });

  factory LangItem.fromJson(Map<String, dynamic> json) {
    return LangItem(
      id: json['id'] as int? ?? 0,
      name: json['name']?.toString() ?? '',
      code: json['code']?.toString() ?? 'en',
      isRtl: json['direction']?.toString() == '1',
      isDefault: json['is_default']?.toString() == '1',
    );
  }

  @override
  bool operator ==(Object other) =>
      other is LangItem && other.code == code;

  @override
  int get hashCode => code.hashCode;
}

class EventsResponse {
  final List<EventItem> events;
  final int currentPage;
  final int lastPage;
  final bool hasNextPage;
  final List<LangItem> langs;
  final LangItem? currentLang;

  EventsResponse({
    required this.events,
    required this.currentPage,
    required this.lastPage,
    required this.hasNextPage,
    this.langs = const [],
    this.currentLang,
  });

  factory EventsResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'];
    final dataMap = data is Map<String, dynamic> ? data : <String, dynamic>{};

    final eventsData = dataMap['events'];
    final paginatedEvents = eventsData is Map<String, dynamic>
        ? eventsData
        : <String, dynamic>{};

    final rawList = paginatedEvents['data'];
    final eventsList = rawList is List ? rawList : [];

    final currentPage = paginatedEvents['current_page'] as int? ?? 1;
    final lastPage = paginatedEvents['last_page'] as int? ?? 1;

    // Parse langs array
    final rawLangs = dataMap['langs'];
    final langs = rawLangs is List
        ? rawLangs
              .map((l) => LangItem.fromJson(l as Map<String, dynamic>))
              .toList()
        : <LangItem>[];

    // Parse current language
    final rawLang = dataMap['language'];
    final currentLang = rawLang is Map<String, dynamic>
        ? LangItem.fromJson(rawLang)
        : null;

    return EventsResponse(
      events: eventsList
          .map((e) => EventItem.fromJson(e as Map<String, dynamic>))
          .toList(),
      currentPage: currentPage,
      lastPage: lastPage,
      hasNextPage: currentPage < lastPage,
      langs: langs,
      currentLang: currentLang,
    );
  }
}

class EventItem {
  final String eventInfoId;
  final String eventId;
  final String title;
  final String slug;
  final String eventType; // venue | online
  final String category;
  final bool isActive; // status == "1"
  final bool isFeatured; // is_featured == "yes"

  EventItem({
    required this.eventInfoId,
    required this.eventId,
    required this.title,
    required this.slug,
    required this.eventType,
    required this.category,
    required this.isActive,
    required this.isFeatured,
  });

  factory EventItem.fromJson(Map<String, dynamic> json) {
    return EventItem(
      eventInfoId: json['eventInfoId']?.toString() ?? '',
      eventId: json['eventId']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
      eventType: json['event_type']?.toString() ?? 'venue',
      category: json['category']?.toString() ?? '',
      isActive: json['status']?.toString() == '1',
      isFeatured: json['is_featured']?.toString().toLowerCase() == 'yes',
    );
  }

  /// Display label for EventCard
  String get typeLabel =>
      eventType.toLowerCase() == 'venue' ? 'Venue' : 'Online';

  EventItem copyWith({bool? isActive, bool? isFeatured}) {
    return EventItem(
      eventInfoId: eventInfoId,
      eventId: eventId,
      title: title,
      slug: slug,
      eventType: eventType,
      category: category,
      isActive: isActive ?? this.isActive,
      isFeatured: isFeatured ?? this.isFeatured,
    );
  }
}
