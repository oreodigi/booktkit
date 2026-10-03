import 'dart:io';
import 'package:dio/dio.dart';
import 'package:booktkit_organizer/app/urls.dart';
import 'package:booktkit_organizer/features/event_management/data/models/seat_mapping_model.dart';
import 'package:booktkit_organizer/services/api_client.dart';
import 'package:booktkit_organizer/utils/app_logger.dart';

/// Service for handling seat mapping related API calls
class SeatMappingService {
  final ApiClient _apiClient = ApiClient();

  /// Fetch seat mapping slots for a specific event and ticket
  ///
  /// Parameters:
  /// - [eventId]: The event ID
  /// - [ticketId]: The ticket ID
  /// - [slotUniqueId]: Unique identifier for the slot
  /// - [pricingType]: Type of pricing (e.g., 'variation')
  Future<SeatMappingResponse> getSeatMappingSlots({
    required int eventId,
    required int ticketId,
    required String slotUniqueId,
    required String pricingType,
  }) async {
    try {
      final response = await _apiClient.get(
        Urls.getSeatMappingSlots,
        queryParameters: {
          'event_id': eventId.toString(),
          'ticket_id': ticketId.toString(),
          'slot_unique_id': slotUniqueId,
          'pricing_type': pricingType,
        },
      );
      return SeatMappingResponse.fromJson(
        response.data as Map<String, dynamic>,
      );
    } catch (e) {
      throw Exception('Failed to fetch seat mapping slots: $e');
    }
  }

  /// Fetch seats for a specific slot
  ///
  /// Endpoint: GET /organizer/seat-mapping/slot/seats
  /// Parameters: event_id, ticket_id, slot_unique_id, slot_id
  Future<List<SeatItem>> getSlotSeats({
    required int eventId,
    required int ticketId,
    required String slotUniqueId,
    required int slotId,
  }) async {
    try {
      final response = await _apiClient.get(
        Urls.getSlotSeats,
        queryParameters: {
          'event_id': eventId.toString(),
          'ticket_id': ticketId.toString(),
          'slot_unique_id': slotUniqueId,
          'slot_id': slotId.toString(),
        },
      );
      final data =
          (response.data as Map<String, dynamic>)['data']
              as Map<String, dynamic>;
      final slot = data['slot'] as Map<String, dynamic>;
      final seats = slot['seats'] as List<dynamic>? ?? [];
      return seats
          .map((e) => SeatItem.fromJson(e as Map<String, dynamic>))
          .toList();
    } catch (e) {
      throw Exception('Failed to fetch slot seats: $e');
    }
  }

  /// Upload/update the background map image for a slot
  ///
  /// Endpoint: POST /organizer/seat-mapping/slot/update-background-image
  Future<void> updateBackgroundImage({
    required int eventId,
    required int ticketId,
    required String slotUniqueId,
    required File mapImage,
  }) async {
    try {
      final formData = FormData.fromMap({
        'event_id': eventId.toString(),
        'ticket_id': ticketId.toString(),
        'slot_unique_id': slotUniqueId,
        'map_image': await MultipartFile.fromFile(
          mapImage.path,
          filename: mapImage.path.split('/').last,
        ),
      });
      await _apiClient.post(
        Urls.updateSeatMapBackgroundImage,
        data: formData,
      );
    } catch (e) {
      throw Exception('Failed to update background image: $e');
    }
  }

  /// Delete a slot by its ID
  ///
  /// Endpoint: POST /organizer/seat-mapping/slot/delete
  Future<void> deleteSlot({required int slotId}) async {
    try {
      final formData = FormData.fromMap({'slot_id': slotId.toString()});
      await _apiClient.post(Urls.deleteSlot, data: formData);
    } catch (e) {
      throw Exception('Failed to delete slot: $e');
    }
  }

  /// Update seat names, prices and active state for a slot
  ///
  /// Endpoint: POST /organizer/seat-mapping/slot/seats/update
  /// Update only the position of an existing slot (drag-and-drop).
  /// Uses form-data: pos_x, pos_y, slot_id.
  Future<void> dragDropSlot({
    required int slotId,
    required String posX,
    required String posY,
  }) async {
    try {
      final formData = FormData.fromMap({
        'slot_id': slotId.toString(),
        'pos_x': posX,
        'pos_y': posY,
      });
      AppLogger.d('dragDropSlot REQUEST: slot_id=$slotId pos_x=$posX pos_y=$posY');
      await _apiClient.post(Urls.dragDropSlot, data: formData);
    } catch (e) {
      AppLogger.e('dragDropSlot FAILED: $e');
      throw Exception('Failed to drag-drop slot: $e');
    }
  }

  /// Create a new slot or update an existing one.
  ///
  /// Returns the server-assigned slot id (useful when creating a new slot).
  Future<int?> storeOrUpdateSlot({
    required String posX,
    required String posY,
    required String rotate,
    required String width,
    required String height,
    required String price,
    required String backgroundColor,
    required String numberOfSeat,
    required int? slotId,
    required int eventId,
    required int ticketId,
    required String slotUniqueId,
    required String slotName,
    required String fontSize,
    required String slotType,
    required String slotDeactive,
    required String round,
    required String pricingType,
  }) async {
    try {
      final body = {
        'pos_x': posX,
        'pos_y': posY,
        'rotate': rotate,
        'width': width,
        'height': height,
        'price': price,
        'background_color': backgroundColor,
        'number_of_seat': numberOfSeat,
        'slot_id': slotId?.toString(),
        'event_id': eventId.toString(),
        'ticket_id': ticketId.toString(),
        'slot_unique_id': slotUniqueId,
        'slot_name': slotName,
        'font_size': fontSize,
        'slot_type': slotType,
        'slot_deactive': slotDeactive,
        'round': round,
        'pricing_type': pricingType,
      };
      AppLogger.d('storeOrUpdateSlot REQUEST: $body');
      final formData = FormData.fromMap(body);
      final response = await _apiClient.post(Urls.storeUpdateSlot, data: formData);
      final data = response.data;
      // Try to extract the slot id from common response shapes
      if (data is Map) {
        final inner = data['data'];
        if (inner is Map) {
          final id =
              inner['id'] ??
              inner['slot_id'] ??
              inner['slot']?['id'];
          if (id != null) return int.tryParse(id.toString());
        }
      }
      return null;
    } catch (e) {
      AppLogger.e('storeOrUpdateSlot FAILED: $e');
      throw Exception('Failed to store/update slot: $e');
    }
  }

  Future<void> updateSeats({
    required int ticketId,
    required String slotUniqueId,
    required int slotId,
    required String slotType,
    required String pricingType,
    required List<String> seatKeys,
    required Map<String, String> keyName,
    required Map<String, String> keyPrice,
    required Map<String, String> slotDeactiveInput,
  }) async {
    try {
      final body = {
        'ticket_id': ticketId.toString(),
        'slot_unique_id': slotUniqueId,
        'slot_id': slotId.toString(),
        'slot_type': slotType,
        'pricing_type': pricingType,
        'seatKey':
            List.generate(seatKeys.length, (i) => (i + 1).toString()),
        'keyName': keyName,
        'keyPrice': keyPrice,
        'slot_deactive_input': slotDeactiveInput,
      };
      AppLogger.d('updateSeats REQUEST: $body');
      await _apiClient.post(Urls.updateSeats, data: body);
    } catch (e) {
      AppLogger.e('updateSeats FAILED: $e');
      throw Exception('Failed to update seats: $e');
    }
  }
}
