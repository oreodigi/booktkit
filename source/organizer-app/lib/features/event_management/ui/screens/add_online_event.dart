import 'dart:io';
import 'dart:ui' as ui;
import 'package:dotted_border/dotted_border.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_checkbox.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_header_text_widget.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_toggle_button.dart';
import 'package:booktkit_organizer/features/event_management/data/models/store_event_request.dart';
import 'package:booktkit_organizer/features/event_management/providers/add_event_provider.dart';
import 'package:booktkit_organizer/features/event_management/providers/categories_provider.dart';
import 'package:booktkit_organizer/features/nav_appbar/ui/widgets/app_text_styles.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

class AddOnlineEvent extends StatefulWidget {
  const AddOnlineEvent({super.key});

  @override
  State<AddOnlineEvent> createState() => _AddOnlineEventState();
}

class DateTimeSlot {
  DateTime? startDate;
  TimeOfDay? startTime;
  DateTime? endDate;
  TimeOfDay? endTime;

  DateTimeSlot({this.startDate, this.startTime, this.endDate, this.endTime});
}

final List<String> status = ['Active', 'Inactive'];
final List<String> featuredStatus = ['Yes', 'No'];
final List<String> discount = ['Fixed', 'Percentage'];

String? selectedValue;
String? selectedFeaturedValue;
String? selectedDiscountValue;
bool _isLimitedTicket = true;
bool _isLimitedCustomer = true;
bool _isSingle = true;
bool _countdown = true;
bool _earlyBird = true;
bool _cloneLanguage = true;

class _AddOnlineEventState extends State<AddOnlineEvent> {
  DateTime? startDate;
  DateTime? endDate;
  DateTime? discountEndDate;
  TimeOfDay? startTime;
  TimeOfDay? endTime;
  TimeOfDay? discountEndTime;
  List<XFile> galleryImages = [];
  XFile? thumbnailImage;
  final ImagePicker _picker = ImagePicker();

  // Form Controllers
  final TextEditingController _titleController = TextEditingController();
  final TextEditingController _descriptionController = TextEditingController();
  final TextEditingController _refundPolicyController = TextEditingController();
  final TextEditingController _metaKeywordsController = TextEditingController();
  final TextEditingController _metaDescriptionController =
      TextEditingController();
  final TextEditingController _priceController = TextEditingController();
  final TextEditingController _discountAmountController =
      TextEditingController();
  final TextEditingController _totalTicketsController = TextEditingController();
  final TextEditingController _maxTicketsController = TextEditingController();
  final TextEditingController _meetingUrlController = TextEditingController();

  // List to store multiple date/time slots
  List<DateTimeSlot> dateTimeSlots = [DateTimeSlot()];

  @override
  void dispose() {
    _titleController.dispose();
    _descriptionController.dispose();
    _refundPolicyController.dispose();
    _metaKeywordsController.dispose();
    _metaDescriptionController.dispose();
    _priceController.dispose();
    _discountAmountController.dispose();
    _totalTicketsController.dispose();
    _maxTicketsController.dispose();
    _meetingUrlController.dispose();
    super.dispose();
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final provider = context.read<AddEventProvider>();
      provider.reset();
      provider.fetchInitData().then((_) {
        // Fetch categories for the default language
        if (mounted) {
          context.read<CategoriesProvider>().fetchCategories(
            provider.defaultLangId,
          );
        }
      });
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

  Future<void> _saveEvent(BuildContext context) async {
    final provider = context.read<AddEventProvider>();
    final languages = provider.languages;

    // Basic validation
    if (_titleController.text.trim().isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Please enter event title')));
      return;
    }

    if (provider.selectedCategoryId == null) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Please select a category')));
      return;
    }

    if (_meetingUrlController.text.trim().isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Please enter meeting URL')));
      return;
    }

    if (galleryImages.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please add at least one slider image')),
      );
      return;
    }

    if (thumbnailImage == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please add a thumbnail image')),
      );
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

    // Build language-specific maps
    final Map<String, String> titles = {};
    final Map<String, String> categoryIds = {};
    final Map<String, String> descriptions = {};
    final Map<String, String> refundPolicies = {};
    final Map<String, String> metaKeywords = {};
    final Map<String, String> metaDescriptions = {};

