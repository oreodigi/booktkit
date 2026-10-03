import 'dart:ui' as ui;
import 'package:booktkit_organizer/features/common/providers/currency_provider.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_checkbox.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_header_text_widget.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_snackbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_toggle_button.dart';
import 'package:booktkit_organizer/features/event_management/data/models/edit_ticket_model.dart';
import 'package:booktkit_organizer/features/event_management/data/models/store_ticket_request.dart';
import 'package:booktkit_organizer/features/event_management/providers/event_tickets_provider.dart';
import 'package:booktkit_organizer/features/event_management/ui/screens/seat_mapping_screen.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

class AddTicketsScreen extends StatefulWidget {
  final int? priceType;
  final int? eventId;

  const AddTicketsScreen({super.key, this.priceType = 0, this.eventId});

  @override
  State<AddTicketsScreen> createState() => _AddTicketsScreenState();
}

final List<String> discount = ['Fixed', 'Percentage'];

class VariationCardData {
  bool isLimitedTickets;
  bool isMaxLimitedTickets;
  bool isSeatMappedForVariation;

  final Map<String, TextEditingController> nameControllers = {};
  final TextEditingController priceController;
  final TextEditingController availableTicketsController;
  final TextEditingController maxTicketsController;

  VariationCardData({
    this.isLimitedTickets = true,
    this.isMaxLimitedTickets = true,
    this.isSeatMappedForVariation = false,
    String? price,
    String? available,
    String? max,
  }) : priceController = TextEditingController(text: price ?? ''),
       availableTicketsController = TextEditingController(
         text: available ?? '',
       ),
       maxTicketsController = TextEditingController(text: max ?? '');

  /// Ensure a name controller exists for each language code.
  void ensureControllersForLanguages(List<String> codes) {
    for (final code in codes) {
      nameControllers.putIfAbsent(code, () => TextEditingController());
    }
  }

  TextEditingController nameCtrlForCode(String code) {
    return nameControllers.putIfAbsent(code, () => TextEditingController());
  }

  void dispose() {
    for (final c in nameControllers.values) {
      c.dispose();
    }
    priceController.dispose();
    availableTicketsController.dispose();
    maxTicketsController.dispose();
  }
}

class _AddTicketsScreenState extends State<AddTicketsScreen> {
  // UI State
  String? selectedDiscountValue;
  bool _earlyBirdDisabled = true; // true => Disabled, false => Enabled
  late int _priceTypeIndex = widget.priceType ?? 0;
  int _availableTicket = 0;
  int _seatMapping = 1;
  int _maxTicket = 0; // 0 Unlimited, 1 Limited

  // Language state — driven by the provider's language list
  String _selectedLangCode = 'en'; // code of the currently viewed language
  bool get _isRTL {
    final p = _prov;
    if (p == null) return false;
    return p.languages
            .where((l) => l.code == _selectedLangCode)
            .firstOrNull
            ?.isRtl ??
        false;
  }

  bool _isCheckedClone = true;
  EventTicketsProvider? _prov; // cached after first build

  // Dates / Times
  DateTime? startDate;
  DateTime? endDate;
  DateTime? discountEndDate;
  TimeOfDay? startTime;
  TimeOfDay? endTime;
  TimeOfDay? discountEndTime;

  // Form controllers — per-language maps for title/description
  final Map<String, TextEditingController> _titleCtrls = {};
  final Map<String, TextEditingController> _descCtrls = {};
  final _fixedPriceController = TextEditingController();
  final _availableTicketsController = TextEditingController();
  final _maxTicketsController = TextEditingController();
  final _discountAmountController = TextEditingController();

