import 'dart:io';
import 'dart:ui' as ui;
import 'package:dotted_border/dotted_border.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_checkbox.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_header_text_widget.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_toggle_button.dart';
import 'package:booktkit_organizer/features/event_management/data/models/event_detail_model.dart';
import 'package:booktkit_organizer/features/event_management/data/models/update_event_request.dart';
import 'package:booktkit_organizer/features/event_management/providers/add_event_provider.dart';
import 'package:booktkit_organizer/features/event_management/providers/categories_provider.dart';
import 'package:booktkit_organizer/features/event_management/providers/event_management_provider.dart';
import 'package:booktkit_organizer/services/event_management_service.dart';
import 'package:booktkit_organizer/features/nav_appbar/ui/widgets/app_text_styles.dart';
import 'package:flutter/material.dart';
import 'package:flutter_quill/flutter_quill.dart';
import 'package:flutter_quill_delta_from_html/flutter_quill_delta_from_html.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

class EditOnlineEvent extends StatefulWidget {
  final int? eventId;
  const EditOnlineEvent({super.key, this.eventId});

  @override
  State<EditOnlineEvent> createState() => _EditOnlineEventState();
}

class DateTimeSlot {
  int? id; // For updating existing dates
  DateTime? startDate;
  TimeOfDay? startTime;
  DateTime? endDate;
  TimeOfDay? endTime;

  DateTimeSlot({
    this.id,
    this.startDate,
    this.startTime,
    this.endDate,
    this.endTime,
  });
}

final List<String> status = ['Active', 'Inactive'];
final List<String> featuredStatus = ['Yes', 'No'];
final List<String> discount = ['Fixed', 'Percentage'];
final List<String> languages = ['English', 'Arabic'];
final List<String> categories = ['Sports', 'Music', 'Movie'];
List<DateTimeSlot> dateTimeSlots = [DateTimeSlot()];
String? selectedValue;
String? selectedFeaturedValue;
String? selectedDiscountValue;
String? selectedCategoryValue;

String selectedLanguageValue = languages.first;
bool _isLimitedTicket = true;
bool _isLimitedCustomer = true;
bool _isSingle = true;
bool _countdown = true;
bool _earlyBird = true;
bool _cloneLanguage = true;

class _EditOnlineEventState extends State<EditOnlineEvent> {
  DateTime? startDate;
  DateTime? endDate;
  DateTime? discountEndDate;
  TimeOfDay? startTime;
  TimeOfDay? endTime;
  TimeOfDay? discountEndTime;
  List<XFile> galleryImages = [];
  XFile? thumbnailImage;
  final ImagePicker _picker = ImagePicker();
  List<EventImage> _existingGalleryImages = [];
  String? _existingThumbnailUrl;

  // Text editing controllers
  final _titleController = TextEditingController();
  QuillController _descriptionController = QuillController.basic();
  final _refundPolicyController = TextEditingController();
  final _metaKeywordsController = TextEditingController();
  final _metaDescriptionController = TextEditingController();
  final _priceController = TextEditingController();
  final _meetingUrlController = TextEditingController();
  final _totalTicketsController = TextEditingController();
  final _maxTicketsController = TextEditingController();
  final _discountAmountController = TextEditingController();

  // Store all content entries and language infos for language switching
  List<EventContent> _allContents = [];
  List<LanguageInfo> _allLanguageInfos = [];
  bool _isRtl = false; // true when the selected language is RTL

