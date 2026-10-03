import 'package:booktkit_organizer/features/event_management/data/models/seat_mapping_model.dart';
import 'package:booktkit_organizer/services/seat_mapping_service.dart';
import 'package:booktkit_organizer/utils/app_logger.dart';
import 'dart:async';
import 'dart:io';
import 'dart:ui' as ui;

import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/event_management/providers/seat_mapping_provider.dart';
import 'package:flutter/material.dart';
import 'package:flutter_colorpicker/flutter_colorpicker.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

class BoxData {
  // Position stored as ratio (0.0 to 1.0) relative to image size
  Offset relativePosition;

  // Width and height in image-pixel space (scaled at render time)
  double width;
  double height;
  double fontSize;
  double rotation;
  double roundness;
  Color backgroundColor;

  /// True if all seats in this slot are booked
  bool isBooked;

  /// True if this slot has been deactivated
  bool isDeactivated;

  /// Server-assigned slot ID (null for locally added boxes)
  int? slotId;

  /// Slot name as stored on the server
  String slotName;

  /// Total seat count for this slot
  String numberOfSeats;

  /// Slot type: '1' = Manual Selection, '2' = Auto Selection
  String slotType;

  /// Slot unique ID from the server (used when fetching seats)
  String slotUniqueId;

  /// Overall slot price (used for Manual pricing)
  String price;

  BoxData({
    required this.relativePosition,
    this.width = 50,
    this.height = 50,
    this.fontSize = 16,
    this.rotation = 0,
    this.roundness = 6,
    this.backgroundColor = Colors.blue,
    this.isBooked = false,
    this.isDeactivated = false,
    this.slotId,
    this.slotName = '',
    this.numberOfSeats = '0',
    this.slotType = '2',
    this.slotUniqueId = '',
    this.price = '0.00',
  });

  /// Returns grey when booked or deactivated, otherwise [backgroundColor].
  Color get effectiveColor {
    if (isBooked || isDeactivated) return Colors.grey.shade400;
    return backgroundColor;
  }
}

/// Dialog row model for your Seat Name table
class SeatRowData {
  final int seatId;
  final String seatLabel; // e.g. "Seat - 1"
  final bool isBooked;
  bool isDeactive;
  final TextEditingController nameController;
  final TextEditingController priceController;

  SeatRowData({
    this.seatId = 0,
    required this.seatLabel,
    required this.nameController,
    TextEditingController? priceController,
    this.isBooked = false,
    this.isDeactive = false,
  }) : priceController = priceController ?? TextEditingController();
}

class SeatMappingScreen extends StatefulWidget {
  final int? eventId;
  final int? ticketId;
  final String? slotUniqueId;
  final String? pricingType;

  const SeatMappingScreen({
    super.key,
    this.eventId,
    this.ticketId,
    this.slotUniqueId,
    this.pricingType,
  });

  @override
  State<SeatMappingScreen> createState() => _SeatMappingScreenState();
}

class _SeatMappingScreenState extends State<SeatMappingScreen> {
  /// User uploaded map image file
  File? _savedMapImage;

  /// Cover image URL from API
  String? _coverImageUrl;

  final List<BoxData> _boxes = [];
  final GlobalKey _imageContainerKey = GlobalKey();
  final TransformationController _transformationController =
      TransformationController();

  // Real on-screen canvas dimensions — updated each LayoutBuilder pass
  double _containerWidth = 300.0;
  double _containerHeight = 300.0;

  // Actual image dimensions (measured from network/file image)
  double imageWidth = 1.0;
  double imageHeight = 1.0;

  // Get current zoom scale
  double get _currentScale =>
      _transformationController.value.getMaxScaleOnAxis();