  // Variation cards
  final List<VariationCardData> variationCards = [VariationCardData()];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      final p = context.read<EventTicketsProvider>();
      _prov = p;
      if (widget.eventId != null) {
        p
            .fetchTicketLanguages(
              eventId: widget.eventId!,
              eventType:
                  'venue', // works for both venue/online — returns same language list
            )
            .then((_) {
              if (!mounted) return;
              _ensureControllersForLanguages(
                p.languages.map((l) => l.code).toList(),
              );
            });
      }
      // Seed defaults so submit works before language list loads
      _ensureControllersForLanguages(['en', 'ar']);
    });
  }

  /// Ensure a title + description controller exists for every language code.
  void _ensureControllersForLanguages(List<String> codes) {
    for (final code in codes) {
      _titleCtrls.putIfAbsent(code, () => TextEditingController());
      _descCtrls.putIfAbsent(code, () => TextEditingController());
    }
    // Also ensure variation cards have controllers for these codes
    for (final v in variationCards) {
      v.ensureControllersForLanguages(codes);
    }
    setState(() {});
  }

  @override
  void dispose() {
    for (final c in _titleCtrls.values) {
      c.dispose();
    }
    for (final c in _descCtrls.values) {
      c.dispose();
    }
    _fixedPriceController.dispose();
    _availableTicketsController.dispose();
    _maxTicketsController.dispose();
    _discountAmountController.dispose();

    for (final v in variationCards) {
      v.dispose();
    }
    super.dispose();
  }

  Future<void> _selectDate(BuildContext context, String dateType) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2030),
    );
    if (picked != null) {
      setState(() {
        if (dateType == 'start') {
          startDate = picked;
        } else if (dateType == 'end') {
          endDate = picked;
        } else if (dateType == 'discount') {
          discountEndDate = picked;
        }
      });
    }
  }

  Future<void> _selectTime(BuildContext context, String timeType) async {
    final TimeOfDay? picked = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.now(),
    );
    if (picked != null) {
      setState(() {
        if (timeType == 'start') {
          startTime = picked;
        } else if (timeType == 'end') {
          endTime = picked;
        } else if (timeType == 'discount') {
          discountEndTime = picked;
        }
      });
    }
  }

  /// Reads form state, delegates payload construction to [StoreTicketRequest],
  /// and submits via the provider.
  Future<void> _submitTicket() async {
    final eventId = widget.eventId;
    if (eventId == null) {
      CustomSnackBar.show(
        context: context,
        message: 'No event selected',
        type: SnackBarType.error,
      );
      return;
    }

    // Build Map<langCode, value> from all language controllers
    final titles = _titleCtrls.map(
      (code, ctrl) => MapEntry(code, ctrl.text.trim()),
    );
    final descriptions = _descCtrls.map(
      (code, ctrl) => MapEntry(code, ctrl.text.trim()),
    );

    // Read common values once
    final earlyBirdEnabled = !_earlyBirdDisabled;
    final discountDateStr = discountEndDate != null
        ? DateFormat('yyyy-MM-dd').format(discountEndDate!)
        : null;
    final discountTimeStr = discountEndTime != null
        ? '${discountEndTime!.hour.toString().padLeft(2, '0')}:${discountEndTime!.minute.toString().padLeft(2, '0')}'
        : null;
    final discountTypeStr = selectedDiscountValue?.toLowerCase() == 'fixed'
        ? 'fixed'
        : 'percentage';

    final StoreTicketRequest request;

    switch (_priceTypeIndex) {
      case 0: // Free
        request = StoreTicketRequest.free(
          eventId: eventId,
          isLimitedAvailable: _availableTicket == 1,
          ticketAvailable: int.tryParse(
            _availableTicketsController.text.trim(),
          ),
          isLimitedMax: _maxTicket == 1,
          maxBuyTicket: int.tryParse(_maxTicketsController.text.trim()),
          earlyBirdEnabled: earlyBirdEnabled,
          titles: titles,
          descriptions: descriptions,
        );
      case 1: // Variation
        // Build per-language variation name map
        final varNames = <String, List<String>>{};
        for (final code in _titleCtrls.keys) {
          varNames[code] = variationCards
              .map((v) => v.nameCtrlForCode(code).text.trim())
              .toList();
        }
        request = StoreTicketRequest.variation(
          eventId: eventId,
          isLimitedAvailable: _availableTicket == 1,
          ticketAvailable: int.tryParse(
            _availableTicketsController.text.trim(),
          ),
          isLimitedMax: _maxTicket == 1,
          maxBuyTicket: int.tryParse(_maxTicketsController.text.trim()),
          earlyBirdEnabled: earlyBirdEnabled,
          discountType: discountTypeStr,
          earlyBirdDiscountAmount: double.tryParse(
            _discountAmountController.text.trim(),
          ),
          earlyBirdDiscountDate: discountDateStr,
          earlyBirdDiscountTime: discountTimeStr,
          titles: titles,
          descriptions: descriptions,
          variationPrice: variationCards
              .map((v) => double.tryParse(v.priceController.text.trim()) ?? 0)
              .toList(),
          vTicketAvailableType: variationCards
              .map((v) => v.isLimitedTickets ? 'limited' : 'unlimited')
              .toList(),
          vTicketAvailable: variationCards
              .map(
                (v) => v.isLimitedTickets
                    ? int.tryParse(v.availableTicketsController.text.trim())
                    : null,
              )
              .toList(),
          vMaxTicketBuyType: variationCards
              .map((v) => v.isMaxLimitedTickets ? 'limited' : 'unlimited')
              .toList(),
          vMaxTicketBuy: variationCards
              .map(
                (v) => v.isMaxLimitedTickets
                    ? int.tryParse(v.maxTicketsController.text.trim())
                    : null,
              )
              .toList(),
          variationNames: varNames,
        );
      default: // Normal / Fixed
        request = StoreTicketRequest.normal(
          eventId: eventId,
          price: double.tryParse(_fixedPriceController.text.trim()) ?? 0,
          isLimitedAvailable: _availableTicket == 1,
          ticketAvailable: int.tryParse(
            _availableTicketsController.text.trim(),
          ),
          isLimitedMax: _maxTicket == 1,
          maxBuyTicket: int.tryParse(_maxTicketsController.text.trim()),
          earlyBirdEnabled: earlyBirdEnabled,
          discountType: discountTypeStr,
          earlyBirdDiscountAmount: double.tryParse(
            _discountAmountController.text.trim(),
          ),
          earlyBirdDiscountDate: discountDateStr,
          earlyBirdDiscountTime: discountTimeStr,
          titles: titles,
          descriptions: descriptions,
        );
    }

    final provider = context.read<EventTicketsProvider>();
    final success = await provider.storeEventTicket(request);
    if (!mounted) return;
    if (success) {
      CustomSnackBar.show(
        context: context,
        message: 'Ticket saved successfully!',
        type: SnackBarType.success,
      );
      Navigator.pop(context);
    } else {
      CustomSnackBar.show(
        context: context,
        message: provider.saveTicketError ?? 'Failed to save ticket',
        type: SnackBarType.error,
      );
    }
  }

  void _cloneLanguageIfNeeded() {
    if (!_isCheckedClone) return;
    // Copy current language's content to all other languages
    final srcTitle = _titleCtrls[_selectedLangCode]?.text ?? '';
    final srcDesc = _descCtrls[_selectedLangCode]?.text ?? '';
    for (final entry in _titleCtrls.entries) {
      if (entry.key != _selectedLangCode && entry.value.text.trim().isEmpty) {
        entry.value.text = srcTitle;
      }
    }
    for (final entry in _descCtrls.entries) {
      if (entry.key != _selectedLangCode && entry.value.text.trim().isEmpty) {
        entry.value.text = srcDesc;
      }
    }
    // Clone variation names too
    for (final v in variationCards) {
      final srcName = v.nameCtrlForCode(_selectedLangCode).text;
      for (final code in v.nameControllers.keys) {
        if (code != _selectedLangCode &&
            v.nameControllers[code]!.text.trim().isEmpty) {
          v.nameControllers[code]!.text = srcName;
        }
      }
    }
  }

  InputDecoration _fieldDecoration(
    BuildContext context, {
    required String hint,
    IconData? icon,
  }) {
    final theme = Theme.of(context);
    return InputDecoration(
      hintText: hint,
      prefixIcon: icon == null ? null : Icon(icon, size: 20),
      filled: true,
      fillColor: theme.colorScheme.surface,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide(color: theme.dividerColor),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide(color: theme.colorScheme.primary, width: 1.5),
      ),
    );
  }

  Widget _sectionCard({
    required Widget child,
    EdgeInsets padding = const EdgeInsets.all(14),
  }) {
    return Card(
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(padding: padding, child: child),
    );
  }

  Widget _pillInfo(String text, {IconData? icon}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: Colors.grey.shade300),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[Icon(icon, size: 16), const SizedBox(width: 6)],
          Flexible(
            child: Text(
              text,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );
  }

  Widget _dividerGap() => const SizedBox(height: 14);

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final currencyText = context.watch<CurrencyProvider>().text;

    return Scaffold(
      appBar: CustomAppBar(
        title: widget.priceType == null ? 'Add Ticket' : 'Edit Ticket',
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ===== Pricing Type =====
            _sectionCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const CustomHeaderTextWidget(text: 'Pricing Type*'),
                  const SizedBox(height: 10),
                  SizedBox(
                    height: 52,
                    child: CustomToggleButton(
                      labels: const ['Free', 'Variation', 'Fixed'],
                      selectedIndex: _priceTypeIndex,
                      onChanged: (index) =>
                          setState(() => _priceTypeIndex = index),
                    ),
                  ),
                  if (_priceTypeIndex == 2) ...[
                    _dividerGap(),
                    CustomHeaderTextWidget(text: 'Price ($currencyText) *'),
                    const SizedBox(height: 8),
                    TextField(
                      controller: _fixedPriceController,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                      decoration: _fieldDecoration(
                        context,
                        hint: 'Enter price',
                        icon: Icons.attach_money,
                      ),
                    ),
                  ],
                ],
              ),
            ),

            const SizedBox(height: 16),

            // ===== Variation Cards =====
            if (_priceTypeIndex == 1) ...[
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'Ticket Variations',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  _pillInfo(
                    '${variationCards.length} item(s)',
                    icon: Icons.confirmation_number_outlined,
                  ),
                ],
              ),
              const SizedBox(height: 10),

              ...variationCards.asMap().entries.map((entry) {
                final index = entry.key;
                final cardData = entry.value;

                return Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: _sectionCard(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Header row
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                'Variation ${index + 1}',
                                style: theme.textTheme.titleMedium?.copyWith(
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ),
                            if (variationCards.length > 1)
                              IconButton(
                                tooltip: 'Remove',
                                onPressed: () {
                                  setState(() {
                                    final removed = variationCards.removeAt(
                                      index,
                                    );

                                    removed.priceController.dispose();
                                    removed.availableTicketsController
                                        .dispose();
                                    removed.maxTicketsController.dispose();
                                  });
                                },
                                icon: const Icon(Icons.delete_outline),
                                color: theme.colorScheme.error,
                              ),
                          ],
                        ),
                        const Divider(height: 18),

                        // Names
                        if (_prov != null)
                          ..._prov!.languages.map((lang) {
                            return Padding(
                              padding: const EdgeInsets.only(bottom: 12),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  CustomHeaderTextWidget(
                                    text: 'Variation Name (${lang.name})*',
                                  ),
                                  const SizedBox(height: 8),
                                  TextFormField(
                                    controller: cardData.nameCtrlForCode(
                                      lang.code,
                                    ),
                                    decoration: _fieldDecoration(
                                      context,
                                      hint:
                                          'Enter variation name in ${lang.name}',
                                      icon: Icons.title,
                                    ),
                                    textDirection: lang.isRtl
                                        ? ui.TextDirection.rtl
                                        : ui.TextDirection.ltr,
                                    onChanged: (_) => _cloneLanguageIfNeeded(),
                                  ),
                                ],
                              ),
                            );
                          }),

                        _dividerGap(),

                        // Price
                        CustomHeaderTextWidget(text: 'Price ($currencyText)*'),
                        const SizedBox(height: 8),
                        TextFormField(
                          controller: cardData.priceController,
                          keyboardType: const TextInputType.numberWithOptions(
                            decimal: true,
                          ),
                          decoration: _fieldDecoration(
                            context,
                            hint: 'Enter price',
                            icon: Icons.attach_money,
                          ),
                        ),

                        _dividerGap(),

                        // Available tickets toggle + field
                        Row(
                          children: [
                            Expanded(
                              child: CustomHeaderTextWidget(
                                text:
                                    '(${cardData.isLimitedTickets ? 'Limited' : 'Unlimited'}) Available Tickets',
                              ),
                            ),
                            CustomCheckbox(
                              value: cardData.isLimitedTickets,
                              onChanged: (v) =>
                                  setState(() => cardData.isLimitedTickets = v),
                            ),
                          ],
                        ),
                        if (cardData.isLimitedTickets) ...[
                          const SizedBox(height: 8),
                          TextFormField(
                            controller: cardData.availableTicketsController,
                            keyboardType: TextInputType.number,
                            decoration: _fieldDecoration(
                              context,
                              hint: 'Enter available tickets',
                              icon: Icons.confirmation_number_outlined,
                            ),
                          ),
                        ],

                        _dividerGap(),

                        // Max tickets toggle + field
                        Row(
                          children: [
                            Expanded(
                              child: CustomHeaderTextWidget(
                                text:
                                    '(${cardData.isMaxLimitedTickets ? 'Limited' : 'Unlimited'}) Max Tickets',
                              ),
                            ),
                            CustomCheckbox(
                              value: cardData.isMaxLimitedTickets,
                              onChanged: (v) => setState(
                                () => cardData.isMaxLimitedTickets = v,
                              ),
                            ),
                          ],
                        ),
                        if (cardData.isMaxLimitedTickets) ...[
                          const SizedBox(height: 8),
                          TextFormField(
                            controller: cardData.maxTicketsController,
                            keyboardType: TextInputType.number,
                            decoration: _fieldDecoration(
                              context,
                              hint: 'Enter max tickets per customer',
                              icon: Icons.person_outline,
                            ),
                          ),
                        ],
                        if (widget.priceType != null) ...[
                          SizedBox(height: 8),
                          Row(
                            children: [
                              Expanded(
                                child: CustomHeaderTextWidget(
                                  text: 'Seat Mapping',
                                ),
                              ),
                              CustomCheckbox(
                                value: cardData.isSeatMappedForVariation,
                                onChanged: (v) => setState(
                                  () => cardData.isSeatMappedForVariation = v,
                                ),
                              ),
                            ],
                          ),
                          if (cardData.isSeatMappedForVariation) ...[
                            SizedBox(height: 8),
                            Align(
                              alignment: Alignment.center,
                              child: ElevatedButton.icon(
                                style: ElevatedButton.styleFrom(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 14,
                                    vertical: 12,
                                  ),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(14),
                                  ),
                                ),
                                onPressed: () {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) => SeatMappingScreen(),
                                    ),
                                  );
                                },
                                icon: const Icon(Icons.add_circle_outline),
                                label: const Text('Edit Seats'),
                                iconAlignment: IconAlignment.end,
                              ),
                            ),
                          ],
                        ],
                      ],
                    ),
                  ),
                );
              }),

              const SizedBox(height: 4),

              Align(
                alignment: Alignment.center,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 12,
                    ),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                  onPressed: () {
                    setState(() => variationCards.add(VariationCardData()));
                  },
                  icon: const Icon(Icons.add_circle_outline),
                  label: const Text('Add Another Variation'),
                  iconAlignment: IconAlignment.end,
                ),
              ),

              const SizedBox(height: 16),
            ],

            // ===== Ticket Limits (only when NOT Variation) =====
            if (_priceTypeIndex != 1) ...[
              _sectionCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Ticket Limits',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 12),

                    const CustomHeaderTextWidget(
                      text: 'Total Number of Available Tickets*',
                    ),
                    const SizedBox(height: 8),
                    SizedBox(
                      height: 52,
                      child: CustomToggleButton(
                        labels: const ['Unlimited', 'Limited'],
                        selectedIndex: _availableTicket,
                        onChanged: (index) =>
                            setState(() => _availableTicket = index),
                      ),
                    ),
                    if (_availableTicket == 1) ...[
                      const SizedBox(height: 12),
                      const CustomHeaderTextWidget(text: 'Available Tickets*'),
                      const SizedBox(height: 8),
                      TextField(
                        controller: _availableTicketsController,
                        keyboardType: TextInputType.number,
                        decoration: _fieldDecoration(
                          context,
                          hint: 'Enter total available tickets',
                          icon: Icons.confirmation_number_outlined,
                        ),
                      ),
                    ],

                    _dividerGap(),

                    const CustomHeaderTextWidget(text: 'Maximum Tickets*'),
                    const SizedBox(height: 8),
                    SizedBox(
                      height: 52,
                      child: CustomToggleButton(
                        labels: const ['Unlimited', 'Limited'],
                        selectedIndex: _maxTicket,
                        onChanged: (index) =>
                            setState(() => _maxTicket = index),
                      ),
                    ),
                    if (_maxTicket == 1) ...[
                      const SizedBox(height: 12),
                      const CustomHeaderTextWidget(text: 'Maximum Ticket*'),
                      const SizedBox(height: 8),
                      TextField(
                        controller: _maxTicketsController,
                        keyboardType: TextInputType.number,
                        decoration: _fieldDecoration(
                          context,
                          hint: 'Max tickets per customer',
                          icon: Icons.person_outline,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 16),
            ],

            // ===== Early Bird Discount =====
            _sectionCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Early Bird Discount',
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    height: 52,
                    child: CustomToggleButton(
                      labels: const ['Disabled', 'Enable'],
                      selectedIndex: _earlyBirdDisabled ? 0 : 1,
                      onChanged: (index) {
                        setState(() => _earlyBirdDisabled = index == 0);
                      },
                    ),
                  ),
                  if (!_earlyBirdDisabled) ...[
                    _dividerGap(),
                    const CustomHeaderTextWidget(text: 'Discount *'),
                    const SizedBox(height: 8),
                    Container(
                      height: 56,
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      decoration: BoxDecoration(
                        border: Border.all(color: theme.dividerColor),
                        borderRadius: BorderRadius.circular(14),
                        color: theme.colorScheme.surface,
                      ),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: selectedDiscountValue,
                          hint: const Text('Select discount type'),
                          borderRadius: BorderRadius.circular(14),
                          dropdownColor: theme.colorScheme.surface,
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

                    _dividerGap(),
                    const CustomHeaderTextWidget(text: 'Amount *'),
                    const SizedBox(height: 8),
                    TextField(
                      controller: _discountAmountController,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                      decoration: _fieldDecoration(
                        context,
                        hint: 'Enter amount',
                        icon: Icons.percent,
                      ),
                    ),

                    _dividerGap(),
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const CustomHeaderTextWidget(
                                text: 'Discount End Date *',
                              ),
                              const SizedBox(height: 8),
                              TextField(
                                readOnly: true,
                                controller: TextEditingController(
                                  text: discountEndDate != null
                                      ? DateFormat(
                                          'MM/dd/yyyy',
                                        ).format(discountEndDate!)
                                      : '',
                                ),
                                decoration: _fieldDecoration(
                                  context,
                                  hint: 'mm/dd/yyyy',
                                  icon: Icons.calendar_month,
                                ),
                                onTap: () => _selectDate(context, 'discount'),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const CustomHeaderTextWidget(
                                text: 'Discount End Time*',
                              ),
                              const SizedBox(height: 8),
                              TextField(
                                readOnly: true,
                                controller: TextEditingController(
                                  text: discountEndTime != null
                                      ? discountEndTime!.format(context)
                                      : '',
                                ),
                                decoration: _fieldDecoration(
                                  context,
                                  hint: '--:--',
                                  icon: Icons.access_time,
                                ),
                                onTap: () => _selectTime(context, 'discount'),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
            if (widget.priceType != null && _priceTypeIndex != 1) ...[
              const SizedBox(height: 16),
              _sectionCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Seat Mapping',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      height: 52,
                      child: CustomToggleButton(
                        labels: const ['Enabled', 'Disabled'],
                        selectedIndex: _seatMapping,
                        onChanged: (index) =>
                            setState(() => _seatMapping = index),
                      ),
                    ),
                    if (_seatMapping == 0) ...[
                      SizedBox(height: 8),
                      Align(
                        alignment: Alignment.center,
                        child: ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 14,
                              vertical: 12,
                            ),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(14),
                            ),
                          ),
                          onPressed: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) => SeatMappingScreen(),
                              ),
                            );
                          },
                          icon: const Icon(Icons.add_circle_outline),
                          label: const Text('Edit Seats'),
                          iconAlignment: IconAlignment.end,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],

            const SizedBox(height: 16),

            // ===== Language + RTL block =====
            Directionality(
              textDirection: _isRTL
                  ? ui.TextDirection.rtl
                  : ui.TextDirection.ltr,
              child: _sectionCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // Language Selector
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            'Content Language',
                            style: theme.textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ),
                        Consumer<EventTicketsProvider>(
                          builder: (_, p, _) {
                            final langs = p.languages.isNotEmpty
                                ? p.languages
                                : [
                                    // Fallback while loading
                                    EditTicketLanguage(
                                      id: 1,
                                      name: 'English',
                                      code: 'en',
                                      isRtl: false,
                                      isDefault: true,
                                    ),
                                    EditTicketLanguage(
                                      id: 2,
                                      name: 'Arabic',
                                      code: 'ar',
                                      isRtl: true,
                                      isDefault: false,
                                    ),
                                  ];
                            // Ensure selected code is valid
                            final validCode =
                                langs.any((l) => l.code == _selectedLangCode)
                                ? _selectedLangCode
                                : langs.first.code;
                            return Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                              ),
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: theme.dividerColor),
                                color: theme.colorScheme.surface,
                              ),
                              child: DropdownButtonHideUnderline(
                                child: DropdownButton<String>(
                                  value: validCode,
                                  items: langs.map((lang) {
                                    return DropdownMenuItem(
                                      value: lang.code,
                                      child: Text(lang.name),
                                    );
                                  }).toList(),
                                  onChanged: (value) {
                                    if (value == null) return;
                                    setState(() => _selectedLangCode = value);
                                  },
                                ),
                              ),
                            );
                          },
                        ),
                      ],
                    ),

                    const SizedBox(height: 14),

                    const CustomHeaderTextWidget(text: 'Ticket Name*'),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller:
                          _titleCtrls[_selectedLangCode] ??
                          TextEditingController(),
                      decoration: _fieldDecoration(
                        context,
                        hint: 'Enter ticket name',
                        icon: Icons.local_activity_outlined,
                      ),
                      onChanged: (_) => _cloneLanguageIfNeeded(),
                    ),

                    _dividerGap(),

                    const CustomHeaderTextWidget(text: 'Description'),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller:
                          _descCtrls[_selectedLangCode] ??
                          TextEditingController(),
                      decoration: _fieldDecoration(
                        context,
                        hint: 'Enter description',
                        icon: Icons.notes_outlined,
                      ),
                      maxLines: 5,
                      onChanged: (_) => _cloneLanguageIfNeeded(),
                    ),

                    const SizedBox(height: 10),
                    Consumer<EventTicketsProvider>(
                      builder: (_, p, _) {
                        final otherLangs = p.languages
                            .where((l) => l.code != _selectedLangCode)
                            .map((l) => l.name)
                            .join(', ');
                        return CustomCheckbox(
                          label:
                              'Clone to: ${otherLangs.isEmpty ? 'other languages' : otherLangs}',
                          value: _isCheckedClone,
                          onChanged: (v) {
                            setState(() {
                              _isCheckedClone = v;
                            });
                            _cloneLanguageIfNeeded();
                          },
                        );
                      },
                    ),

                    const SizedBox(height: 18),

                    Consumer<EventTicketsProvider>(
                      builder: (_, p, _) {
                        final saving = p.isSavingTicket;
                        return ElevatedButton(
                          onPressed: saving ? null : _submitTicket,
                          style: ElevatedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(14),
                            ),
                          ),
                          child: saving
                              ? const SizedBox(
                                  height: 20,
                                  width: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : const Text(
                                  'Save Ticket',
                                  style: TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                        );
                      },
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
