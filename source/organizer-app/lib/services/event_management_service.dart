import 'package:flutter/foundation.dart';
import 'package:booktkit_organizer/app/urls.dart';
import 'package:booktkit_organizer/features/event_management/data/models/add_event_init_model.dart';
import 'package:booktkit_organizer/features/event_management/data/models/categories_model.dart';
import 'package:booktkit_organizer/features/event_management/data/models/edit_ticket_model.dart';
import 'package:booktkit_organizer/features/event_management/data/models/event_detail_model.dart';
import 'package:booktkit_organizer/features/event_management/data/models/event_tickets_model.dart';
import 'package:booktkit_organizer/features/event_management/data/models/events_model.dart';
import 'package:booktkit_organizer/features/event_management/data/models/store_event_request.dart';
import 'package:booktkit_organizer/features/event_management/data/models/store_ticket_request.dart';
import 'package:booktkit_organizer/features/event_management/data/models/update_event_request.dart';
import 'package:booktkit_organizer/features/event_management/data/models/update_ticket_request.dart';
import 'package:booktkit_organizer/utils/app_logger.dart';
import 'package:booktkit_organizer/features/event_management/data/models/ticket_settings_model.dart';
import 'package:booktkit_organizer/services/api_client.dart';
import 'package:dio/dio.dart';

class EventManagementService {
  final ApiClient _apiClient = ApiClient();

  Future<EventsResponse> getEvents({
    String? title,
    String? eventType,
    int page = 1,
    String? languageCode,
  }) async {
    try {
      final queryParams = <String, String>{
        'page': page.toString(),
        if (title != null && title.isNotEmpty) 'title': title,
        if (eventType != null &&
            eventType.isNotEmpty &&
            eventType.toLowerCase() != 'all')
          'event_type': eventType.toLowerCase(),
      };

      // Per-request Accept-Language override — does NOT affect other API calls
      final options = languageCode != null
          ? Options(headers: {'Accept-Language': languageCode})
          : null;

      final response = await _apiClient.get(
        Urls.getEvents,
        queryParameters: queryParams,
        options: options,
      );

      return EventsResponse.fromJson(response.data as Map<String, dynamic>);
    } catch (e) {
      throw Exception('Failed to fetch events: $e');
    }
  }

  Future<EventDetailResponse> getEventDetail(int eventId) async {
    try {
      final response = await _apiClient.get('${Urls.getEventEdit}/$eventId');
      return EventDetailResponse.fromJson(
        response.data as Map<String, dynamic>,
      );
    } catch (e) {
      throw Exception('Failed to fetch event details: $e');
    }
  }

  Future<EventTicketsResponse> getEventTickets({
    required int eventId,
    required String eventType,
  }) async {
    try {
      final response = await _apiClient.get(
        Urls.getEventTickets,
        queryParameters: {
          'event_id': eventId.toString(),
          'event_type': eventType,
        },
      );
      return EventTicketsResponse.fromJson(
        response.data as Map<String, dynamic>,
      );
    } catch (e) {
      throw Exception('Failed to fetch event tickets: $e');
    }
  }

  Future<CategoriesResponse> getAllCategories(int languageId) async {
    try {
      final response = await _apiClient.get(
        '${Urls.getAllCategories}/$languageId',
      );
      return CategoriesResponse.fromJson(response.data as Map<String, dynamic>);
    } catch (e) {
      throw Exception('Failed to fetch categories: $e');
    }
  }

  Future<TicketSettingsModel> getTicketSettings(int eventId) async {
    try {
      final response = await _apiClient.get(
        '${Urls.getTicketSettings}/$eventId',
      );
      return TicketSettingsModel.fromJson(
        response.data as Map<String, dynamic>,
      );
    } catch (e) {
      throw Exception('Failed to fetch ticket settings: $e');
    }
  }