    if (_cloneLanguage) {
      // Clone for all languages
      for (final lang in languages) {
        titles[lang.code] = _titleController.text.trim();
        categoryIds[lang.code] = provider.selectedCategoryId ?? '';
        descriptions[lang.code] = _descriptionController.text.trim();
        refundPolicies[lang.code] = _refundPolicyController.text.trim();
        metaKeywords[lang.code] = _metaKeywordsController.text.trim();
        metaDescriptions[lang.code] = _metaDescriptionController.text.trim();
      }
    } else {
      // Only for selected language
      final selectedLang = languages.firstWhere(
        (l) => l.id.toString() == provider.selectedLanguageId,
        orElse: () => languages.first,
      );
      titles[selectedLang.code] = _titleController.text.trim();
      categoryIds[selectedLang.code] = provider.selectedCategoryId ?? '';
      descriptions[selectedLang.code] = _descriptionController.text.trim();
      refundPolicies[selectedLang.code] = _refundPolicyController.text.trim();
      metaKeywords[selectedLang.code] = _metaKeywordsController.text.trim();
      metaDescriptions[selectedLang.code] = _metaDescriptionController.text
          .trim();
    }

    // Prepare date/time data
    String? singleStartDate, singleStartTime, singleEndDate, singleEndTime;
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
      multipleStartDates = dateTimeSlots
          .map((slot) => DateFormat('yyyy-MM-dd').format(slot.startDate!))
          .toList();
      multipleStartTimes = dateTimeSlots
          .map(
            (slot) =>
                '${slot.startTime!.hour.toString().padLeft(2, '0')}:${slot.startTime!.minute.toString().padLeft(2, '0')}',
          )
          .toList();
      multipleEndDates = dateTimeSlots
          .map((slot) => DateFormat('yyyy-MM-dd').format(slot.endDate!))
          .toList();
      multipleEndTimes = dateTimeSlots
          .map(
            (slot) =>
                '${slot.endTime!.hour.toString().padLeft(2, '0')}:${slot.endTime!.minute.toString().padLeft(2, '0')}',
          )
          .toList();
    }

    // Prepare early bird discount data
    String? earlyBirdDate, earlyBirdTime;
    if (!_earlyBird && discountEndDate != null && discountEndTime != null) {
      earlyBirdDate = DateFormat('yyyy-MM-dd').format(discountEndDate!);
      earlyBirdTime =
          '${discountEndTime!.hour.toString().padLeft(2, '0')}:${discountEndTime!.minute.toString().padLeft(2, '0')}';
    }

    // Create request
    final request = StoreEventRequest(
      eventType: 'online',
      dateType: _isSingle ? 'single' : 'multiple',
      countdownStatus: _isSingle ? (_countdown ? '1' : '0') : '0',
      status: selectedValue == 'Active' ? '1' : '0',
      isFeatured: selectedFeaturedValue == 'Yes' ? 'yes' : 'no',
      sliderImagePaths: galleryImages.map((img) => img.path).toList(),
      thumbnailPath: thumbnailImage!.path,
      meetingUrl: _meetingUrlController.text.trim(),
      price: _priceController.text.trim(),
      ticketAvailableType: _isLimitedTicket ? 'unlimited' : 'limited',
      ticketAvailable: !_isLimitedTicket
          ? _totalTicketsController.text.trim()
          : null,
      maxTicketBuyType: _isLimitedCustomer ? 'unlimited' : 'limited',
      maxBuyTicket: !_isLimitedCustomer
          ? _maxTicketsController.text.trim()
          : null,
      earlyBirdDiscountType: _earlyBird ? 'disable' : 'enable',
      discountType: !_earlyBird && selectedDiscountValue != null
          ? selectedDiscountValue!.toLowerCase()
          : null,
      earlyBirdDiscountAmount: !_earlyBird
          ? _discountAmountController.text.trim()
          : null,
      earlyBirdDiscountDate: earlyBirdDate,
      earlyBirdDiscountTime: earlyBirdTime,
      startDate: singleStartDate,
      startTime: singleStartTime,
      endDate: singleEndDate,
      endTime: singleEndTime,
      multipleStartDates: multipleStartDates,
      multipleStartTimes: multipleStartTimes,
      multipleEndDates: multipleEndDates,
      multipleEndTimes: multipleEndTimes,
      titles: titles,
      categoryIds: categoryIds,
      descriptions: descriptions,
      refundPolicies: refundPolicies,
      metaKeywords: metaKeywords,
      metaDescriptions: metaDescriptions,
      // Online events don't have location data
      countries: null,
      states: null,
      cities: null,
      addresses: null,
      zipCodes: {},
    );

    // Submit
    final success = await provider.createEvent(request);

    if (context.mounted) {
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Event created successfully!'),
            backgroundColor: Colors.green,
          ),
        );
        Navigator.of(context).pop(true);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(provider.createError ?? 'Failed to create event'),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Scaffold(
      appBar: const CustomAppBar(title: 'Add Event(Online)'),
      body: Consumer<AddEventProvider>(
        builder: (context, provider, _) {
          if (provider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          final languages = provider.languages;
          if (languages.isEmpty) {
            return const Center(child: Text('Failed to load init data.'));
          }

          final defaultLang = languages.firstWhere(
            (l) => l.isDefault,
            orElse: () => languages.first,
          );
          final selectedLang = languages.firstWhere(
            (l) => l.id.toString() == provider.selectedLanguageId,
            orElse: () => defaultLang,
          );
          final isRtlSelected = selectedLang.isRtl;

          return SingleChildScrollView(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CustomHeaderTextWidget(text: 'Gallery Images *'),
                SizedBox(height: 8),
                if (galleryImages.isEmpty)
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
                else
                  Column(
                    children: [
                      SizedBox(
                        height: 120,
                        child: ListView.builder(
                          scrollDirection: Axis.horizontal,
                          itemCount: galleryImages.length + 1,
                          itemBuilder: (context, index) {
                            if (index == galleryImages.length) {
                              return GestureDetector(
                                onTap: _pickGalleryImages,
                                child: Container(
                                  width: 120,
                                  margin: EdgeInsets.only(right: 8),
                                  decoration: BoxDecoration(
                                    border: Border.all(
                                      color: Colors.grey.shade300,
                                    ),
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
                                    child: Image.file(
                                      File(galleryImages[index].path),
                                      width: 120,
                                      height: 120,
                                      fit: BoxFit.cover,
                                    ),
                                  ),
                                  Positioned(
                                    top: 4,
                                    right: 4,
                                    child: GestureDetector(
                                      onTap: () => _removeGalleryImage(index),
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
                CustomHeaderTextWidget(text: 'Thumbnail Image *'),
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
                      child: thumbnailImage == null
                          ? Column(
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
                            )
                          : Stack(
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
                                    ? DateFormat(
                                        'MM/dd/yyyy',
                                      ).format(startDate!)
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
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
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
                                      onPressed: () =>
                                          _removeDateTimeSlot(index),
                                      icon: Icon(
                                        Icons.close,
                                        color: Colors.red,
                                      ),
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
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        CustomHeaderTextWidget(
                                          text: 'Start Date*',
                                        ),
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
                                            suffixIcon: Icon(
                                              Icons.calendar_month,
                                            ),
                                          ),
                                          onTap: () => _selectDate(
                                            context,
                                            'start',
                                            index,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  SizedBox(width: 8),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        CustomHeaderTextWidget(
                                          text: 'Start Time*',
                                        ),
                                        SizedBox(height: 8),
                                        TextField(
                                          readOnly: true,
                                          controller: TextEditingController(
                                            text: slot.startTime != null
                                                ? slot.startTime!.format(
                                                    context,
                                                  )
                                                : '',
                                          ),
                                          decoration: InputDecoration(
                                            hintText: '--:--:--',
                                            suffixIcon: Icon(Icons.access_time),
                                          ),
                                          onTap: () => _selectTime(
                                            context,
                                            'start',
                                            index,
                                          ),
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
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        CustomHeaderTextWidget(
                                          text: 'End Date*',
                                        ),
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
                                            suffixIcon: Icon(
                                              Icons.calendar_month,
                                            ),
                                          ),
                                          onTap: () => _selectDate(
                                            context,
                                            'end',
                                            index,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  SizedBox(width: 8),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        CustomHeaderTextWidget(
                                          text: 'End Time*',
                                        ),
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
                                          onTap: () => _selectTime(
                                            context,
                                            'end',
                                            index,
                                          ),
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
                                dropdownColor:
                                    theme.dialogTheme.backgroundColor,
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
                                value: selectedFeaturedValue,
                                hint: Text('Select'),
                                borderRadius: BorderRadius.circular(12),
                                dropdownColor:
                                    theme.dialogTheme.backgroundColor,
                                items: featuredStatus.map((item) {
                                  return DropdownMenuItem<String>(
                                    value: item,
                                    child: Text(item),
                                  );
                                }).toList(),
                                onChanged: (value) => setState(
                                  () => selectedFeaturedValue = value,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                SizedBox(height: 16),
                CustomHeaderTextWidget(
                  text: 'Total Number of Available Tickets*',
                ),
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
                    controller: _totalTicketsController,
                    keyboardType: TextInputType.number,
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
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      hintText:
                          'Enter Maximum number of tickets for each customer',
                    ),
                  ),
                ],
                SizedBox(height: 16),
                CustomHeaderTextWidget(
                  text: 'Price (${provider.currencyInfo?.text ?? ''}) *',
                ),
                SizedBox(height: 8),
                TextField(
                  controller: _priceController,
                  keyboardType: TextInputType.number,
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
                    labels: ['Disabled', 'Enable'],
                    selectedIndex: _earlyBird ? 0 : 1,
                    onChanged: (index) {
                      setState(() {
                        _earlyBird = index == 0;
                      });
                    },
                  ),
                ),
                if (!_earlyBird) ...[
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
                    keyboardType: TextInputType.number,
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
                  padding: const EdgeInsets.all(16),
                  width: double.infinity,
                  decoration: BoxDecoration(
                    color: isDark
                        ? Colors.blue.withValues(alpha: 0.07)
                        : Colors.blue.shade50,
                    border: Border.all(
                      color: isDark
                          ? Colors.blue.shade900
                          : Colors.blue.shade200,
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
                      const SizedBox(height: 8),
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
                            value: provider.selectedLanguageId,
                            hint: const Text('Select Language'),
                            borderRadius: BorderRadius.circular(12),
                            dropdownColor: theme.dialogTheme.backgroundColor,
                            items: languages.map((lang) {
                              return DropdownMenuItem<String>(
                                value: lang.id.toString(),
                                child: Text(
                                  lang.id == defaultLang.id
                                      ? '${lang.name} (Default)'
                                      : lang.name,
                                ),
                              );
                            }).toList(),
                            onChanged: (value) {
                              if (value != null) {
                                setState(() {
                                  provider.selectedLanguageId = value;
                                });
                                final id = int.tryParse(value);
                                if (id != null) {
                                  context
                                      .read<CategoriesProvider>()
                                      .fetchCategories(id);
                                }
                              }
                            },
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Directionality(
                        textDirection: isRtlSelected
                            ? ui.TextDirection.rtl
                            : ui.TextDirection.ltr,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            const CustomHeaderTextWidget(text: 'Event Title*'),
                            const SizedBox(height: 8),
                            TextField(
                              controller: _titleController,
                              decoration: const InputDecoration(
                                hintText: 'Enter Event Title',
                              ),
                            ),
                            const SizedBox(height: 16),
                            const CustomHeaderTextWidget(text: 'Category*'),
                            const SizedBox(height: 8),
                            Consumer<CategoriesProvider>(
                              builder: (context, catProvider, _) {
                                final cats = catProvider.categories;
                                final validValue =
                                    cats.any(
                                      (c) =>
                                          c.id.toString() ==
                                          provider.selectedCategoryId,
                                    )
                                    ? provider.selectedCategoryId
                                    : null;
                                return Container(
                                  height: 56,
                                  width: double.infinity,
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 12,
                                  ),
                                  decoration: BoxDecoration(
                                    border: Border.all(
                                      color: Colors.grey.shade300,
                                    ),
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
                                            borderRadius: BorderRadius.circular(
                                              12,
                                            ),
                                            dropdownColor: theme
                                                .dialogTheme
                                                .backgroundColor,
                                            items: cats.map((cat) {
                                              return DropdownMenuItem<String>(
                                                value: cat.id.toString(),
                                                child: Text(cat.name),
                                              );
                                            }).toList(),
                                            onChanged: (value) => setState(
                                              () =>
                                                  provider.selectedCategoryId =
                                                      value,
                                            ),
                                          ),
                                        ),
                                );
                              },
                            ),
                            const SizedBox(height: 16),
                            const CustomHeaderTextWidget(text: 'Description*'),
                            const SizedBox(height: 8),
                            TextField(
                              controller: _descriptionController,
                              decoration: const InputDecoration(
                                hintText: 'Enter Description',
                              ),
                              maxLines: 6,
                            ),
                            const SizedBox(height: 16),
                            const CustomHeaderTextWidget(
                              text: 'Refund Policy *',
                            ),
                            const SizedBox(height: 8),
                            TextField(
                              controller: _refundPolicyController,
                              decoration: const InputDecoration(
                                hintText: 'Enter Refund Policy',
                              ),
                              maxLines: 5,
                            ),
                            const SizedBox(height: 16),
                            const CustomHeaderTextWidget(text: 'Meta Keywords'),
                            const SizedBox(height: 8),
                            TextField(
                              controller: _metaKeywordsController,
                              decoration: const InputDecoration(
                                hintText: 'Enter Meta Keywords',
                              ),
                            ),
                            const SizedBox(height: 16),
                            const CustomHeaderTextWidget(
                              text: 'Meta Description',
                            ),
                            const SizedBox(height: 8),
                            TextField(
                              controller: _metaDescriptionController,
                              decoration: const InputDecoration(
                                hintText: 'Enter Meta Description',
                              ),
                              maxLines: 5,
                            ),
                            const SizedBox(height: 4),

                            CustomCheckbox(
                              value: _cloneLanguage,
                              label:
                                  'Clone for ${selectedLang.name == 'English' ? 'Arabic' : 'English'} Language',
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
          );
        },
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
                    : () => _saveEvent(context),
                child: provider.isCreating
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : Text('Save'),
              );
            },
          ),
        ),
      ),
    );
  }
}
