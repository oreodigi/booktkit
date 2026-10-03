import 'package:dio/dio.dart';

/// Request model for POST /organizer/event-management/event-update
///
/// This model is similar to StoreEventRequest but includes:
/// - `event_id`: The event being updated
/// - `date_ids`: For updating multiple date slots (existing date IDs)
/// - Optional images: Only include if new images are selected
class UpdateEventRequest {
  const UpdateEventRequest({
    required this.eventId,
    required this.eventType,
    required this.dateType,
    required this.countdownStatus,
    required this.status,
    required this.isFeatured,
    required this.earlyBirdDiscountType,
    required this.ticketAvailableType,
    required this.maxTicketBuyType,
    this.sliderImagePaths,
    this.thumbnailPath,
    this.meetingUrl,
    this.discountType,
    this.earlyBirdDiscountAmount,
    this.earlyBirdDiscountDate,
    this.earlyBirdDiscountTime,
    this.maxBuyTicket,
    this.ticketAvailable,
    this.price,
    this.startDate,
    this.startTime,
    this.endDate,
    this.endTime,
    this.dateIds,
    this.multipleStartDates,
    this.multipleStartTimes,
    this.multipleEndDates,
    this.multipleEndTimes,
    this.latitude,
    this.longitude,
    required this.titles,
    required this.categoryIds,
    required this.countries,
    required this.states,
    required this.cities,
    required this.addresses,
    required this.refundPolicies,
    required this.metaKeywords,
    required this.metaDescriptions,
    required this.descriptions,
    required this.zipCodes,
  });

  // ── Event identification ──────────────────────────────────────────────
  final String eventId;

  // ── Event basic info ──────────────────────────────────────────────────
  final String eventType; // 'venue' | 'online'
  final String dateType; // 'single' | 'multiple'
  final String countdownStatus; // '0', '1', '2', etc.
  final String status; // '1' = active, '0' = inactive
  final String isFeatured; // 'yes' | 'no'

  // ── File uploads (optional - only if new images selected) ─────────────
  final List<String>? sliderImagePaths; // Paths to slider images
  final String? thumbnailPath; // Path to thumbnail image

  // ── Pricing & discount ────────────────────────────────────────────────
  final String earlyBirdDiscountType; // 'enable' | 'disable'
  final String? discountType; // 'fixed' | 'percentage'
  final String? earlyBirdDiscountAmount;
  final String? earlyBirdDiscountDate; // yyyy-MM-dd
  final String? earlyBirdDiscountTime; // HH:mm
  final String? price;

  // ── Ticket availability ───────────────────────────────────────────────
  final String ticketAvailableType; // 'unlimited' | 'limited'
  final String? ticketAvailable;
  final String maxTicketBuyType; // 'unlimited' | 'limited'
  final String? maxBuyTicket;

  // ── Date/Time (single date) ───────────────────────────────────────────
  final String? startDate; // yyyy-MM-dd
  final String? startTime; // HH:mm
  final String? endDate; // yyyy-MM-dd
  final String? endTime; // HH:mm

  // ── Date/Time (multiple dates) ────────────────────────────────────────
  final List<String>? dateIds; // existing date IDs for updating: date_ids[]
  final List<String>? multipleStartDates; // m_start_date[]
  final List<String>? multipleStartTimes; // m_start_time[]
  final List<String>? multipleEndDates; // m_end_date[]
  final List<String>? multipleEndTimes; // m_end_time[]

  // ── Location (for venue events only) ──────────────────────────────────
  final String? latitude;
  final String? longitude;

  // ── Online meeting URL (for online events only) ───────────────────────
  final String? meetingUrl;

  // ── Language-dependent fields ─────────────────────────────────────────
  /// Map of langCode → title, e.g. {'en': 'My Event', 'ar': 'حدثي'}
  final Map<String, String> titles;

  /// Map of langCode → category ID
  final Map<String, String> categoryIds;

  /// Map of langCode → country ID (venue events only)
  final Map<String, String>? countries;

  /// Map of langCode → state ID (venue events only)
  final Map<String, String>? states;

  /// Map of langCode → city ID (venue events only)
  final Map<String, String>? cities;

  /// Map of langCode → address (venue events only)
  final Map<String, String>? addresses;

  /// Map of langCode → refund policy
  final Map<String, String> refundPolicies;

  /// Map of langCode → meta keywords
  final Map<String, String> metaKeywords;

  /// Map of langCode → meta description
  final Map<String, String> metaDescriptions;

  /// Map of langCode → description (HTML content)
  final Map<String, String> descriptions;

  /// Map of langCode → zip code
  final Map<String, String> zipCodes;

  // ─────────────────────────────────────────────────────────────────────
  // FormData serialization
  // ─────────────────────────────────────────────────────────────────────