  Future<void> updateTicketSettings({
    required int eventId,
    String? ticketImagePath,
    String? ticketLogoPath,
    required String instructions,
  }) async {
    try {
      final formData = FormData.fromMap({
        'event_id': eventId,
        'instructions': instructions,
        if (ticketImagePath != null)
          'ticket_image': await MultipartFile.fromFile(
            ticketImagePath,
            filename: ticketImagePath.split('/').last,
          ),
        if (ticketLogoPath != null)
          'ticket_logo': await MultipartFile.fromFile(
            ticketLogoPath,
            filename: ticketLogoPath.split('/').last,
          ),
      });
      final response = await _apiClient.post(
        Urls.updateTicketSetting,
        data: formData,
        options: Options(contentType: Headers.multipartFormDataContentType),
      );
      final isSuccess = response.data['success'] == true;
      if (!isSuccess) {
        throw Exception(
          response.data['message']?.toString() ??
              'Failed to update ticket settings',
        );
      }
    } on DioException catch (e) {
      final serverMsg = e.response?.data is Map
          ? e.response!.data['message']?.toString()
          : null;
      throw Exception(
        serverMsg ?? 'Failed to update ticket settings: ${e.message}',
      );
    } catch (e) {
      throw Exception('Failed to update ticket settings: $e');
    }
  }

  Future<AddEventInitModel> getAddEventInit() async {
    try {
      final response = await _apiClient.get(Urls.addEvent);
      return AddEventInitModel.fromJson(response.data as Map<String, dynamic>);
    } catch (e) {
      AppLogger.info('[AddEvent] getAddEventInit error: $e');
      throw Exception('Failed to fetch add-event init data: $e');
    }
  }

  /// Fetches just the language list for a given event by calling the editTicket
  /// endpoint. Used by the add ticket screen to know which language keys to send.
  Future<List<EditTicketLanguage>> getTicketLanguages({
    required int eventId,
    required String eventType,
  }) async {
    try {
      final response = await _apiClient.get(
        Urls.editTicket,
        queryParameters: {
          'event_id': eventId.toString(),
          'event_type': eventType,
        },
      );
      final data =
          (response.data as Map<String, dynamic>)['data']
              as Map<String, dynamic>? ??
          {};
      final raw = data['languages'] as List<dynamic>? ?? [];
      return raw
          .map((e) => EditTicketLanguage.fromJson(e as Map<String, dynamic>))
          .toList();
    } catch (e) {
      throw Exception('Failed to fetch ticket languages: $e');
    }
  }

  Future<List<LocationItem>> getAllCountries(int langId) async {
    try {
      final response = await _apiClient.get('${Urls.allCountries}/$langId');
      AppLogger.info('[AddEvent] countries raw: ${response.data}');
      return LocationListResponse.fromCountriesJson(
        response.data as Map<String, dynamic>,
      ).countries;
    } catch (e) {
      AppLogger.info('[AddEvent] getAllCountries error: $e');
      throw Exception('Failed to fetch countries: $e');
    }
  }

  Future<LocationListResponse> getCountryWiseStateCity(int countryId) async {
    try {
      final response = await _apiClient.get(
        '${Urls.countryWiseStateCity}/$countryId',
      );
      AppLogger.info('[AddEvent] countryWise raw: ${response.data}');
      return LocationListResponse.fromCountryWiseJson(
        response.data as Map<String, dynamic>,
      );
    } catch (e) {
      AppLogger.info('[AddEvent] getCountryWiseStateCity error: $e');
      throw Exception('Failed to fetch states/cities for country: $e');
    }
  }

  Future<List<LocationItem>> getStateWiseCity(int stateId) async {
    try {
      final response = await _apiClient.get('${Urls.stateWiseCity}/$stateId');
      AppLogger.info('[AddEvent] stateWise raw: ${response.data}');
      return LocationListResponse.fromStateWiseCitiesJson(
        response.data as Map<String, dynamic>,
      ).cities;
    } catch (e) {
      AppLogger.info('[AddEvent] getStateWiseCity error: $e');
      throw Exception('Failed to fetch cities for state: $e');
    }
  }

  Future<void> deleteEvent(int eventId) async {
    try {
      await _apiClient.post('${Urls.eventDelete}/$eventId');
    } catch (e) {
      throw Exception('Failed to delete event: $e');
    }
  }