  @override
  void initState() {
    super.initState();
    if (widget.eventId != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        final provider = context.read<EventManagementProvider>();
        provider.fetchEventDetail(widget.eventId!).then((_) {
          if (!mounted) return;
          final detail = provider.eventDetail;
          if (detail == null) return;

          final ev = detail.event;
          final defaultContent = detail.eventContents.isNotEmpty
              ? detail.eventContents.first
              : null;

          setState(() {
            // Status & featured
            selectedValue = ev.isActive ? 'Active' : 'Inactive';
            selectedFeaturedValue = ev.isFeaturedBool ? 'Yes' : 'No';

            // Date type
            _isSingle = ev.isSingleDate;
            _countdown = ev.isCountdownActive;

            // Dates
            if (ev.startDate != null) {
              startDate = DateTime.tryParse(ev.startDate!);
            }
            if (ev.endDate != null) {
              endDate = DateTime.tryParse(ev.endDate!);
            }
            if (ev.startTime != null) {
              final parts = ev.startTime!.split(':');
              if (parts.length >= 2) {
                startTime = TimeOfDay(
                  hour: int.tryParse(parts[0]) ?? 0,
                  minute: int.tryParse(parts[1]) ?? 0,
                );
              }
            }
            if (ev.endTime != null) {
              final parts = ev.endTime!.split(':');
              if (parts.length >= 2) {
                endTime = TimeOfDay(
                  hour: int.tryParse(parts[0]) ?? 0,
                  minute: int.tryParse(parts[1]) ?? 0,
                );
              }
            }

            // Multiple date slots
            if (!_isSingle && detail.eventDates.isNotEmpty) {
              dateTimeSlots = detail.eventDates.map((d) {
                TimeOfDay? parsedStartTime;
                TimeOfDay? parsedEndTime;
                if (d.startTime != null) {
                  final p = d.startTime!.split(':');
                  if (p.length >= 2) {
                    parsedStartTime = TimeOfDay(
                      hour: int.tryParse(p[0]) ?? 0,
                      minute: int.tryParse(p[1]) ?? 0,
                    );
                  }
                }
                if (d.endTime != null) {
                  final p = d.endTime!.split(':');
                  if (p.length >= 2) {
                    parsedEndTime = TimeOfDay(
                      hour: int.tryParse(p[0]) ?? 0,
                      minute: int.tryParse(p[1]) ?? 0,
                    );
                  }
                }
                return DateTimeSlot(
                  id: d.id, // Store the existing date ID
                  startDate: d.startDate != null
                      ? DateTime.tryParse(d.startDate!)
                      : null,
                  startTime: parsedStartTime,
                  endDate: d.endDate != null
                      ? DateTime.tryParse(d.endDate!)
                      : null,
                  endTime: parsedEndTime,
                );
              }).toList();
            }

            // Images
            _existingGalleryImages = detail.eventImages;
            _existingThumbnailUrl = ev.thumbnail;

            // Languages from API
            if (detail.languages.isNotEmpty) {
              languages.clear();
              languages.addAll(detail.languages.map((l) => l.name).toList());
              selectedLanguageValue = languages.first;
            }

            // Category — pre-select from content
            if (defaultContent?.eventCategoryId != null) {
              selectedCategoryValue = defaultContent!.eventCategoryId;
            }

            // Content for default language
            if (defaultContent != null) {
              _titleController.text = defaultContent.title;
              _loadDescriptionHtml(defaultContent.description ?? '');
              _refundPolicyController.text = defaultContent.refundPolicy ?? '';
              _metaKeywordsController.text = defaultContent.metaKeywords ?? '';
              _metaDescriptionController.text =
                  defaultContent.metaDescription ?? '';
            }

            // Store all contents/languages for language switching
            _allContents = detail.eventContents;
            _allLanguageInfos = detail.languages;

            // Set RTL for the default language
            if (detail.languages.isNotEmpty) {
              _isRtl = detail.languages.first.isRtl;
            }

            // Fetch categories using the first language's ID
            final firstLangId = detail.languages.isNotEmpty
                ? int.tryParse(detail.languages.first.id.toString())
                : null;
            if (firstLangId != null) {
              context.read<CategoriesProvider>().fetchCategories(firstLangId);
            }

            // Meeting URL
            _meetingUrlController.text = ev.meetingUrl ?? '';

            // Ticket info
            final ticket = ev.ticket;
            if (ticket != null) {
              _isLimitedTicket = ticket.isUnlimitedTickets;
              _isLimitedCustomer = ticket.isUnlimitedPerCustomer;
              _earlyBird = ticket.isEarlyBirdEnabled;
              _priceController.text = ticket.isFree ? '' : ticket.price;
              _totalTicketsController.text = ticket.ticketAvailable ?? '';
              _maxTicketsController.text = ticket.maxBuyTicket ?? '';

              if (ticket.isEarlyBirdEnabled) {
                selectedDiscountValue =
                    ticket.earlyBirdDiscountType == 'percentage'
                    ? 'Percentage'
                    : 'Fixed';
                _discountAmountController.text =
                    ticket.earlyBirdDiscountAmount ?? '';
                if (ticket.earlyBirdDiscountDate != null) {
                  discountEndDate = DateTime.tryParse(
                    ticket.earlyBirdDiscountDate!,
                  );
                }
                if (ticket.earlyBirdDiscountTime != null) {
                  final parts = ticket.earlyBirdDiscountTime!.split(':');
                  if (parts.length >= 2) {
                    discountEndTime = TimeOfDay(
                      hour: int.tryParse(parts[0]) ?? 0,
                      minute: int.tryParse(parts[1]) ?? 0,
                    );
                  }
                }
              }
            }
          });
        });
      });
    }
  }

  @override
  void dispose() {
    _titleController.dispose();
    _descriptionController.dispose();
    _refundPolicyController.dispose();
    _metaKeywordsController.dispose();
    _metaDescriptionController.dispose();
    _priceController.dispose();
    _meetingUrlController.dispose();
    _totalTicketsController.dispose();
    _maxTicketsController.dispose();
    _discountAmountController.dispose();
    super.dispose();
  }

  /// Loads HTML content into the QuillController
  void _loadDescriptionHtml(String html) {
    if (html.isEmpty) {
      _descriptionController = QuillController.basic();
      return;
    }
    try {
      final converter = HtmlToDelta();
      final delta = converter.convert(html);
      _descriptionController = QuillController(
        document: Document.fromDelta(delta),
        selection: const TextSelection.collapsed(offset: 0),
      );
    } catch (_) {
      _descriptionController = QuillController(
        document: Document()..insert(0, html),
        selection: const TextSelection.collapsed(offset: 0),
      );
    }
  }

  /// Called when user switches language in the dropdown
  void _onLanguageChanged(String languageName) {
    setState(() {
      selectedLanguageValue = languageName;

      final langInfo = _allLanguageInfos.firstWhere(
        (l) => l.name == languageName,
        orElse: () => _allLanguageInfos.first,
      );

      // Update RTL for the selected language
      _isRtl = langInfo.isRtl;

      final content = _allContents.cast<EventContent?>().firstWhere(
        (c) => c!.languageId == langInfo.id.toString(),
        orElse: () => null,
      );

      if (content != null) {
        _titleController.text = content.title;
        _loadDescriptionHtml(content.description ?? '');
        _refundPolicyController.text = content.refundPolicy ?? '';
        _metaKeywordsController.text = content.metaKeywords ?? '';
        _metaDescriptionController.text = content.metaDescription ?? '';
      } else {
        _titleController.clear();
        _loadDescriptionHtml('');
        _refundPolicyController.clear();
        _metaKeywordsController.clear();
        _metaDescriptionController.clear();
      }
    });
  }

  Future<void> _pickGalleryImages() async {
    try {
      final List<XFile> images = await _picker.pickMultiImage();
      if (images.isNotEmpty) {
        setState(() {
          galleryImages.addAll(images);
        });
      }
    } catch (e) {
      // Handle error
    }
  }

  Future<void> _pickThumbnailImage() async {
    try {
      final XFile? image = await _picker.pickImage(source: ImageSource.gallery);
      if (image != null) {
        setState(() {
          thumbnailImage = image;
        });
      }
    } catch (e) {
      // Handle error
    }
  }

  void _removeGalleryImage(int index) {
    setState(() {
      galleryImages.removeAt(index);
    });
  }

  Future<void> _deleteEventImage(int imageId, int index) async {
    try {
      final service = EventManagementService();
      await service.deleteEventImage(imageId);

      setState(() {
        _existingGalleryImages.removeAt(index);
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Image deleted successfully'),
            backgroundColor: Colors.green,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(e.toString().replaceAll('Exception: ', '')),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  void _removeThumbnailImage() {
    setState(() {
      thumbnailImage = null;
    });
  }

  Future<void> _selectDate(
    BuildContext context,
    String dateType, [
    int? slotIndex,
  ]) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2030),
    );
    if (picked != null) {
      setState(() {
        if (slotIndex != null) {
          // For additional date/time slots
          if (dateType == 'start') {
            dateTimeSlots[slotIndex].startDate = picked;
          } else if (dateType == 'end') {
            dateTimeSlots[slotIndex].endDate = picked;
          }
        } else {
          // For original single date fields
          if (dateType == 'start') {
            startDate = picked;
          } else if (dateType == 'end') {
            endDate = picked;
          } else if (dateType == 'discount') {
            discountEndDate = picked;
          }
        }
      });
    }
  }

  Future<void> _selectTime(
    BuildContext context,
    String timeType, [
    int? slotIndex,
  ]) async {
    final TimeOfDay? picked = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.now(),
    );
    if (picked != null) {
      setState(() {
        if (slotIndex != null) {
          // For additional date/time slots
          if (timeType == 'start') {
            dateTimeSlots[slotIndex].startTime = picked;
          } else if (timeType == 'end') {
            dateTimeSlots[slotIndex].endTime = picked;
          }
        } else {
          // For original single date fields
          if (timeType == 'start') {
            startTime = picked;
          } else if (timeType == 'end') {
            endTime = picked;
          } else if (timeType == 'discount') {
            discountEndTime = picked;
          }
        }
      });
    }
  }

  void _addDateTimeSlot() {
    setState(() {
      dateTimeSlots.add(DateTimeSlot());
    });
  }

  void _removeDateTimeSlot(int index) {
    setState(() {
      if (dateTimeSlots.length > 1) {
        dateTimeSlots.removeAt(index);
      }
    });
  }

  Future<void> _deleteEventDate(int dateId, int index) async {
    try {
      final service = EventManagementService();
      await service.deleteEventDate(dateId);

      setState(() {
        if (dateTimeSlots.length > 1) {
          dateTimeSlots.removeAt(index);
        }
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Date deleted successfully'),
            backgroundColor: Colors.green,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(e.toString().replaceAll('Exception: ', '')),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _updateEvent(BuildContext context) async {
    final provider = context.read<AddEventProvider>();
    final eventProvider = context.read<EventManagementProvider>();
    final eventDetail = eventProvider.eventDetail;

    if (eventDetail == null || widget.eventId == null) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Event data not loaded')));
      return;
    }

    final languages = _allLanguageInfos;

    // Basic validation
    if (_titleController.text.trim().isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Please enter event title')));
      return;
    }

    // Validate meeting URL
    if (_meetingUrlController.text.trim().isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Please enter meeting URL')));
      return;
    }

    // Validate date/time based on date type
    if (_isSingle) {
      if (startDate == null ||
          startTime == null ||
          endDate == null ||
          endTime == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Please select all date and time fields'),
          ),
        );
        return;
      }
    } else {
      // Multiple dates validation
      for (int i = 0; i < dateTimeSlots.length; i++) {
        final slot = dateTimeSlots[i];
        if (slot.startDate == null ||
            slot.startTime == null ||
            slot.endDate == null ||
            slot.endTime == null) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                'Please complete all fields for date slot ${i + 1}',
              ),
            ),
          );
          return;
        }
      }
    }

    // Build language-specific maps from all contents
    final Map<String, String> titles = {};
    final Map<String, String> categoryIds = {};
    final Map<String, String> descriptions = {};
    final Map<String, String> refundPolicies = {};
    final Map<String, String> metaKeywords = {};
    final Map<String, String> metaDescriptions = {};

    // Collect data from all language contents
    for (final lang in languages) {
      final content = _allContents.cast<EventContent?>().firstWhere(
        (c) => c!.languageId == lang.id.toString(),
        orElse: () => null,
      );

      if (content != null) {
        titles[lang.code] = content.title;
        categoryIds[lang.code] = content.eventCategoryId ?? '';
        descriptions[lang.code] = content.description ?? '';
        refundPolicies[lang.code] = content.refundPolicy ?? '';
        metaKeywords[lang.code] = content.metaKeywords ?? '';
        metaDescriptions[lang.code] = content.metaDescription ?? '';
      }
    }

    // Update current language data with latest form values
    final currentLang = languages.firstWhere(
      (l) => l.name == selectedLanguageValue,
      orElse: () => languages.first,
    );

    // Get HTML content from Quill editor
    final deltaJson = _descriptionController.document.toDelta().toJson();
    String htmlDescription = '';
    try {
      // Extract plain text and basic formatting into HTML
      final ops = deltaJson as List;
      for (var op in ops) {
        if (op is Map && op['insert'] != null) {
          htmlDescription += op['insert'].toString();
        }
      }
    } catch (e) {
      htmlDescription = _descriptionController.document.toPlainText();
    }

    titles[currentLang.code] = _titleController.text.trim();
    descriptions[currentLang.code] = htmlDescription;
    refundPolicies[currentLang.code] = _refundPolicyController.text.trim();
    metaKeywords[currentLang.code] = _metaKeywordsController.text.trim();
    metaDescriptions[currentLang.code] = _metaDescriptionController.text.trim();

    // Get category from current selection
    if (selectedCategoryValue != null) {
      categoryIds[currentLang.code] = selectedCategoryValue!;
    }

    // Prepare date/time data
    String? singleStartDate, singleStartTime, singleEndDate, singleEndTime;
    List<String>? dateIds;
    List<String>? multipleStartDates,
        multipleStartTimes,
        multipleEndDates,
        multipleEndTimes;

    if (_isSingle) {
      singleStartDate = DateFormat('yyyy-MM-dd').format(startDate!);
      singleStartTime =
          '${startTime!.hour.toString().padLeft(2, '0')}:${startTime!.minute.toString().padLeft(2, '0')}';
      singleEndDate = DateFormat('yyyy-MM-dd').format(endDate!);
      singleEndTime =
          '${endTime!.hour.toString().padLeft(2, '0')}:${endTime!.minute.toString().padLeft(2, '0')}';
    } else {
      // Collect existing date IDs
      dateIds = dateTimeSlots
          .where((slot) => slot.id != null)
          .map((slot) => slot.id.toString())
          .toList();

      multipleStartDates = dateTimeSlots
          .map((slot) => ' ${DateFormat('yyyy-MM-dd').format(slot.startDate!)}')
          .toList();
      multipleStartTimes = dateTimeSlots
          .map(
            (slot) =>
                '${slot.startTime!.hour.toString().padLeft(2, '0')}:${slot.startTime!.minute.toString().padLeft(2, '0')}',
          )
          .toList();
      multipleEndDates = dateTimeSlots
          .map((slot) => ' ${DateFormat('yyyy-MM-dd').format(slot.endDate!)}')
          .toList();
      multipleEndTimes = dateTimeSlots
          .map(
            (slot) =>
                ' ${slot.endTime!.hour.toString().padLeft(2, '0')}:${slot.endTime!.minute.toString().padLeft(2, '0')}',
          )
          .toList();
    }

    // Prepare ticket data from event detail
    final ticket = eventDetail.event.ticket;
    String earlyBirdType = 'disable';
    String? discountType;
    String? earlyBirdAmount;
    String? earlyBirdDate;
    String? earlyBirdTime;
    String? price;
    String ticketAvailableType = 'unlimited';
    String? ticketAvailable;
    String maxTicketBuyType = 'unlimited';
    String? maxBuyTicket;

    if (ticket != null) {
      price = ticket.price.toString();
      ticketAvailableType = ticket.ticketAvailableType == 'limited'
          ? 'limited'
          : 'unlimited';
      if (ticketAvailableType == 'limited') {
        ticketAvailable = ticket.ticketAvailable?.toString();
      }
      maxTicketBuyType = ticket.maxTicketBuyType == 'limited'
          ? 'limited'
          : 'unlimited';
      if (maxTicketBuyType == 'limited') {
        maxBuyTicket = ticket.maxBuyTicket?.toString();
      }
      if (ticket.isEarlyBirdEnabled) {
        earlyBirdType = 'enable';
        discountType = ticket.earlyBirdDiscountType == 'percentage'
            ? 'percentage'
            : 'fixed';
        earlyBirdAmount = ticket.earlyBirdDiscountAmount?.toString();
        earlyBirdDate = ticket.earlyBirdDiscountDate;
        earlyBirdTime = ticket.earlyBirdDiscountTime;
      }
    }

    // Create request
    final request = UpdateEventRequest(
      eventId: widget.eventId!.toString(),
      eventType: 'online',
      dateType: _isSingle ? 'single' : 'multiple',
      countdownStatus: _isSingle ? (_countdown ? '1' : '2') : '0',
      status: selectedValue == 'Active' ? '1' : '0',
      isFeatured: selectedFeaturedValue == 'Yes' ? 'yes' : 'no',
      meetingUrl: _meetingUrlController.text.trim(),
      // Only include new images if selected
      sliderImagePaths: galleryImages.isNotEmpty
          ? galleryImages.map((img) => img.path).toList()
          : null,
      thumbnailPath: thumbnailImage?.path,
      earlyBirdDiscountType: earlyBirdType,
      discountType: discountType,
      earlyBirdDiscountAmount: earlyBirdAmount,
      earlyBirdDiscountDate: earlyBirdDate,
      earlyBirdDiscountTime: earlyBirdTime,
      price: price,
      ticketAvailableType: ticketAvailableType,
      ticketAvailable: ticketAvailable,
      maxTicketBuyType: maxTicketBuyType,
      maxBuyTicket: maxBuyTicket,
      startDate: singleStartDate,
      startTime: singleStartTime,
      endDate: singleEndDate,
      endTime: singleEndTime,
      dateIds: dateIds,
      multipleStartDates: multipleStartDates,
      multipleStartTimes: multipleStartTimes,
      multipleEndDates: multipleEndDates,
      multipleEndTimes: multipleEndTimes,
      titles: titles,
      categoryIds: categoryIds,
      countries: {},
      states: {},
      cities: {},
      addresses: {},
      zipCodes: {},
      descriptions: descriptions,
      refundPolicies: refundPolicies,
      metaKeywords: metaKeywords,
      metaDescriptions: metaDescriptions,
    );

    // Submit
    final success = await provider.updateEvent(request);

    if (context.mounted) {
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Event updated successfully!'),
            backgroundColor: Colors.green,
          ),
        );
        Navigator.of(context).pop(true);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(provider.createError ?? 'Failed to update event'),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    // Show loading/error state when fetching event details
    if (widget.eventId != null) {
      final provider = context.watch<EventManagementProvider>();
      if (provider.isDetailLoading) {
        return Scaffold(
          appBar: CustomAppBar(title: 'Edit Event(Online)'),
          body: const Center(child: CircularProgressIndicator()),
        );
      }
      if (provider.detailError != null) {
        return Scaffold(
          appBar: CustomAppBar(title: 'Edit Event(Online)'),
          body: Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  provider.detailError!,
                  style: const TextStyle(color: Colors.red),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () => provider.fetchEventDetail(widget.eventId!),
                  child: const Text('Retry'),
                ),
              ],
            ),
          ),
        );
      }
    }

    final currencyText =
        context.read<EventManagementProvider>().currencyInfo?.text ?? '';

    return Scaffold(
      appBar: CustomAppBar(title: 'Edit Event(Online)'),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CustomHeaderTextWidget(text: 'Gallery Images *'),
            SizedBox(height: 8),
            if (galleryImages.isEmpty && _existingGalleryImages.isEmpty)
              GestureDetector(
                onTap: _pickGalleryImages,
                child: DottedBorder(
                  options: RoundedRectDottedBorderOptions(
                    color: Colors.grey,
                    dashPattern: [10, 5],
                    strokeWidth: 1,
                    radius: Radius.circular(10),
                  ),
                  child: SizedBox(
                    height: 160,
                    width: double.infinity,
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          FontAwesomeIcons.cloudArrowUp,
                          size: 50,
                          color: Colors.grey.shade600,
                        ),
                        SizedBox(height: 16),
                        Text(
                          'Tap to upload images',
                          style: AppTextStyles.bodyLargeGrey,
                        ),
                      ],
                    ),
                  ),
                ),
              )
            else if (galleryImages.isEmpty && _existingGalleryImages.isNotEmpty)
              Column(
                children: [
                  SizedBox(
                    height: 120,
                    child: ListView.builder(
                      scrollDirection: Axis.horizontal,
                      itemCount: _existingGalleryImages.length + 1,
                      itemBuilder: (context, index) {
                        if (index == _existingGalleryImages.length) {
                          return GestureDetector(
                            onTap: _pickGalleryImages,
                            child: Container(
                              width: 120,
                              margin: EdgeInsets.only(right: 8),
                              decoration: BoxDecoration(
                                border: Border.all(color: Colors.grey.shade300),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(
                                    Icons.add_photo_alternate,
                                    size: 40,
                                    color: Colors.grey.shade600,
                                  ),
                                  SizedBox(height: 8),
                                  Text(
                                    'Add More',
                                    style: TextStyle(
                                      color: Colors.grey.shade600,
                                      fontSize: 12,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
                        }
                        return Container(
                          width: 120,
                          margin: EdgeInsets.only(right: 8),
                          child: Stack(
                            children: [
                              ClipRRect(
                                borderRadius: BorderRadius.circular(8),
                                child: Image.network(
                                  _existingGalleryImages[index].image,
                                  width: 120,
                                  height: 120,
                                  fit: BoxFit.cover,
                                  errorBuilder: (_, _, _) => Container(
                                    width: 120,
                                    height: 120,
                                    color: Colors.grey.shade200,
                                    child: Icon(
                                      Icons.broken_image,
                                      color: Colors.grey,
                                    ),
                                  ),
                                ),
                              ),
                              Positioned(
                                top: 4,
                                right: 4,
                                child: GestureDetector(
                                  onTap: () => _deleteEventImage(
                                    _existingGalleryImages[index].id,
                                    index,
                                  ),
                                  child: Container(
                                    padding: EdgeInsets.all(4),
                                    decoration: BoxDecoration(
                                      color: Colors.red,
                                      shape: BoxShape.circle,
                                    ),
                                    child: Icon(
                                      Icons.close,
                                      color: Colors.white,
                                      size: 16,
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                  ),
                ],
              )
            else
              Column(
                children: [
                  SizedBox(
                    height: 120,
                    child: ListView.builder(
                      scrollDirection: Axis.horizontal,
                      itemCount:
                          _existingGalleryImages.length +
                          galleryImages.length +
                          1,
                      itemBuilder: (context, index) {
                        // Show "Add More" button at the end
                        if (index ==
                            _existingGalleryImages.length +
                                galleryImages.length) {
                          return GestureDetector(
                            onTap: _pickGalleryImages,
                            child: Container(
                              width: 120,
                              margin: EdgeInsets.only(right: 8),
                              decoration: BoxDecoration(
                                border: Border.all(color: Colors.grey.shade300),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(
                                    Icons.add_photo_alternate,
                                    size: 40,
                                    color: Colors.grey.shade600,
                                  ),
                                  SizedBox(height: 8),
                                  Text(
                                    'Add More',
                                    style: TextStyle(
                                      color: Colors.grey.shade600,
                                      fontSize: 12,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
                        }

                        // Show existing images first
                        if (index < _existingGalleryImages.length) {
                          return Container(
                            width: 120,
                            margin: EdgeInsets.only(right: 8),
                            child: Stack(
                              children: [
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(8),
                                  child: Image.network(
                                    _existingGalleryImages[index].image,
                                    width: 120,
                                    height: 120,
                                    fit: BoxFit.cover,
                                    errorBuilder: (_, _, _) => Container(
                                      width: 120,
                                      height: 120,
                                      color: Colors.grey.shade200,
                                      child: Icon(
                                        Icons.broken_image,
                                        color: Colors.grey,
                                      ),
                                    ),
                                  ),
                                ),
                                Positioned(
                                  top: 4,
                                  right: 4,
                                  child: GestureDetector(
                                    onTap: () => _deleteEventImage(
                                      _existingGalleryImages[index].id,
                                      index,
                                    ),
                                    child: Container(
                                      padding: EdgeInsets.all(4),
                                      decoration: BoxDecoration(
                                        color: Colors.red,
                                        shape: BoxShape.circle,
                                      ),
                                      child: Icon(
                                        Icons.close,
                                        color: Colors.white,
                                        size: 16,
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          );
                        }

                        // Show new images after existing ones
                        final newImageIndex =
                            index - _existingGalleryImages.length;
                        return Container(
                          width: 120,
                          margin: EdgeInsets.only(right: 8),
                          child: Stack(
                            children: [
                              ClipRRect(
                                borderRadius: BorderRadius.circular(8),
                                child: Image.file(
                                  File(galleryImages[newImageIndex].path),
                                  width: 120,
                                  height: 120,
                                  fit: BoxFit.cover,
                                ),
                              ),
                              Positioned(
                                top: 4,
                                right: 4,
                                child: GestureDetector(
                                  onTap: () =>
                                      _removeGalleryImage(newImageIndex),
                                  child: Container(
                                    padding: EdgeInsets.all(4),
                                    decoration: BoxDecoration(
                                      color: Colors.red,
                                      shape: BoxShape.circle,
                                    ),
                                    child: Icon(
                                      Icons.close,
                                      color: Colors.white,
                                      size: 16,
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                  ),
                ],
              ),
            SizedBox(height: 6),
            Text(
              'Note: Image Size 1170x570',
              style: AppTextStyles.bodySmall.copyWith(color: Colors.amber),
            ),

            SizedBox(height: 16),
            CustomHeaderTextWidget(text: 'Thumbnail Images *'),
            SizedBox(height: 8),
            GestureDetector(
              onTap: thumbnailImage == null ? _pickThumbnailImage : null,
              child: DottedBorder(
                options: RoundedRectDottedBorderOptions(
                  color: Colors.grey,
                  dashPattern: [10, 5],
                  strokeWidth: 1,
                  radius: Radius.circular(10),
                ),
                child: SizedBox(
                  height: 160,
                  width: double.infinity,
                  child: thumbnailImage != null
                      ? Stack(
                          children: [
                            Center(
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(8),
                                child: Image.file(
                                  File(thumbnailImage!.path),
                                  height: 150,
                                  fit: BoxFit.cover,
                                ),
                              ),
                            ),
                            Positioned(
                              top: 4,
                              right: 4,
                              child: GestureDetector(
                                onTap: _removeThumbnailImage,
                                child: Container(
                                  padding: EdgeInsets.all(4),
                                  decoration: BoxDecoration(
                                    color: Colors.red,
                                    shape: BoxShape.circle,
                                  ),
                                  child: Icon(
                                    Icons.close,
                                    color: Colors.white,
                                    size: 16,
                                  ),
                                ),
                              ),
                            ),
                          ],
                        )
                      : _existingThumbnailUrl != null &&
                            _existingThumbnailUrl!.isNotEmpty
                      ? Stack(
                          children: [
                            Center(
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(8),
                                child: Image.network(
                                  _existingThumbnailUrl!,
                                  height: 150,
                                  fit: BoxFit.cover,
                                  errorBuilder: (_, _, _) => Icon(
                                    FontAwesomeIcons.cameraRetro,
                                    size: 50,
                                    color: Colors.grey.shade600,
                                  ),
                                ),
                              ),
                            ),
                            Positioned(
                              top: 4,
                              right: 4,
                              child: GestureDetector(
                                onTap: () => setState(
                                  () => _existingThumbnailUrl = null,
                                ),
                                child: Container(
                                  padding: EdgeInsets.all(4),
                                  decoration: BoxDecoration(
                                    color: Colors.red,
                                    shape: BoxShape.circle,
                                  ),
                                  child: Icon(
                                    Icons.close,
                                    color: Colors.white,
                                    size: 16,
                                  ),
                                ),
                              ),
                            ),
                          ],
                        )
                      : Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              FontAwesomeIcons.cameraRetro,
                              size: 50,
                              color: Colors.grey.shade600,
                            ),
                            SizedBox(height: 16),
                            Text(
                              'Tap to upload thumbnail',
                              style: AppTextStyles.bodyLargeGrey,
                            ),
                          ],
                        ),
                ),
              ),
            ),
            SizedBox(height: 16),
            CustomHeaderTextWidget(text: 'Date Type*'),
            SizedBox(height: 8),
            SizedBox(
              height: 52,
              child: CustomToggleButton(
                labels: ['Single', 'Multiple'],
                selectedIndex: _isSingle ? 0 : 1,
                onChanged: (index) {
                  setState(() {
                    _isSingle = index == 0;
                  });
                },
              ),
            ),

            if (_isSingle) ...[
              SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        CustomHeaderTextWidget(text: 'Start Date*'),
                        SizedBox(height: 8),
                        TextField(
                          readOnly: true,
                          controller: TextEditingController(
                            text: startDate != null
                                ? DateFormat('MM/dd/yyyy').format(startDate!)
                                : '',
                          ),
                          decoration: InputDecoration(
                            hintText: 'mm/dd/yyyy',
                            suffixIcon: Icon(Icons.calendar_month),
                          ),
                          onTap: () => _selectDate(context, 'start'),
                        ),
                      ],
                    ),
                  ),
                  SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,

                      children: [
                        CustomHeaderTextWidget(text: 'Start Time*'),
                        SizedBox(height: 8),
                        TextField(
                          readOnly: true,
                          controller: TextEditingController(
                            text: startTime != null
                                ? startTime!.format(context)
                                : '',
                          ),
                          decoration: InputDecoration(
                            hintText: '--:--:--',
                            suffixIcon: Icon(Icons.access_time),
                          ),
                          onTap: () => _selectTime(context, 'start'),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        CustomHeaderTextWidget(text: 'End Date*'),
                        SizedBox(height: 8),
                        TextField(
                          readOnly: true,
                          controller: TextEditingController(
                            text: endDate != null
                                ? DateFormat('MM/dd/yyyy').format(endDate!)
                                : '',
                          ),
                          decoration: InputDecoration(
                            hintText: 'mm/dd/yyyy',
                            suffixIcon: Icon(Icons.calendar_month),
                          ),
                          onTap: () => _selectDate(context, 'end'),
                        ),
                      ],
                    ),
                  ),
                  SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        CustomHeaderTextWidget(text: 'End Time*'),
                        SizedBox(height: 8),
                        TextField(
                          readOnly: true,
                          controller: TextEditingController(
                            text: endTime != null
                                ? endTime!.format(context)
                                : '',
                          ),
                          decoration: InputDecoration(
                            hintText: '--:--:--',
                            suffixIcon: Icon(Icons.access_time),
                          ),
                          onTap: () => _selectTime(context, 'end'),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ],
            if (!_isSingle) ...[
              SizedBox(height: 16),
              ...List.generate(dateTimeSlots.length, (index) {
                final slot = dateTimeSlots[index];
                return Padding(
                  padding: const EdgeInsets.only(bottom: 16.0),
                  child: Card(
                    elevation: 2,
                    child: Padding(
                      padding: const EdgeInsets.all(12.0),
                      child: Column(
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'Date & Time #${index + 1}',
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 16,
                                ),
                              ),
                              if (dateTimeSlots.length > 1)
                                IconButton(
                                  onPressed: () {
                                    // If slot has ID, delete from API; otherwise just remove locally
                                    if (slot.id != null) {
                                      _deleteEventDate(slot.id!, index);
                                    } else {
                                      _removeDateTimeSlot(index);
                                    }
                                  },
                                  icon: Icon(Icons.close, color: Colors.red),
                                  padding: EdgeInsets.zero,
                                  constraints: BoxConstraints(),
                                ),
                            ],
                          ),
                          SizedBox(height: 8),
                          Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    CustomHeaderTextWidget(text: 'Start Date*'),
                                    SizedBox(height: 8),
                                    TextField(
                                      readOnly: true,
                                      controller: TextEditingController(
                                        text: slot.startDate != null
                                            ? DateFormat(
                                                'MM/dd/yyyy',
                                              ).format(slot.startDate!)
                                            : '',
                                      ),
                                      decoration: InputDecoration(
                                        hintText: 'mm/dd/yyyy',
                                        suffixIcon: Icon(Icons.calendar_month),
                                      ),
                                      onTap: () =>
                                          _selectDate(context, 'start', index),
                                    ),
                                  ],
                                ),
                              ),
                              SizedBox(width: 8),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    CustomHeaderTextWidget(text: 'Start Time*'),
                                    SizedBox(height: 8),
                                    TextField(
                                      readOnly: true,
                                      controller: TextEditingController(
                                        text: slot.startTime != null
                                            ? slot.startTime!.format(context)
                                            : '',
                                      ),
                                      decoration: InputDecoration(
                                        hintText: '--:--:--',
                                        suffixIcon: Icon(Icons.access_time),
                                      ),
                                      onTap: () =>
                                          _selectTime(context, 'start', index),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                          SizedBox(height: 16),
                          Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    CustomHeaderTextWidget(text: 'End Date*'),
                                    SizedBox(height: 8),
                                    TextField(
                                      readOnly: true,
                                      controller: TextEditingController(
                                        text: slot.endDate != null
                                            ? DateFormat(
                                                'MM/dd/yyyy',
                                              ).format(slot.endDate!)
                                            : '',
                                      ),
                                      decoration: InputDecoration(
                                        hintText: 'mm/dd/yyyy',
                                        suffixIcon: Icon(Icons.calendar_month),
                                      ),
                                      onTap: () =>
                                          _selectDate(context, 'end', index),
                                    ),
                                  ],
                                ),
                              ),
                              SizedBox(width: 8),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    CustomHeaderTextWidget(text: 'End Time*'),
                                    SizedBox(height: 8),
                                    TextField(
                                      readOnly: true,
                                      controller: TextEditingController(
                                        text: slot.endTime != null
                                            ? slot.endTime!.format(context)
                                            : '',
                                      ),
                                      decoration: InputDecoration(
                                        hintText: '--:--:--',
                                        suffixIcon: Icon(Icons.access_time),
                                      ),
                                      onTap: () =>
                                          _selectTime(context, 'end', index),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              }),
              ElevatedButton.icon(
                onPressed: _addDateTimeSlot,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green,
                  padding: EdgeInsets.symmetric(vertical: 12),
                ),
                iconAlignment: IconAlignment.end,
                icon: Icon(Icons.add_circle_outline_sharp, size: 20),
                label: Text('Add Another Date'),
              ),
            ],
            if (_isSingle) ...[
              SizedBox(height: 16),
              CustomHeaderTextWidget(text: 'Countdown Status*'),
              SizedBox(height: 8),
              SizedBox(
                height: 52,
                child: CustomToggleButton(
                  labels: ['Activate', 'Deactivate'],
                  selectedIndex: _countdown ? 0 : 1,
                  onChanged: (index) {
                    setState(() {
                      _countdown = index == 0;
                    });
                  },
                ),
              ),
            ],
            SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      CustomHeaderTextWidget(text: 'Status*'),
                      SizedBox(height: 8),
                      Container(
                        height: 56,
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          border: Border.all(color: Colors.grey.shade300),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<String>(
                            value: selectedValue,
                            hint: Text('Select Status'),
                            borderRadius: BorderRadius.circular(12),
                            dropdownColor: theme.dialogTheme.backgroundColor,
                            items: status.map((item) {
                              return DropdownMenuItem<String>(
                                value: item,
                                child: Text(item),
                              );
                            }).toList(),
                            onChanged: (value) =>
                                setState(() => selectedValue = value),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      CustomHeaderTextWidget(text: 'Is Feature**'),
                      SizedBox(height: 8),
                      Container(
                        height: 56,
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          border: Border.all(color: Colors.grey.shade300),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<String>(
                            value: selectedValue,
                            hint: Text('Select'),
                            borderRadius: BorderRadius.circular(12),
                            dropdownColor: theme.dialogTheme.backgroundColor,
                            items: status.map((item) {
                              return DropdownMenuItem<String>(
                                value: item,
                                child: Text(item),
                              );
                            }).toList(),
                            onChanged: (value) =>
                                setState(() => selectedValue = value),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            SizedBox(height: 16),
            CustomHeaderTextWidget(text: 'Total Number of Available Tickets*'),
            SizedBox(height: 8),
            SizedBox(
              height: 52,
              child: CustomToggleButton(
                labels: ['Unlimited', 'Limited'],
                selectedIndex: _isLimitedTicket ? 0 : 1,
                onChanged: (index) {
                  setState(() {
                    _isLimitedTicket = index == 0;
                  });
                },
              ),
            ),

            if (!_isLimitedTicket) ...[
              SizedBox(height: 16),
              CustomHeaderTextWidget(text: 'Enter total number*'),
              SizedBox(height: 8),
              TextField(
                decoration: InputDecoration(
                  hintText: 'Enter total number of available Tickets',
                ),
              ),
            ],
            SizedBox(height: 16),
            CustomHeaderTextWidget(
              text: 'Maximum number of Tickets for each customer*',
            ),
            SizedBox(height: 8),
            SizedBox(
              height: 52,
              child: CustomToggleButton(
                labels: ['Unlimited', 'Limited'],
                selectedIndex: _isLimitedCustomer ? 0 : 1,
                onChanged: (index) {
                  setState(() {
                    _isLimitedCustomer = index == 0;
                  });
                },
              ),
            ),

            if (!_isLimitedCustomer) ...[
              SizedBox(height: 16),
              CustomHeaderTextWidget(text: 'Enter The Number*'),
              SizedBox(height: 8),
              TextField(
                controller: _maxTicketsController,
                decoration: InputDecoration(
                  hintText: 'Enter Maximum number of tickets for each customer',
                ),
              ),
            ],
            SizedBox(height: 16),
            CustomHeaderTextWidget(
              text: currencyText.isEmpty ? 'Price *' : 'Price ($currencyText) *',
            ),
            SizedBox(height: 8),
            TextField(
              controller: _priceController,
              decoration: InputDecoration(hintText: 'Enter Ticket Price'),
            ),
            SizedBox(height: 16),
            CustomHeaderTextWidget(text: 'Meeting Url *'),
            SizedBox(height: 8),
            TextField(
              controller: _meetingUrlController,
              decoration: InputDecoration(hintText: 'Enter Meeting Url'),
            ),
            SizedBox(height: 16),
            CustomHeaderTextWidget(text: 'Early Bird Discount*'),
            SizedBox(height: 8),
            SizedBox(
              height: 52,
              child: CustomToggleButton(
                labels: ['Enable', 'Disabled'],
                selectedIndex: _earlyBird ? 0 : 1,
                onChanged: (index) {
                  setState(() {
                    _earlyBird = index == 0;
                  });
                },
              ),
            ),
            if (_earlyBird) ...[
              SizedBox(height: 16),
              CustomHeaderTextWidget(text: 'Discount *'),
              SizedBox(height: 8),
              Container(
                height: 56,
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 12),
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.grey.shade300),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<String>(
                    value: selectedDiscountValue,
                    hint: Text('Select Discount Type'),
                    borderRadius: BorderRadius.circular(12),
                    dropdownColor: theme.dialogTheme.backgroundColor,
                    items: discount.map((item) {
                      return DropdownMenuItem<String>(
                        value: item,
                        child: Text(item),
                      );
                    }).toList(),
                    onChanged: (value) =>
                        setState(() => selectedDiscountValue = value),
                  ),
                ),
              ),
              SizedBox(height: 16),
              CustomHeaderTextWidget(text: 'Amount *'),
              SizedBox(height: 8),
              TextField(
                controller: _discountAmountController,
                decoration: InputDecoration(hintText: 'Enter Amount'),
              ),
              SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        CustomHeaderTextWidget(text: 'Discount End Date *'),
                        SizedBox(height: 8),
                        TextField(
                          readOnly: true,
                          controller: TextEditingController(
                            text: discountEndDate != null
                                ? DateFormat(
                                    'MM/dd/yyyy',
                                  ).format(discountEndDate!)
                                : '',
                          ),
                          decoration: InputDecoration(
                            hintText: 'mm/dd/yyyy',
                            suffixIcon: Icon(Icons.calendar_month),
                          ),
                          onTap: () => _selectDate(context, 'discount'),
                        ),
                      ],
                    ),
                  ),
                  SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        CustomHeaderTextWidget(text: 'Discount End Time*'),
                        SizedBox(height: 8),
                        TextField(
                          readOnly: true,
                          controller: TextEditingController(
                            text: discountEndTime != null
                                ? discountEndTime!.format(context)
                                : '',
                          ),
                          decoration: InputDecoration(
                            hintText: '--:--:--',
                            suffixIcon: Icon(Icons.access_time),
                          ),
                          onTap: () => _selectTime(context, 'discount'),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ],
            SizedBox(height: 16),
            Container(
              padding: EdgeInsets.all(16),
              width: double.infinity,
              decoration: BoxDecoration(
                color: isDark
                    ? Colors.blue.withValues(alpha: 0.07)
                    : Colors.blue.shade50,
                border: Border.all(
                  color: isDark ? Colors.blue.shade900 : Colors.blue.shade200,
                ),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(
                        Icons.translate_rounded,
                        size: 15,
                        color: isDark
                            ? Colors.blue.shade300
                            : Colors.blue.shade700,
                      ),
                      const SizedBox(width: 6),
                      Text(
                        'Translatable Content',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: isDark
                              ? Colors.blue.shade300
                              : Colors.blue.shade700,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  CustomHeaderTextWidget(
                    text:
                        'Select Language',
                  ),
                  SizedBox(height: 8),
                  Container(
                    height: 56,
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey.shade300),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<String>(
                        value: selectedLanguageValue,
                        hint: Text('Select Language'),
                        borderRadius: BorderRadius.circular(12),
                        dropdownColor: theme.dialogTheme.backgroundColor,
                        items: languages.map((item) {
                          return DropdownMenuItem<String>(
                            value: item,
                            child: Text(
                              item == languages.first ? '$item(Default)' : item,
                            ),
                          );
                        }).toList(),
                        onChanged: (value) => _onLanguageChanged(value!),
                      ),
                    ),
                  ),
                  SizedBox(height: 16),
                  Directionality(
                    textDirection: _isRtl
                        ? ui.TextDirection.rtl
                        : ui.TextDirection.ltr,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        CustomHeaderTextWidget(text: 'Event Title*'),
                        SizedBox(height: 8),
                        TextField(
                          controller: _titleController,
                          decoration: InputDecoration(
                            hintText: 'Enter Event Title',
                          ),
                        ),
                        SizedBox(height: 16),
                        CustomHeaderTextWidget(text: 'Category*'),
                        SizedBox(height: 8),
                        Consumer<CategoriesProvider>(
                          builder: (context, catProvider, _) {
                            final cats = catProvider.categories;
                            final validValue =
                                cats.any(
                                  (c) =>
                                      c.id.toString() == selectedCategoryValue,
                                )
                                ? selectedCategoryValue
                                : null;
                            return Container(
                              height: 56,
                              width: double.infinity,
                              padding: const EdgeInsets.symmetric(
                                horizontal: 12,
                              ),
                              decoration: BoxDecoration(
                                border: Border.all(color: Colors.grey.shade300),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: catProvider.isLoading
                                  ? const Center(
                                      child: SizedBox(
                                        height: 20,
                                        width: 20,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                        ),
                                      ),
                                    )
                                  : DropdownButtonHideUnderline(
                                      child: DropdownButton<String>(
                                        value: validValue,
                                        hint: const Text('Select Category'),
                                        borderRadius: BorderRadius.circular(12),
                                        dropdownColor:
                                            theme.dialogTheme.backgroundColor,
                                        items: cats.map((cat) {
                                          return DropdownMenuItem<String>(
                                            value: cat.id.toString(),
                                            child: Text(cat.name),
                                          );
                                        }).toList(),
                                        onChanged: (value) => setState(
                                          () => selectedCategoryValue = value,
                                        ),
                                      ),
                                    ),
                            );
                          },
                        ),
                        SizedBox(height: 16),
                        CustomHeaderTextWidget(text: 'Description*'),
                        SizedBox(height: 8),
                        Container(
                          height: 300,
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            border: Border.all(color: Colors.grey.shade300),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Column(
                            children: [
                              QuillSimpleToolbar(
                                controller: _descriptionController,
                                config: QuillSimpleToolbarConfig(
                                  showAlignmentButtons: false,
                                  showBackgroundColorButton: false,
                                  showClearFormat: false,
                                  showCodeBlock: false,
                                  showDirection: false,
                                  showFontFamily: false,
                                  showFontSize: false,
                                  showHeaderStyle: true,
                                  showIndent: false,
                                  showInlineCode: false,
                                  showLink: false,
                                  showQuote: false,
                                  showSearchButton: false,
                                  showStrikeThrough: false,
                                  showSubscript: false,
                                  showSuperscript: false,
                                  multiRowsDisplay: false,
                                ),
                              ),
                              Divider(height: 1),
                              Expanded(
                                child: QuillEditor.basic(
                                  controller: _descriptionController,
                                  config: QuillEditorConfig(
                                    placeholder: 'Enter Description',
                                    padding: const EdgeInsets.all(8),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        SizedBox(height: 16),
                        CustomHeaderTextWidget(text: 'Refund Policy *'),
                        SizedBox(height: 8),
                        TextField(
                          controller: _refundPolicyController,
                          decoration: InputDecoration(
                            hintText: 'Enter Refund Policy',
                          ),
                          maxLines: 5,
                        ),
                        SizedBox(height: 16),
                        CustomHeaderTextWidget(text: 'Meta Keywords'),
                        SizedBox(height: 8),
                        TextField(
                          controller: _metaKeywordsController,
                          decoration: InputDecoration(
                            hintText: 'Enter Meta Keywords',
                          ),
                        ),
                        SizedBox(height: 16),
                        CustomHeaderTextWidget(text: 'Meta Description'),
                        SizedBox(height: 8),
                        TextField(
                          controller: _metaDescriptionController,
                          decoration: InputDecoration(
                            hintText: 'Enter Meta Description',
                          ),
                          maxLines: 5,
                        ),
                        SizedBox(height: 4),

                        CustomCheckbox(
                          value: _cloneLanguage,
                          label:
                              'Clone for ${selectedLanguageValue == 'English' ? 'Arabic' : 'English'} Language',
                          onChanged: (value) {
                            setState(() {
                              _cloneLanguage = value;
                            });
                          },
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: SafeArea(
        child: Container(
          decoration: BoxDecoration(
            color: isDark ? Colors.black : Colors.white,
            boxShadow: [
              BoxShadow(
                color: isDark ? Colors.grey.shade900 : Colors.grey.shade200,
                blurRadius: 10,
                offset: Offset(0, -5),
              ),
            ],
          ),
          padding: EdgeInsets.fromLTRB(12, 12, 12, 0),
          child: Consumer<AddEventProvider>(
            builder: (context, provider, _) {
              return ElevatedButton(
                onPressed: provider.isCreating
                    ? null
                    : () => _updateEvent(context),
                child: provider.isCreating
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Text('Save'),
              );
            },
          ),
        ),
      ),
    );
  }
}