  /// Converts to multipart [FormData] for file uploads.
  /// - Scalar fields → single entry
  /// - Language map fields → `{langCode}_field_name` entries
  /// - List fields → repeated `key[]` entries (Laravel convention)
  /// - Files → MultipartFile entries (only if provided)
  Future<FormData> toFormData() async {
    final fd = FormData();

    void add(String key, dynamic value) {
      if (value == null) return;
      fd.fields.add(MapEntry(key, value.toString()));
    }

    void addList(String key, List<dynamic>? list) {
      if (list == null || list.isEmpty) return;
      for (final item in list) {
        fd.fields.add(MapEntry('$key[]', item.toString()));
      }
    }

    // ── Event ID (required for update) ────────────────────────────────
    add('event_id', eventId);

    // ── Basic event info ──────────────────────────────────────────────
    add('event_type', eventType);
    add('date_type', dateType);
    add('countdown_status', countdownStatus);
    add('status', status);
    add('is_featured', isFeatured);

    // ── Slider images (only if new images provided) ───────────────────
    if (sliderImagePaths != null && sliderImagePaths!.isNotEmpty) {
      for (final path in sliderImagePaths!) {
        fd.files.add(
          MapEntry(
            'slider_images[]',
            await MultipartFile.fromFile(
              path,
              filename: path.split('/').last.split('\\').last,
            ),
          ),
        );
      }
    }

    // ── Thumbnail (only if new thumbnail provided) ────────────────────
    if (thumbnailPath != null && thumbnailPath!.isNotEmpty) {
      fd.files.add(
        MapEntry(
          'thumbnail',
          await MultipartFile.fromFile(
            thumbnailPath!,
            filename: thumbnailPath!.split('/').last.split('\\').last,
          ),
        ),
      );
    }

    // ── Pricing & discount ────────────────────────────────────────────
    add('early_bird_discount_type', earlyBirdDiscountType);
    if (earlyBirdDiscountType == 'enable') {
      add('discount_type', discountType);
      add('early_bird_discount_amount', earlyBirdDiscountAmount);
      add('early_bird_discount_date', earlyBirdDiscountDate);
      add('early_bird_discount_time', earlyBirdDiscountTime);
    }
    add('price', price);

    // ── Ticket availability ───────────────────────────────────────────
    add('ticket_available_type', ticketAvailableType);
    if (ticketAvailableType == 'limited') {
      add('ticket_available', ticketAvailable);
    }

    add('max_ticket_buy_type', maxTicketBuyType);
    if (maxTicketBuyType == 'limited') {
      add('max_buy_ticket', maxBuyTicket);
    }

    // ── Date/Time based on dateType ───────────────────────────────────
    if (dateType == 'single') {
      add('start_date', startDate);
      add('start_time', startTime);
      add('end_date', endDate);
      add('end_time', endTime);
    } else if (dateType == 'multiple') {
      // Include existing date IDs for update
      addList('date_ids', dateIds);
      addList('m_start_date', multipleStartDates);
      addList('m_start_time', multipleStartTimes);
      addList('m_end_date', multipleEndDates);
      addList('m_end_time', multipleEndTimes);
    }

    // ── Location (for venue) / Meeting URL (for online) ───────────────
    if (eventType == 'venue') {
      add('latitude', latitude);
      add('longitude', longitude);
    } else if (eventType == 'online') {
      add('meeting_url', meetingUrl);
    }

    // ── Dynamic language fields ───────────────────────────────────────
    // Each language gets its own prefixed field: en_title, ar_title, etc.
    for (final entry in titles.entries) {
      add('${entry.key}_title', entry.value);
    }

    for (final entry in categoryIds.entries) {
      add('${entry.key}_category_id', entry.value);
    }

    // Location fields only for venue events
    if (countries != null) {
      for (final entry in countries!.entries) {
        add('${entry.key}_country', entry.value);
      }
    }

    if (states != null) {
      for (final entry in states!.entries) {
        add('${entry.key}_state', entry.value);
      }
    }

    if (cities != null) {
      for (final entry in cities!.entries) {
        add('${entry.key}_city', entry.value);
      }
    }

    if (addresses != null) {
      for (final entry in addresses!.entries) {
        add('${entry.key}_address', entry.value);
      }
    }

    for (final entry in refundPolicies.entries) {
      add('${entry.key}_refund_policy', entry.value);
    }

    for (final entry in metaKeywords.entries) {
      add('${entry.key}_meta_keywords', entry.value);
    }

    for (final entry in metaDescriptions.entries) {
      add('${entry.key}_meta_description', entry.value);
    }

    for (final entry in descriptions.entries) {
      add('${entry.key}_description', entry.value);
    }

    for (final entry in zipCodes.entries) {
      add('${entry.key}_zip_code', entry.value);
    }

    return fd;
  }
}