  // Image fills the entire container (container is sized to image aspect ratio)
  Rect _getImageBounds() =>
      Rect.fromLTWH(0, 0, _containerWidth, _containerHeight);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadSeatMappingData();
    });
  }

  /// Load seat mapping data from API
  Future<void> _loadSeatMappingData() async {
    if (widget.eventId == null ||
        widget.ticketId == null ||
        widget.slotUniqueId == null ||
        widget.pricingType == null) {
      return; // No API call if parameters are missing
    }

    final provider = context.read<SeatMappingProvider>();
    await provider.fetchSeatMappingSlots(
      eventId: widget.eventId!,
      ticketId: widget.ticketId!,
      slotUniqueId: widget.slotUniqueId!,
      pricingType: widget.pricingType!,
    );

    // Load cover image from API if available
    if (mounted && provider.coverImage != null) {
      setState(() {
        _coverImageUrl = provider.coverImage;
      });
      await _loadNetworkImageDimensions(provider.coverImage!);
    }

    // Populate canvas boxes from the fetched slots
    if (mounted && provider.slots.isNotEmpty) {
      _populateBoxesFromSlots(provider.slots);
    }
  }

  /// Save only the position of an existing slot after a drag.
  Future<void> _dragDropBox(BoxData box) async {
    if (box.slotId == null) return; // unsaved box — nothing to update
    final posX = (box.relativePosition.dx * imageWidth) - box.width / 2;
    final posY = (box.relativePosition.dy * imageHeight) - box.height / 2;
    try {
      await SeatMappingService().dragDropSlot(
        slotId: box.slotId!,
        posX: posX.toStringAsFixed(5),
        posY: posY.toStringAsFixed(5),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Failed to save position: $e')));
    }
  }

  /// Convert a Flutter [Color] to a CSS hex string (e.g. "#00e5b5").
  String _colorToHex(Color c) {
    final hex = c.toARGB32().toRadixString(16).padLeft(8, '0');
    return '#${hex.substring(2)}';
  }

  /// Save (create or update) a [BoxData] slot to the server.
  /// Updates [box.slotId] with the server-assigned id when creating a new slot.
  Future<void> _saveBoxToServer(BoxData box) async {
    if (widget.eventId == null || widget.ticketId == null) return;
    // top-left in image-pixel space
    final posX = (box.relativePosition.dx * imageWidth) - box.width / 2;
    final posY = (box.relativePosition.dy * imageHeight) - box.height / 2;
    try {
      final newId = await SeatMappingService().storeOrUpdateSlot(
        posX: posX.toStringAsFixed(5),
        posY: posY.toStringAsFixed(5),
        rotate: box.rotation.toStringAsFixed(0),
        width: box.width.toStringAsFixed(0),
        height: box.height.toStringAsFixed(0),
        price: box.price,
        backgroundColor: _colorToHex(box.backgroundColor),
        numberOfSeat: box.numberOfSeats,
        slotId: box.slotId,
        eventId: widget.eventId!,
        ticketId: widget.ticketId!,
        slotUniqueId: widget.slotUniqueId ?? box.slotUniqueId,
        slotName: box.slotName,
        fontSize: box.fontSize.toStringAsFixed(0),
        slotType: box.slotType,
        slotDeactive: box.isDeactivated ? '1' : '0',
        round: box.roundness.toStringAsFixed(0),
        pricingType: widget.pricingType ?? 'variation',
      );
      if (newId != null && box.slotId == null) {
        setState(() => box.slotId = newId);
      }
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Slot saved successfully'),
          behavior: SnackBarBehavior.floating,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Failed to save slot: $e')));
    }
  }

  /// Parse a CSS hex color string (e.g. "#00e5b5") into a Flutter [Color].
  Color _parseHexColor(String hex) {
    try {
      final sanitized = hex.replaceAll('#', '').trim();
      final intValue = int.parse(sanitized, radix: 16);
      return Color(0xFF000000 | intValue);
    } catch (_) {
      return const Color(0xFF00E5B5);
    }
  }

  /// Convert API [SlotItem] list into [BoxData] objects and populate [_boxes].
  ///
  /// The server stores `pos_x`/`pos_y` as the TOP-LEFT corner of the box
  /// (standard CSS positioning). We convert to the CENTER point so the
  /// render code (which does `left = center - halfWidth`) works correctly.
  /// Positions are then normalised to 0.0–1.0 relative to image dimensions.
  void _populateBoxesFromSlots(List<SlotItem> slots) {
    final newBoxes = <BoxData>[];
    for (final slot in slots) {
      final posX = double.tryParse(slot.posX) ?? 0.0;
      final posY = double.tryParse(slot.posY) ?? 0.0;
      final width = double.tryParse(slot.width) ?? 50.0;
      final height = double.tryParse(slot.height) ?? 50.0;
      final rotation = double.tryParse(slot.rotate) ?? 0.0;
      final roundness = double.tryParse(slot.round) ?? 6.0;
      final fontSize = double.tryParse(slot.fontSize) ?? 14.0;
      final color = _parseHexColor(slot.backgroundColor);

      // Server pos_x/pos_y = top-left corner.
      // Rendering code centers the box at relativePosition, so convert to center.
      final centerX = posX + width / 2;
      final centerY = posY + height / 2;

      final relX = imageWidth > 0 ? centerX / imageWidth : 0.0;
      final relY = imageHeight > 0 ? centerY / imageHeight : 0.0;

      newBoxes.add(
        BoxData(
          relativePosition: Offset(relX.clamp(0.0, 1.0), relY.clamp(0.0, 1.0)),
          width: width,
          height: height,
          fontSize: fontSize,
          rotation: rotation,
          roundness: roundness,
          backgroundColor: color,
          isBooked: slot.isBooked == 1,
          isDeactivated: slot.isDeactive == '1',
          slotId: slot.id,
          slotUniqueId: slot.slotUniqueId,
          slotName: slot.slotName,
          numberOfSeats: slot.numberOfSeat,
          slotType: slot.type,
          price: slot.price,
        ),
      );
    }

    setState(() {
      _boxes
        ..clear()
        ..addAll(newBoxes);
    });
  }

  @override
  void dispose() {
    _transformationController.dispose();
    super.dispose();
  }

  void _zoomIn() {
    final currentScale = _currentScale;
    final newScale = (currentScale * 1.2).clamp(0.5, 4.0);
    _transformationController.value = Matrix4.diagonal3Values(
      newScale,
      newScale,
      1.0,
    );
  }

  void _zoomOut() {
    final currentScale = _currentScale;
    final newScale = (currentScale / 1.2).clamp(0.5, 4.0);
    _transformationController.value = Matrix4.diagonal3Values(
      newScale,
      newScale,
      1.0,
    );
  }

  void _resetZoom() {
    _transformationController.value = Matrix4.identity();
  }

  Future<void> _measureImageDimensions(File imageFile) async {
    try {
      final bytes = await imageFile.readAsBytes();
      final codec = await ui.instantiateImageCodec(bytes);
      final frame = await codec.getNextFrame();
      final image = frame.image;

      setState(() {
        imageWidth = image.width.toDouble();
        imageHeight = image.height.toDouble();
      });

      image.dispose();
      codec.dispose();
    } catch (e) {
      AppLogger.info('Error measuring image dimensions: $e');
    }
  }

  Future<void> _loadNetworkImageDimensions(String imageUrl) async {
    try {
      final NetworkImage networkImage = NetworkImage(imageUrl);
      final ImageStream stream = networkImage.resolve(
        const ImageConfiguration(),
      );
      final Completer<ui.Image> completer = Completer<ui.Image>();

      late ImageStreamListener listener;
      listener = ImageStreamListener(
        (ImageInfo info, bool _) {
          completer.complete(info.image);
          stream.removeListener(listener);
        },
        onError: (exception, stackTrace) {
          completer.completeError(exception);
          stream.removeListener(listener);
        },
      );

      stream.addListener(listener);
      final image = await completer.future;

      if (mounted) {
        setState(() {
          imageWidth = image.width.toDouble();
          imageHeight = image.height.toDouble();
        });
      }

      image.dispose();
    } catch (e) {
      AppLogger.info('Error loading network image dimensions: $e');
    }
  }

  void _openUploadSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _UploadSeatMapSheet(
        initialImage: _savedMapImage,
        onSave: (file) async {
          if (file != null) {
            await _measureImageDimensions(file);
            setState(() => _savedMapImage = file);
            _uploadBackgroundImage(file);
          }
          if (mounted) Navigator.pop(context);
        },
      ),
    );
  }

  Future<void> _uploadBackgroundImage(File file) async {
    if (widget.eventId == null ||
        widget.ticketId == null ||
        widget.slotUniqueId == null) {
      return;
    }
    try {
      await SeatMappingService().updateBackgroundImage(
        eventId: widget.eventId!,
        ticketId: widget.ticketId!,
        slotUniqueId: widget.slotUniqueId!,
        mapImage: file,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Map image updated successfully')),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Failed to upload image: $e')));
    }
  }

  void _addBoxAt(Offset localPosition) {
    final imageBounds = _getImageBounds();
    if (!imageBounds.contains(localPosition)) return;

    final relativeX = (localPosition.dx - imageBounds.left) / imageBounds.width;
    final relativeY = (localPosition.dy - imageBounds.top) / imageBounds.height;

    setState(() {
      _boxes.add(BoxData(relativePosition: Offset(relativeX, relativeY)));
    });
  }

  void _removeBox(int index) {
    final box = _boxes[index];
    // If this slot exists on the server, delete it via API
    if (box.slotId != null) {
      SeatMappingService().deleteSlot(slotId: box.slotId!).catchError((e) {
        if (!mounted) return;
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Failed to delete slot: $e')));
      });
    }
    setState(() => _boxes.removeAt(index));
  }

  void _updateBoxPosition(int index, Offset absolutePosition) {
    final imageBounds = _getImageBounds();

    final clampedX = absolutePosition.dx.clamp(
      imageBounds.left,
      imageBounds.right,
    );
    final clampedY = absolutePosition.dy.clamp(
      imageBounds.top,
      imageBounds.bottom,
    );

    final relativeX = (clampedX - imageBounds.left) / imageBounds.width;
    final relativeY = (clampedY - imageBounds.top) / imageBounds.height;

    setState(() {
      _boxes[index].relativePosition = Offset(relativeX, relativeY);
    });
  }

  Offset _getAbsolutePosition(Offset relativePos) {
    final imageBounds = _getImageBounds();
    return Offset(
      imageBounds.left + (relativePos.dx * imageBounds.width),
      imageBounds.top + (relativePos.dy * imageBounds.height),
    );
  }

  // -----------------------------
  // Seat Name dialog opener (for your button)
  // -----------------------------
  Future<void> _openSeatNameDialog(BoxData box) async {
    // Must have valid IDs to call the API
    if (widget.eventId == null ||
        widget.ticketId == null ||
        box.slotId == null ||
        box.slotUniqueId.isEmpty) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Slot data unavailable')));
      return;
    }

    // Show loading indicator
    if (!mounted) return;
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator()),
    );

    try {
      final seats = await SeatMappingService().getSlotSeats(
        eventId: widget.eventId!,
        ticketId: widget.ticketId!,
        slotUniqueId: box.slotUniqueId,
        slotId: box.slotId!,
      );

      if (!mounted) return;
      Navigator.pop(context); // close loading

      if (!mounted) return;
      await showDialog(
        context: context,
        barrierDismissible: false,
        builder: (_) => _SeatSettingsDialog(
          seats: seats,
          ticketId: widget.ticketId!,
          slotUniqueId: box.slotUniqueId,
          slotId: box.slotId!,
          slotType: box.slotType,
          pricingType: widget.pricingType ?? 'variation',
          onUpdate: () => setState(() {}),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      Navigator.pop(context); // close loading
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Failed to load seats: $e')));
    }
  }

  void _editBox(int index) {
    final box = _boxes[index];

    final slotNameController = TextEditingController(text: box.slotName);
    final numberOfSeatsController = TextEditingController(
      text: box.numberOfSeats,
    );
    final priceController = TextEditingController(text: box.price);
    final widthController = TextEditingController(
      text: box.width.toStringAsFixed(0),
    );
    final heightController = TextEditingController(
      text: box.height.toStringAsFixed(0),
    );
    final fontSizeController = TextEditingController(
      text: box.fontSize.toString(),
    );
    final rotationController = TextEditingController(
      text: box.rotation.toString(),
    );
    final roundnessController = TextEditingController(
      text: box.roundness.toString(),
    );

    showDialog(
      context: context,
      builder: (context) {
        Color selectedColor = box.backgroundColor;
        // type '1' = Manual Selection, '2' = Auto Selection
        String selectedSlotType = box.slotType == '2'
            ? 'Auto Selection'
            : 'Manual Selection';
        bool isActive = !box.isDeactivated;

        InputDecoration deco(String label, {IconData? icon}) {
          return InputDecoration(
            labelText: label,
            prefixIcon: icon == null ? null : Icon(icon, size: 20),
            filled: true,
            fillColor: Theme.of(context).colorScheme.surface,
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 14,
              vertical: 14,
            ),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
          );
        }

        Widget sectionTitle(String t) => Padding(
          padding: const EdgeInsets.only(top: 6, bottom: 8),
          child: Text(
            t,
            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
          ),
        );

        return StatefulBuilder(
          builder: (context, setDialogState) {
            return Dialog(
              insetPadding: const EdgeInsets.symmetric(
                horizontal: 16,
                vertical: 20,
              ),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              child: ConstrainedBox(
                constraints: const BoxConstraints(
                  maxWidth: 520,
                  maxHeight: 720,
                ),
                child: Column(
                  children: [
                    // Header
                    Padding(
                      padding: const EdgeInsets.fromLTRB(18, 16, 12, 10),
                      child: Row(
                        children: [
                          Container(
                            width: 40,
                            height: 40,
                            decoration: BoxDecoration(
                              color: Theme.of(
                                context,
                              ).colorScheme.primary.withValues(alpha: 0.10),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Icon(
                              Icons.crop_square_rounded,
                              color: Theme.of(context).colorScheme.primary,
                            ),
                          ),
                          const SizedBox(width: 12),
                          const Expanded(
                            child: Text(
                              'Edit Slot',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                          ),
                          IconButton(
                            onPressed: () => Navigator.pop(context),
                            icon: const Icon(Icons.close),
                          ),
                        ],
                      ),
                    ),
                    const Divider(height: 1),

                    // Body
                    Expanded(
                      child: SingleChildScrollView(
                        padding: const EdgeInsets.fromLTRB(18, 14, 18, 18),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Wrap(
                              spacing: 8,
                              runSpacing: 8,
                              children: const [
                                _ChipPill(
                                  label: 'Drag to move',
                                  icon: Icons.pan_tool_alt_outlined,
                                ),
                                _ChipPill(
                                  label: 'Tap box to edit',
                                  icon: Icons.touch_app_outlined,
                                ),
                              ],
                            ),
                            const SizedBox(height: 14),

                            sectionTitle('Box Size & Style'),
                            Row(
                              children: [
                                Expanded(
                                  child: TextField(
                                    controller: widthController,
                                    decoration: deco(
                                      'Width (px) *',
                                      icon: Icons.swap_horiz,
                                    ),
                                    keyboardType: TextInputType.number,
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: TextField(
                                    controller: heightController,
                                    decoration: deco(
                                      'Height (px) *',
                                      icon: Icons.swap_vert,
                                    ),
                                    keyboardType: TextInputType.number,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),

                            Row(
                              children: [
                                Expanded(
                                  child: TextField(
                                    controller: fontSizeController,
                                    decoration: deco(
                                      'Font Size (px) *',
                                      icon: Icons.format_size,
                                    ),
                                    keyboardType: TextInputType.number,
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: TextField(
                                    controller: rotationController,
                                    decoration: deco(
                                      'Rotate (deg) *',
                                      icon: Icons.rotate_right,
                                    ),
                                    keyboardType: TextInputType.number,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),

                            TextField(
                              controller: roundnessController,
                              decoration: deco(
                                'Round (%) *',
                                icon: Icons.rounded_corner,
                              ),
                              keyboardType: TextInputType.number,
                            ),
                            const SizedBox(height: 12),

                            InputDecorator(
                              decoration: deco(
                                'Background *',
                                icon: Icons.palette_outlined,
                              ),
                              child: Row(
                                children: [
                                  GestureDetector(
                                    onTap: () {
                                      showDialog(
                                        context: context,
                                        builder: (innerContext) {
                                          Color pickerColor = selectedColor;
                                          return AlertDialog(
                                            shape: RoundedRectangleBorder(
                                              borderRadius:
                                                  BorderRadius.circular(16),
                                            ),
                                            title: const Text('Pick a Color'),
                                            content: SingleChildScrollView(
                                              child: ColorPicker(
                                                pickerColor: pickerColor,
                                                onColorChanged: (color) {
                                                  pickerColor = color;
                                                },
                                                pickerAreaHeightPercent: 0.8,
                                                displayThumbColor: true,
                                                enableAlpha: true,
                                                labelTypes: const [],
                                              ),
                                            ),
                                            actions: [
                                              TextButton(
                                                onPressed: () {
                                                  Navigator.pop(innerContext);
                                                },
                                                child: const Text('Cancel'),
                                              ),
                                              ElevatedButton(
                                                onPressed: () {
                                                  setDialogState(() {
                                                    selectedColor = pickerColor;
                                                  });
                                                  Navigator.pop(innerContext);
                                                },
                                                child: const Text('Select'),
                                              ),
                                            ],
                                          );
                                        },
                                      );
                                    },
                                    child: Container(
                                      width: 34,
                                      height: 34,
                                      decoration: BoxDecoration(
                                        color: selectedColor,
                                        borderRadius: BorderRadius.circular(10),
                                        border: Border.all(
                                          color: Colors.black12,
                                        ),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Text(
                                    'Tap to change',
                                    style: TextStyle(
                                      color: Theme.of(context).hintColor,
                                    ),
                                  ),
                                ],
                              ),
                            ),

                            const SizedBox(height: 18),
                            sectionTitle('Slot Details'),

                            InputDecorator(
                              decoration: deco(
                                'Slot Type *',
                                icon: Icons.category_outlined,
                              ),
                              child: DropdownButtonHideUnderline(
                                child: DropdownButton<String>(
                                  value: selectedSlotType,
                                  isExpanded: true,
                                  items: const [
                                    DropdownMenuItem(
                                      value: 'Manual Selection',
                                      child: Text('Manual Selection'),
                                    ),
                                    DropdownMenuItem(
                                      value: 'Auto Selection',
                                      child: Text('Auto Selection'),
                                    ),
                                  ],
                                  onChanged: (value) {
                                    setDialogState(
                                      () => selectedSlotType = value!,
                                    );
                                  },
                                ),
                              ),
                            ),
                            const SizedBox(height: 12),

                            TextField(
                              controller: slotNameController,
                              decoration: deco(
                                'Slot Name *',
                                icon: Icons.badge_outlined,
                              ),
                            ),
                            const SizedBox(height: 12),

                            TextField(
                              controller: numberOfSeatsController,
                              decoration: deco(
                                'Number of Seat *',
                                icon: Icons.event_seat_outlined,
                              ),
                              keyboardType: TextInputType.number,
                            ),
                            const SizedBox(height: 12),

                            TextField(
                              controller: priceController,
                              decoration: deco(
                                'Price',
                                icon: Icons.attach_money_outlined,
                              ),
                              keyboardType:
                                  const TextInputType.numberWithOptions(
                                    decimal: true,
                                  ),
                            ),
                            const SizedBox(height: 12),

                            ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                padding: const EdgeInsets.symmetric(
                                  vertical: 16,
                                  horizontal: 16,
                                ),
                                minimumSize: const Size(double.infinity, 50),
                              ),
                              onPressed: () => _openSeatNameDialog(box),
                              icon: const Icon(Icons.edit_note),
                              label: const Text('Seat Name'),
                            ),
                            const SizedBox(height: 12),

                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 14,
                                vertical: 10,
                              ),
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(14),
                                color: Theme.of(context).colorScheme.surface,
                                border: Border.all(
                                  color: Theme.of(context).dividerColor,
                                ),
                              ),
                              child: Row(
                                children: [
                                  const Expanded(
                                    child: Text(
                                      'Active / Deactive',
                                      style: TextStyle(
                                        fontSize: 14,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                  ),
                                  Switch(
                                    value: isActive,
                                    onChanged: (value) {
                                      setDialogState(() => isActive = value);
                                    },
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),

                    // Footer
                    Container(
                      padding: const EdgeInsets.fromLTRB(18, 12, 18, 16),
                      decoration: BoxDecoration(
                        border: Border(
                          top: BorderSide(
                            color: Theme.of(context).dividerColor,
                          ),
                        ),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: OutlinedButton(
                              onPressed: () => Navigator.pop(context),
                              style: OutlinedButton.styleFrom(
                                padding: const EdgeInsets.symmetric(
                                  vertical: 14,
                                ),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(14),
                                ),
                              ),
                              child: const Text('Cancel'),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: ElevatedButton(
                              onPressed: () {
                                setState(() {
                                  box.width =
                                      double.tryParse(widthController.text) ??
                                      box.width;
                                  box.height =
                                      double.tryParse(heightController.text) ??
                                      box.height;
                                  box.fontSize =
                                      double.tryParse(
                                        fontSizeController.text,
                                      ) ??
                                      box.fontSize;
                                  box.rotation =
                                      double.tryParse(
                                        rotationController.text,
                                      ) ??
                                      box.rotation;
                                  box.roundness =
                                      double.tryParse(
                                        roundnessController.text,
                                      ) ??
                                      box.roundness;
                                  box.backgroundColor = selectedColor;
                                  box.slotName = slotNameController.text.trim();
                                  box.numberOfSeats = numberOfSeatsController
                                      .text
                                      .trim();
                                  box.price =
                                      priceController.text.trim().isEmpty
                                      ? '0.00'
                                      : priceController.text.trim();
                                  box.slotType =
                                      selectedSlotType == 'Auto Selection'
                                      ? '2'
                                      : '1';
                                  box.isDeactivated = !isActive;
                                });
                                Navigator.pop(context);
                                _saveBoxToServer(box);
                              },
                              style: ElevatedButton.styleFrom(
                                padding: const EdgeInsets.symmetric(
                                  vertical: 14,
                                ),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(14),
                                ),
                              ),
                              child: const Text('Save'),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  Widget _hintBar(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        color: theme.colorScheme.primary.withValues(alpha: 0.07),
        border: Border.all(
          color: theme.colorScheme.primary.withValues(alpha: 0.16),
        ),
      ),
      child: Row(
        children: [
          Icon(Icons.touch_app_outlined, color: theme.colorScheme.primary),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              'Tap on the image to add a slot. Drag a slot to move it. Tap a slot to edit.',
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }

  ImageProvider? _currentMapProvider() {
    if (_savedMapImage != null) return FileImage(_savedMapImage!);
    if (_coverImageUrl != null) return NetworkImage(_coverImageUrl!);
    return null; // No image available
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Scaffold(
      appBar: CustomAppBar(
        title: 'Slot Settings',
        actions: [
          IconButton(
            onPressed: () {
              setState(() => _boxes.clear());
              _loadSeatMappingData();
            },
            icon: Icon(
              Icons.refresh,
              color: isDark ? Colors.grey.shade600 : Colors.grey.shade400,
            ),
          ),
        ],
      ),
      body: Consumer<SeatMappingProvider>(
        builder: (context, provider, _) {
          // Show loading indicator
          if (provider.isLoading && _boxes.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }

          // Show error message if any
          if (provider.errorMessage != null && _boxes.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.error_outline, size: 64, color: Colors.red),
                  const SizedBox(height: 16),
                  Text(
                    provider.errorMessage!,
                    style: const TextStyle(color: Colors.red),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: _loadSeatMappingData,
                    child: const Text('Retry'),
                  ),
                ],
              ),
            );
          }

          // Show main content
          return _buildMainContent(theme, provider);
        },
      ),
      bottomNavigationBar: _buildBottomBar(theme),
    );
  }

  Widget _buildMainContent(ThemeData theme, SeatMappingProvider provider) {
    return OrientationBuilder(
      builder: (context, orientation) {
        return SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'Seat Map',
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 8,
                    ),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(999),
                      border: Border.all(color: theme.dividerColor),
                    ),
                    child: Text(
                      '${_boxes.length} slot(s)',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              _hintBar(context),
              const SizedBox(height: 12),

              LayoutBuilder(
                builder: (context, constraints) {
                  final maxWidth = constraints.maxWidth;
                  // Size canvas to image aspect ratio so image fills it fully
                  final aspectRatio = imageWidth > 0 && imageHeight > 0
                      ? imageWidth / imageHeight
                      : 1.0;
                  final actualCanvasWidth = maxWidth;
                  final actualCanvasHeight = actualCanvasWidth / aspectRatio;

                  // Keep container dims in sync for position math
                  _containerWidth = actualCanvasWidth;
                  _containerHeight = actualCanvasHeight;

                  return Center(
                    child: Container(
                      key: ValueKey(
                        'canvas_${orientation.name}_${actualCanvasWidth.toStringAsFixed(0)}_${actualCanvasHeight.toStringAsFixed(0)}',
                      ),
                      width: actualCanvasWidth,
                      height: actualCanvasHeight,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(
                          color: theme.dividerColor,
                          width: 1.5,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.04),
                            blurRadius: 18,
                            offset: const Offset(0, 10),
                          ),
                        ],
                        color: theme.colorScheme.surface,
                      ),
                      clipBehavior: Clip.antiAlias,
                      child: ClipRect(
                        child: InteractiveViewer(
                          transformationController: _transformationController,
                          boundaryMargin: const EdgeInsets.all(double.infinity),
                          minScale: 0.5,
                          maxScale: 4.0,
                          child: Stack(
                            children: [
                              GestureDetector(
                                onTapDown: (details) =>
                                    _addBoxAt(details.localPosition),
                                behavior: HitTestBehavior.opaque,
                                child: Container(
                                  width: _containerWidth,
                                  height: _containerHeight,
                                  key: _imageContainerKey,
                                  decoration: BoxDecoration(
                                    color: Colors.grey.shade100,
                                    image: _currentMapProvider() != null
                                        ? DecorationImage(
                                            image: _currentMapProvider()!,
                                            // Container is already sized to the
                                            // image aspect ratio, so fill exactly
                                            fit: BoxFit.fill,
                                          )
                                        : null,
                                  ),
                                  child: _currentMapProvider() == null
                                      ? Center(
                                          child: Column(
                                            mainAxisAlignment:
                                                MainAxisAlignment.center,
                                            children: [
                                              Icon(
                                                Icons.event_seat,
                                                size: 64,
                                                color: Colors.grey.shade400,
                                              ),
                                              const SizedBox(height: 16),
                                              Text(
                                                'No seat map image available',
                                                style: TextStyle(
                                                  color: Colors.grey.shade600,
                                                  fontSize: 16,
                                                ),
                                              ),
                                              const SizedBox(height: 8),
                                              Text(
                                                'Upload an image to get started',
                                                style: TextStyle(
                                                  color: Colors.grey.shade500,
                                                  fontSize: 14,
                                                ),
                                              ),
                                            ],
                                          ),
                                        )
                                      : null,
                                ),
                              ),
                              IgnorePointer(
                                ignoring: false,
                                child: SizedBox(
                                  width: _containerWidth,
                                  height: _containerHeight,
                                  child: Stack(
                                    children: _boxes.asMap().entries.map((e) {
                                      final index = e.key;
                                      final box = e.value;

                                      final absolutePos = _getAbsolutePosition(
                                        box.relativePosition,
                                      );

                                      final imageBounds = _getImageBounds();
                                      final imageScale =
                                          imageBounds.width / imageWidth;
                                      final scaledWidth =
                                          box.width * imageScale;
                                      final scaledHeight =
                                          box.height * imageScale;

                                      return Positioned(
                                        left: absolutePos.dx - scaledWidth / 2,
                                        top: absolutePos.dy - scaledHeight / 2,
                                        child: Stack(
                                          clipBehavior: Clip.none,
                                          children: [
                                            GestureDetector(
                                              onPanUpdate: (details) {
                                                _updateBoxPosition(
                                                  index,
                                                  Offset(
                                                    absolutePos.dx +
                                                        details.delta.dx,
                                                    absolutePos.dy +
                                                        details.delta.dy,
                                                  ),
                                                );
                                              },
                                              onPanEnd: (_) =>
                                                  _dragDropBox(_boxes[index]),
                                              onTap: () => _editBox(index),
                                              behavior: HitTestBehavior.opaque,
                                              child: Transform.rotate(
                                                angle:
                                                    box.rotation *
                                                    3.14159 /
                                                    180,
                                                child: AnimatedContainer(
                                                  duration: const Duration(
                                                    milliseconds: 120,
                                                  ),
                                                  width: scaledWidth,
                                                  height: scaledHeight,
                                                  decoration: BoxDecoration(
                                                    color: box.effectiveColor
                                                        .withValues(
                                                          alpha: 0.72,
                                                        ),
                                                    borderRadius:
                                                        BorderRadius.circular(
                                                          (box.roundness *
                                                                  imageScale)
                                                              .clamp(2, 40),
                                                        ),
                                                    border: Border.all(
                                                      color: Colors.white
                                                          .withValues(
                                                            alpha: 0.9,
                                                          ),
                                                      width: (2 * imageScale)
                                                          .clamp(1, 4),
                                                    ),
                                                    boxShadow: [
                                                      BoxShadow(
                                                        color: Colors.black
                                                            .withValues(
                                                              alpha: 0.10,
                                                            ),
                                                        blurRadius: 10,
                                                        offset: const Offset(
                                                          0,
                                                          6,
                                                        ),
                                                      ),
                                                    ],
                                                  ),
                                                  child: box.slotName.isNotEmpty
                                                      ? Padding(
                                                          padding:
                                                              const EdgeInsets.all(
                                                                3,
                                                              ),
                                                          child: Center(
                                                            child: FittedBox(
                                                              fit: BoxFit
                                                                  .scaleDown,
                                                              child: Text(
                                                                box.slotName,
                                                                textAlign:
                                                                    TextAlign
                                                                        .center,
                                                                maxLines: 2,
                                                                overflow:
                                                                    TextOverflow
                                                                        .ellipsis,
                                                                style: const TextStyle(
                                                                  color: Colors
                                                                      .white,
                                                                  fontWeight:
                                                                      FontWeight
                                                                          .w700,
                                                                  fontSize: 11,
                                                                  shadows: [
                                                                    Shadow(
                                                                      color: Colors
                                                                          .black38,
                                                                      blurRadius:
                                                                          4,
                                                                    ),
                                                                  ],
                                                                ),
                                                              ),
                                                            ),
                                                          ),
                                                        )
                                                      : null,
                                                ),
                                              ),
                                            ),
                                            Positioned(
                                              top: -10 * imageScale,
                                              right: -10 * imageScale,
                                              child: GestureDetector(
                                                onTap: () => _removeBox(index),
                                                behavior:
                                                    HitTestBehavior.opaque,
                                                child: Container(
                                                  padding: EdgeInsets.all(
                                                    (6 * imageScale).clamp(
                                                      4,
                                                      10,
                                                    ),
                                                  ),
                                                  decoration: BoxDecoration(
                                                    color:
                                                        theme.colorScheme.error,
                                                    shape: BoxShape.circle,
                                                    boxShadow: [
                                                      BoxShadow(
                                                        color: Colors.black
                                                            .withValues(
                                                              alpha: 0.18,
                                                            ),
                                                        blurRadius: 10,
                                                        offset: const Offset(
                                                          0,
                                                          6,
                                                        ),
                                                      ),
                                                    ],
                                                  ),
                                                  child: Icon(
                                                    Icons.close,
                                                    color: Colors.white,
                                                    size: (16 * imageScale)
                                                        .clamp(14, 20),
                                                  ),
                                                ),
                                              ),
                                            ),
                                          ],
                                        ),
                                      );
                                    }).toList(),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  );
                },
              ),

              const SizedBox(height: 14),

              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 10,
                ),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: theme.dividerColor),
                  color: theme.colorScheme.surface,
                ),
                child: Row(
                  children: [
                    IconButton.filledTonal(
                      onPressed: _zoomOut,
                      icon: const Icon(Icons.zoom_out),
                      tooltip: 'Zoom Out',
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: ElevatedButton.icon(
                        onPressed: _resetZoom,
                        icon: const Icon(Icons.restart_alt),
                        label: const Text('Reset'),
                        style: ElevatedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    IconButton.filledTonal(
                      onPressed: _zoomIn,
                      icon: const Icon(Icons.zoom_in),
                      tooltip: 'Zoom In',
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildBottomBar(ThemeData theme) {
    return SafeArea(
      child: Container(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
        decoration: BoxDecoration(
          border: Border(top: BorderSide(color: theme.dividerColor)),
        ),
        child: ElevatedButton.icon(
          onPressed: _openUploadSheet,
          icon: const Icon(Icons.upload),
          label: Text(
            _savedMapImage == null ? 'Upload Map Image' : 'Change Map Image',
          ),
          style: ElevatedButton.styleFrom(
            padding: const EdgeInsets.symmetric(vertical: 14),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
            ),
          ),
        ),
      ),
    );
  }
}

class _ChipPill extends StatelessWidget {
  final String label;
  final IconData icon;

  const _ChipPill({required this.label, required this.icon});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: theme.dividerColor),
        color: theme.colorScheme.surface,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: theme.colorScheme.primary),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
          ),
        ],
      ),
    );
  }
}

class _UploadSeatMapSheet extends StatefulWidget {
  final File? initialImage;
  final ValueChanged<File?> onSave;

  const _UploadSeatMapSheet({required this.initialImage, required this.onSave});

  @override
  State<_UploadSeatMapSheet> createState() => _UploadSeatMapSheetState();
}

class _UploadSeatMapSheetState extends State<_UploadSeatMapSheet> {
  final ImagePicker _picker = ImagePicker();
  File? _preview;

  @override
  void initState() {
    super.initState();
    _preview = widget.initialImage;
  }

  Future<void> _pick(ImageSource source) async {
    try {
      final XFile? x = await _picker.pickImage(
        source: source,
        imageQuality: 85,
      );
      if (x == null) return;
      setState(() => _preview = File(x.path));
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return SafeArea(
      child: Container(
        decoration: BoxDecoration(
          color: theme.colorScheme.surface,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(22)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.14),
              blurRadius: 20,
              offset: const Offset(0, -10),
            ),
          ],
        ),
        padding: EdgeInsets.fromLTRB(
          16,
          10,
          16,
          16 + MediaQuery.of(context).viewInsets.bottom,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 46,
              height: 5,
              decoration: BoxDecoration(
                color: Colors.grey.shade400,
                borderRadius: BorderRadius.circular(999),
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: theme.colorScheme.primary.withValues(alpha: 0.10),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(
                    Icons.map_outlined,
                    color: theme.colorScheme.primary,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Upload Seat Map',
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
                IconButton(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.close),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: theme.dividerColor),
                color: theme.colorScheme.surface,
              ),
              child: _preview == null
                  ? Padding(
                      padding: const EdgeInsets.all(18),
                      child: Column(
                        children: [
                          Container(
                            width: 64,
                            height: 64,
                            decoration: BoxDecoration(
                              color: theme.colorScheme.primary.withValues(
                                alpha: 0.08,
                              ),
                              borderRadius: BorderRadius.circular(18),
                            ),
                            child: Icon(
                              Icons.image_outlined,
                              size: 34,
                              color: theme.colorScheme.primary,
                            ),
                          ),
                          const SizedBox(height: 12),
                          Text(
                            'Pick an image to preview here',
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: theme.hintColor,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    )
                  : ClipRRect(
                      borderRadius: BorderRadius.circular(18),
                      child: AspectRatio(
                        aspectRatio: 16 / 10,
                        child: Image.file(_preview!, fit: BoxFit.cover),
                      ),
                    ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => _pick(ImageSource.camera),
                    icon: const Icon(Icons.photo_camera_outlined),
                    label: const Text('Camera'),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => _pick(ImageSource.gallery),
                    icon: const Icon(Icons.photo_library_outlined),
                    label: const Text('Gallery'),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            Row(
              children: [
                Expanded(
                  child: TextButton.icon(
                    onPressed: () => setState(() => _preview = null),
                    icon: Icon(
                      Icons.delete_outline,
                      color: theme.colorScheme.error,
                    ),
                    label: Text(
                      'Clear',
                      style: TextStyle(
                        color: theme.colorScheme.error,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  flex: 2,
                  child: ElevatedButton.icon(
                    onPressed: () => widget.onSave(_preview),
                    icon: const Icon(Icons.check_circle_outline),
                    label: const Text('Save'),
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// ===================================================================
// Seat Settings Dialog (used by "Seat Name" button)
// ===================================================================

class _SeatSettingsDialog extends StatefulWidget {
  final List<SeatItem> seats;
  final VoidCallback? onUpdate;
  final int ticketId;
  final String slotUniqueId;
  final int slotId;
  final String slotType;
  final String pricingType;

  const _SeatSettingsDialog({
    required this.seats,
    required this.ticketId,
    required this.slotUniqueId,
    required this.slotId,
    required this.slotType,
    required this.pricingType,
    this.onUpdate,
  });

  @override
  State<_SeatSettingsDialog> createState() => _SeatSettingsDialogState();
}

class _SeatSettingsDialogState extends State<_SeatSettingsDialog> {
  bool _isUpdating = false;
  late List<SeatRowData> _seatRows;

  @override
  void initState() {
    super.initState();
    _seatRows = widget.seats
        .map(
          (s) => SeatRowData(
            seatId: s.id,
            seatLabel: s.name,
            isBooked: s.isBooked == 1,
            isDeactive: s.isDeactive == '1',
            nameController: TextEditingController(text: s.name),
            priceController: TextEditingController(text: s.price),
          ),
        )
        .toList();
  }

  @override
  void dispose() {
    for (final r in _seatRows) {
      r.nameController.dispose();
      r.priceController.dispose();
    }
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() => _isUpdating = true);
    try {
      final seatKeys = _seatRows.map((s) => s.seatId.toString()).toList();
      final keyName = {
        for (final s in _seatRows)
          s.seatId.toString(): s.nameController.text.trim(),
      };
      final keyPrice = {
        for (final s in _seatRows)
          s.seatId.toString(): s.priceController.text.trim(),
      };
      final slotDeactiveInput = {
        for (final s in _seatRows)
          s.seatId.toString(): s.isDeactive ? '1' : '0',
      };
      await SeatMappingService().updateSeats(
        ticketId: widget.ticketId,
        slotUniqueId: widget.slotUniqueId,
        slotId: widget.slotId,
        slotType: widget.slotType,
        pricingType: widget.pricingType,
        seatKeys: seatKeys,
        keyName: keyName,
        keyPrice: keyPrice,
        slotDeactiveInput: slotDeactiveInput,
      );
      widget.onUpdate?.call();
      if (mounted) Navigator.pop(context);
    } catch (e) {
      if (mounted) {
        setState(() => _isUpdating = false);
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Failed to update seats: $e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 820, maxHeight: 620),
        child: Column(
          children: [
            // Header
            Padding(
              padding: const EdgeInsets.fromLTRB(18, 14, 10, 12),
              child: Row(
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: theme.colorScheme.primary.withValues(alpha: 0.10),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Icon(
                      Icons.event_seat_outlined,
                      color: theme.colorScheme.primary,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      'Seat Settings',
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close),
                  ),
                ],
              ),
            ),
            Divider(height: 1, color: theme.dividerColor),

            // Table header
            Builder(
              builder: (context) {
                final isManual = widget.slotType == '1';
                return Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 20,
                    vertical: 12,
                  ),
                  color: theme.colorScheme.surface,
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children: [
                      const Expanded(flex: 2, child: _HeaderCell('Slot')),
                      const Spacer(),
                      const Expanded(
                        flex: 4,
                        child: _HeaderCell('Set Seat Name'),
                      ),
                      if (isManual) ...const [
                        Spacer(),
                        Expanded(flex: 3, child: _HeaderCell('Price')),
                      ],
                      const Spacer(),
                      const Expanded(flex: 2, child: _HeaderCell('Active')),
                    ],
                  ),
                );
              },
            ),
            Divider(height: 1, color: theme.dividerColor),

            // Rows
            Expanded(
              child: Scrollbar(
                child: ListView.separated(
                  padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
                  itemCount: _seatRows.length,
                  separatorBuilder: (_, _) => const SizedBox(height: 10),
                  itemBuilder: (context, i) {
                    final seat = _seatRows[i];
                    return _SeatRowCard(
                      seat: seat,
                      showPrice: widget.slotType == '1',
                      onChanged: () => setState(() {}),
                    );
                  },
                ),
              ),
            ),

            Divider(height: 1, color: theme.dividerColor),

            // Footer
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
              child: Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () => Navigator.pop(context),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                      ),
                      child: const Text('Cancel'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    flex: 2,
                    child: ElevatedButton(
                      onPressed: _isUpdating ? null : _submit,
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                      ),
                      child: _isUpdating
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Text(
                              'Update',
                              style: TextStyle(fontWeight: FontWeight.w800),
                            ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _SeatRowCard extends StatelessWidget {
  final SeatRowData seat;
  final bool showPrice;
  final VoidCallback onChanged;

  const _SeatRowCard({
    required this.seat,
    required this.showPrice,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    InputDecoration fieldDeco() => InputDecoration(
      isDense: true,
      hintText: 'Enter seat name',
      filled: true,
      fillColor: theme.colorScheme.surface,
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: theme.dividerColor),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: theme.colorScheme.primary, width: 1.4),
      ),
    );

    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: theme.dividerColor),
        color: theme.colorScheme.surface,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 16,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
      child: Row(
        children: [
          SizedBox(
            width: 72,
            child: Column(
              children: [
                Text(
                  seat.seatLabel,
                  style: const TextStyle(fontWeight: FontWeight.w800),
                  overflow: TextOverflow.ellipsis,
                ),
                if (seat.isBooked) ...[
                  const SizedBox(height: 4),
                  _StatusPill(
                    text: 'Booked',
                    background: theme.colorScheme.tertiaryContainer,
                    foreground: theme.colorScheme.onTertiaryContainer,
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            flex: 4,
            child: SizedBox(
              width: 200,
              child: TextField(
                controller: seat.nameController,
                decoration: fieldDeco(),
                onChanged: (_) => onChanged(),
              ),
            ),
          ),
          if (showPrice) ...[
            const SizedBox(width: 8),
            Expanded(
              flex: 3,
              child: TextField(
                controller: seat.priceController,
                decoration: fieldDeco().copyWith(hintText: 'Price'),
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                onChanged: (_) => onChanged(),
              ),
            ),
          ],
          const SizedBox(width: 14),
          SizedBox(
            width: 60,
            child: Align(
              alignment: Alignment.centerRight,
              child: Switch(
                value: !seat.isDeactive,
                onChanged: (v) {
                  seat.isDeactive = !v;
                  onChanged();
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _HeaderCell extends StatelessWidget {
  final String text;
  const _HeaderCell(this.text);

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Text(
      text,
      style: theme.textTheme.bodyMedium?.copyWith(
        fontWeight: FontWeight.w900,
        color: theme.colorScheme.onSurface.withValues(alpha: 0.70),
      ),
    );
  }
}

class _StatusPill extends StatelessWidget {
  final String text;
  final Color background;
  final Color foreground;

  const _StatusPill({
    required this.text,
    required this.background,
    required this.foreground,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: Colors.black12),
      ),
      child: Text(
        text,
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w900,
          color: foreground,
        ),
      ),
    );
  }
}