  Future<EditTicketResponse> getEditTicket({
    required int eventId,
    required String eventType,
    required int ticketId,
  }) async {
    try {
      final response = await _apiClient.get(
        Urls.editTicket,
        queryParameters: {
          'event_id': eventId,
          'event_type': eventType,
          'id': ticketId,
        },
      );
      return EditTicketResponse.fromJson(response.data as Map<String, dynamic>);
    } catch (e) {
      throw Exception('Failed to fetch ticket for editing: $e');
    }
  }

  /// Toggle event active/inactive status
  ///   status: '1' = active, '0' = inactive
  Future<void> updateEventStatus({
    required int eventId,
    required int status,
  }) async {
    try {
      final response = await _apiClient.post(
        Urls.eventUpdateStatus,
        data: {'event_id': eventId, 'status': status},
      );
      final isSuccess = response.data['success'] == true;
      if (!isSuccess) {
        throw Exception(
          response.data['message']?.toString() ?? 'Failed to update status',
        );
      }
    } catch (e) {
      throw Exception('Failed to update event status: $e');
    }
  }

  /// Toggle event featured status
  ///   is_featured: 'yes' | 'no'
  Future<void> updateEventFeatured({
    required int eventId,
    required bool isFeatured,
  }) async {
    try {
      final response = await _apiClient.post(
        Urls.eventUpdateFeatured,
        data: {'event_id': eventId, 'is_featured': isFeatured ? 'yes' : 'no'},
      );
      final isSuccess = response.data['success'] == true;
      if (!isSuccess) {
        throw Exception(
          response.data['message']?.toString() ?? 'Failed to update featured',
        );
      }
    } catch (e) {
      throw Exception('Failed to update event featured: $e');
    }
  }

  /// Delete a ticket by its id
  Future<void> deleteEventTicket(int ticketId) async {
    try {
      final response = await _apiClient.post(
        Urls.deleteEventTicket,
        data: {'id': ticketId},
      );
      final isSuccess = response.data['success'] == true;
      if (!isSuccess) {
        throw Exception(
          response.data['message']?.toString() ?? 'Failed to delete ticket',
        );
      }
    } catch (e) {
      throw Exception('Failed to delete ticket: $e');
    }
  }

  /// Delete an event image by its id
  Future<void> deleteEventImage(int imageId) async {
    try {
      final response = await _apiClient.post(
        Urls.deleteEventImage,
        data: {'id': imageId},
      );
      final isSuccess = response.data['success'] == true;
      if (!isSuccess) {
        throw Exception(
          response.data['message']?.toString() ?? 'Failed to delete image',
        );
      }
    } catch (e) {
      throw Exception('Failed to delete event image: $e');
    }
  }

  /// Delete an event date by its id
  Future<void> deleteEventDate(int dateId) async {
    try {
      final response = await _apiClient.post(
        '${Urls.deleteEventDate}?date_id=$dateId',
      );
      final isSuccess = response.data['success'] == true;
      if (!isSuccess) {
        throw Exception(
          response.data['message']?.toString() ?? 'Failed to delete date',
        );
      }
    } catch (e) {
      throw Exception('Failed to delete event date: $e');
    }
  }

  /// Store (create) a new ticket — delegates FormData building to [StoreTicketRequest].
  Future<void> storeTicket(StoreTicketRequest request) async {
    try {
      final fd = request.toFormData();
      for (var f in fd.fields) {
        AppLogger.info('FormData Field => ${f.key} : ${f.value}');
      }
      final response = await _apiClient.post(
        Urls.storeEventTicket,
        data: fd,
        // Override the base 'application/json' so Dio sets the correct
        // multipart/form-data boundary for FormData payloads.
        options: Options(contentType: Headers.multipartFormDataContentType),
      );
      final isSuccess = response.data['success'] == true;
      if (!isSuccess) {
        throw Exception(
          response.data['message']?.toString() ?? 'Failed to save ticket',
        );
      }
    } on DioException catch (e) {
      // Log the actual server error body for debugging
      if (kDebugMode) {
        AppLogger.info('storeTicket error body: ${e.response?.data}');
      }
      final serverMsg = e.response?.data is Map
          ? e.response!.data['message']?.toString()
          : null;
      throw Exception(serverMsg ?? 'Failed to save ticket: ${e.message}');
    } catch (e) {
      throw Exception('Failed to save ticket: $e');
    }
  }

  /// Update an existing ticket — delegates FormData building to [UpdateTicketRequest].
  Future<void> updateTicket(UpdateTicketRequest request) async {
    try {
      final fd = request.toFormData();
      for (var f in fd.fields) {
        AppLogger.info('FormData Field => ${f.key} : ${f.value}');
      }
      final response = await _apiClient.post(
        Urls.updateEventTicket,
        data: fd,
        options: Options(contentType: Headers.multipartFormDataContentType),
      );
      final isSuccess = response.data['success'] == true;
      if (!isSuccess) {
        throw Exception(
          response.data['message']?.toString() ?? 'Failed to update ticket',
        );
      }
    } on DioException catch (e) {
      if (kDebugMode) {
        AppLogger.info('updateTicket error body: ${e.response?.data}');
      }
      final serverMsg = e.response?.data is Map
          ? e.response!.data['message']?.toString()
          : null;
      throw Exception(serverMsg ?? 'Failed to update ticket: ${e.message}');
    } catch (e) {
      throw Exception('Failed to update ticket: $e');
    }
  }

  /// Store (create) a new event — delegates FormData building to [StoreEventRequest].
  Future<void> storeEvent(StoreEventRequest request) async {
    try {
      final fd = await request.toFormData();
      if (kDebugMode) {
        AppLogger.info('──────────── Store Event FormData ────────────');
        for (var f in fd.fields) {
          AppLogger.info('Field => ${f.key} : ${f.value}');
        }
        for (var f in fd.files) {
          AppLogger.info('File => ${f.key} : ${f.value.filename}');
        }
      }
      final response = await _apiClient.post(
        Urls.storeEvent,
        data: fd,
        options: Options(contentType: Headers.multipartFormDataContentType),
      );
      final isSuccess = response.data['success'] == true;
      if (!isSuccess) {
        throw Exception(
          response.data['message']?.toString() ?? 'Failed to create event',
        );
      }
    } on DioException catch (e) {
      if (kDebugMode) {
        AppLogger.info('storeEvent error body: ${e.response?.data}');
      }
      final serverMsg = e.response?.data is Map
          ? e.response!.data['message']?.toString()
          : null;
      throw Exception(serverMsg ?? 'Failed to create event: ${e.message}');
    } catch (e) {
      throw Exception('Failed to create event: $e');
    }
  }

  /// Update an existing event — delegates FormData building to [UpdateEventRequest].
  Future<void> updateEvent(UpdateEventRequest request) async {
    try {
      final fd = await request.toFormData();
      if (kDebugMode) {
        AppLogger.info('──────────── Update Event FormData ────────────');
        for (var f in fd.fields) {
          AppLogger.info('Field => ${f.key} : ${f.value}');
        }
        for (var f in fd.files) {
          AppLogger.info('File => ${f.key} : ${f.value.filename}');
        }
      }
      final response = await _apiClient.post(
        Urls.updateEvent,
        data: fd,
        options: Options(contentType: Headers.multipartFormDataContentType),
      );
      final isSuccess = response.data['success'] == true;
      if (!isSuccess) {
        throw Exception(
          response.data['message']?.toString() ?? 'Failed to update event',
        );
      }
    } on DioException catch (e) {
      if (kDebugMode) {
        AppLogger.info('updateEvent error body: ${e.response?.data}');
      }
      final serverMsg = e.response?.data is Map
          ? e.response!.data['message']?.toString()
          : null;
      throw Exception(serverMsg ?? 'Failed to update event: ${e.message}');
    } catch (e) {
      throw Exception('Failed to update event: $e');
    }
  }
}
