import 'dart:ui' as ui;
import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_checkbox.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_header_text_widget.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_snackbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_toggle_button.dart';
import 'package:booktkit_organizer/features/event_management/data/models/edit_ticket_model.dart';
import 'package:booktkit_organizer/features/event_management/data/models/update_ticket_request.dart';
import 'package:booktkit_organizer/features/event_management/providers/edit_ticket_provider.dart';
import 'package:booktkit_organizer/features/event_management/ui/screens/seat_mapping_screen.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

// ---------------------------------------------------------------------------
// Widget
// ---------------------------------------------------------------------------
class EditTicketScreen extends StatefulWidget {
  final int? eventId;
  final String? eventType;
  final int? ticketId;

  // Legacy: kept for backward-compat if old args are passed
  final String? ticketTitle;

  const EditTicketScreen({
    super.key,
    this.eventId,
    this.eventType,
    this.ticketId,
    this.ticketTitle,
  });

  @override
  State<EditTicketScreen> createState() => _EditTicketScreenState();
}

// ---------------------------------------------------------------------------
// Variation card state (mirrors AddTicketsScreen's VariationCardData)
// ---------------------------------------------------------------------------
class _EditVariationCard {
  final Map<String, TextEditingController> nameControllers = {};
  final TextEditingController priceController;
  final TextEditingController availableController;
  final TextEditingController maxController;
  bool isLimitedTickets;
  bool isMaxLimited;
  bool isSeatMapped;

  /// Slot unique ID from server (null for newly added variations)
  String? slotUniqueId;

  _EditVariationCard({
    String price = '',
    String available = '',
    String max = '',
    this.isLimitedTickets = false,
    this.isMaxLimited = false,
    this.isSeatMapped = false,
    this.slotUniqueId,
  }) : priceController = TextEditingController(text: price),
       availableController = TextEditingController(text: available),
       maxController = TextEditingController(text: max);

  TextEditingController nameCtrlForCode(String code) {
    return nameControllers.putIfAbsent(code, () => TextEditingController());
  }

  void ensureControllersForLanguages(List<String> codes) {
    for (final code in codes) {
      nameControllers.putIfAbsent(code, () => TextEditingController());
    }
  }

  void dispose() {
    for (final c in nameControllers.values) {
      c.dispose();
    }
    priceController.dispose();
    availableController.dispose();
    maxController.dispose();
  }
}

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------
class _EditTicketScreenState extends State<EditTicketScreen> {
  bool _initialised = false;

  // ── Form state ────────────────────────────────────────────────────────────
  int _priceTypeIndex = 0; // 0=Free, 1=Variation, 2=Fixed
  int _availableTicket = 0; // 0=Unlimited, 1=Limited
  int _maxTicket = 0; // 0=Unlimited, 1=Limited
  bool _earlyBirdDisabled = true;
  String? _selectedDiscountType; // Fixed / Percentage
  int _seatMapping = 1; // 0=Enabled, 1=Disabled

  /// Language code for cloning text
  final String _selectedLangCode = 'en';
  bool _isCheckedClone = false;

  final Map<String, TextEditingController> _titleCtrls = {};
  final Map<String, TextEditingController> _descCtrls = {};
  final _fixedPriceController = TextEditingController();
  final _availableTicketsController = TextEditingController();
  final _maxTicketsController = TextEditingController();
  final _discountAmountController = TextEditingController();

  DateTime? _discountEndDate;
  TimeOfDay? _discountEndTime;

  final List<_EditVariationCard> _variationCards = [];

  final List<String> _discountOptions = ['Fixed', 'Percentage'];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (widget.eventId != null &&
          widget.eventType != null &&
          widget.ticketId != null) {
        context.read<EditTicketProvider>().fetchEditTicket(
          eventId: widget.eventId!,
          eventType: widget.eventType!,
          ticketId: widget.ticketId!,
        );
      }
    });
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
    for (final c in _variationCards) {
      c.dispose();
    }
    super.dispose();
  }

  void _ensureControllersForLanguages(List<String> codes) {
    for (final code in codes) {
      _titleCtrls.putIfAbsent(code, () => TextEditingController());
      _descCtrls.putIfAbsent(code, () => TextEditingController());
    }
    for (final v in _variationCards) {
      v.ensureControllersForLanguages(codes);
    }
  }

  void _cloneLanguageIfNeeded() {
    if (!_isCheckedClone) return;
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
    for (final v in _variationCards) {
      final srcName = v.nameCtrlForCode(_selectedLangCode).text;
      for (final code in v.nameControllers.keys) {
        if (code != _selectedLangCode &&
            v.nameControllers[code]!.text.trim().isEmpty) {
          v.nameControllers[code]!.text = srcName;
        }
      }
    }
  }

  // ── Pre-populate from API ─────────────────────────────────────────────────
  void _initFromTicket(
    EditTicketItem ticket,
    List<EditTicketVariation> vars,
    List<EditTicketLanguage> langs,
    List<EditTicketContent> ticketContents,
  ) {
    if (_initialised) return;
    _initialised = true;

    final langCodes = langs.map((e) => e.code).toList();
    _ensureControllersForLanguages(langCodes);

    // Pricing type
    _priceTypeIndex = ticket.priceTypeIndex;

    // Title per language — prefer ticket_contents (per-language), fall back to ticket.title
    if (ticketContents.isNotEmpty) {
      // Build a map: languageId → code for quick lookup
      final idToCode = {for (final l in langs) l.id: l.code};
      for (final content in ticketContents) {
        final code = idToCode[content.languageId];
        if (code != null && _titleCtrls.containsKey(code)) {
          _titleCtrls[code]!.text = content.title ?? '';
        }
        if (code != null && _descCtrls.containsKey(code)) {
          _descCtrls[code]!.text = content.description ?? '';
        }
      }
    } else {
      // Fallback: put ticket.title into the English (or first) controller
      if (_titleCtrls.containsKey('en')) {
        _titleCtrls['en']!.text = ticket.title ?? '';
      } else if (langCodes.isNotEmpty) {
        _titleCtrls[langCodes.first]!.text = ticket.title ?? '';
      }
    }

    // Available tickets
    _availableTicket = ticket.isAvailableLimited ? 1 : 0;
    _availableTicketsController.text = ticket.ticketAvailable ?? '';

    // Max tickets
    _maxTicket = ticket.isMaxLimited ? 1 : 0;
    _maxTicketsController.text = ticket.maxBuyTicket ?? '';

    // Price (fixed)
    _fixedPriceController.text =
        ticket.fPrice ?? (ticket.price != '0' ? ticket.price : '');

    // Seat mapping for free / normal tickets
    if (ticket.isFree) {
      _seatMapping = ticket.freeTicketSlotEnable ? 0 : 1;
    } else {
      _seatMapping = ticket.normalTicketSlotEnable ? 0 : 1;
    }

    // Early bird
    _earlyBirdDisabled = !ticket.isEarlyBird;
    _discountAmountController.text = ticket.earlyBirdDiscountAmount ?? '';
    _selectedDiscountType = ticket.earlyBirdDiscountType == 'percentage'
        ? 'Percentage'
        : (ticket.earlyBirdDiscountType == 'fixed' ? 'Fixed' : null);

    // Early bird date/time
    if (ticket.earlyBirdDiscountDate != null &&
        ticket.earlyBirdDiscountDate!.isNotEmpty) {
      try {
        _discountEndDate = DateFormat(
          'yyyy-MM-dd',
        ).parse(ticket.earlyBirdDiscountDate!);
      } catch (_) {}
    }
    if (ticket.earlyBirdDiscountTime != null &&
        ticket.earlyBirdDiscountTime!.isNotEmpty) {
      final parts = ticket.earlyBirdDiscountTime!.split(':');
      if (parts.length >= 2) {
        _discountEndTime = TimeOfDay(
          hour: int.tryParse(parts[0]) ?? 0,
          minute: int.tryParse(parts[1]) ?? 0,
        );
      }
    }

    // Variations — prefer the top-level variations list from the API (has AR names)
    final effectiveVars = vars.isNotEmpty
        ? vars
        : ticket.variations.map((v) {
            return EditTicketVariation(
              enName: v.enName ?? v.name,
              arName: v.arName,
              name: v.name,
              price: v.price,
              ticketAvailableType: v.ticketAvailableType,
              ticketAvailable: v.ticketAvailable,
              maxTicketBuyType: v.maxTicketBuyType,
              vMaxTicketBuy: v.vMaxTicketBuy,
              slotEnable: v.slotEnable,
              slotUniqueId: v.slotUniqueId,
            );
          }).toList();

    for (final v in effectiveVars) {
      final c = _EditVariationCard(
        price: v.price,
        available: v.ticketAvailable ?? '',
        max: v.vMaxTicketBuy ?? '',
        isLimitedTickets: v.isAvailableLimited,
        isMaxLimited: v.isMaxLimited,
        isSeatMapped: v.slotEnable,
        slotUniqueId: v.slotUniqueId,
      );
      c.ensureControllersForLanguages(langCodes);
      if (v.displayEnName.isNotEmpty && langCodes.contains('en')) {
        c.nameCtrlForCode('en').text = v.displayEnName;
      }
      if (v.displayArName.isNotEmpty && langCodes.contains('ar')) {
        c.nameCtrlForCode('ar').text = v.displayArName;
      }
      _variationCards.add(c);
    }
    if (_variationCards.isEmpty && ticket.isVariation) {
      _variationCards.add(_EditVariationCard());
    }
  }

  // ── Date / Time pickers ───────────────────────────────────────────────────
  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _discountEndDate ?? DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2035),
    );
    if (picked != null) setState(() => _discountEndDate = picked);
  }

  Future<void> _pickTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: _discountEndTime ?? TimeOfDay.now(),
    );
    if (picked != null) setState(() => _discountEndTime = picked);
  }

  // ── Helpers ───────────────────────────────────────────────────────────────
  InputDecoration _dec(String hint, {IconData? icon}) {
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

  Widget _card({required Widget child}) {
    return Card(
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(padding: const EdgeInsets.all(14), child: child),
    );
  }

  Widget _gap() => const SizedBox(height: 14);

  // ── Build ─────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final provider = context.watch<EditTicketProvider>();

    if (provider.isLoading) {
      return Scaffold(
        appBar: const CustomAppBar(title: 'Edit Ticket'),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (provider.error != null) {
      return Scaffold(
        appBar: const CustomAppBar(title: 'Edit Ticket'),
        body: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(provider.error!, style: const TextStyle(color: Colors.red)),
              const SizedBox(height: 12),
              ElevatedButton(
                onPressed: () {
                  if (widget.eventId != null) {
                    context.read<EditTicketProvider>().fetchEditTicket(
                      eventId: widget.eventId!,
                      eventType: widget.eventType!,
                      ticketId: widget.ticketId!,
                    );
                  }
                },
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    // Pre-populate once data arrives
    if (provider.ticket != null && !_initialised) {
      _initFromTicket(
        provider.ticket!,
        provider.response?.variations ?? [],
        provider.languages,
        provider.response?.ticketContents ?? [],
      );
    }

    final currencyText = provider.currencyInfo?.text ?? '';
    final langs = provider.languages;

    return Scaffold(
      appBar: const CustomAppBar(title: 'Edit Ticket'),
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── 1. Clone Language Data ──────────────────────────────────────
            if (langs.isNotEmpty)
              _card(
                child: Row(
                  children: [
                    const Expanded(
                      child: Text(
                        'Copy same info to other languages?',
                        style: TextStyle(
                          fontWeight: FontWeight.w700,
                          fontSize: 15,
                        ),
                      ),
                    ),
                    CustomCheckbox(
                      value: _isCheckedClone,
                      onChanged: (v) {
                        setState(() {
                          _isCheckedClone = v;
                          _cloneLanguageIfNeeded();
                        });
                      },
                    ),
                  ],
                ),
              ),

            const SizedBox(height: 16),

            // ── 2. Pricing type ───────────────────────────────────────────
            _card(
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
                      onChanged: (i) => setState(() => _priceTypeIndex = i),
                    ),
                  ),
                  if (_priceTypeIndex == 2) ...[
                    _gap(),
                    CustomHeaderTextWidget(text: 'Price ($currencyText) *'),
                    const SizedBox(height: 8),
                    TextField(
                      controller: _fixedPriceController,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                      decoration: _dec('Enter price', icon: Icons.attach_money),
                    ),
                  ],
                ],
              ),
            ),

            const SizedBox(height: 16),

            // ── 3. Variation cards ────────────────────────────────────────
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
                  _buildPill(
                    '${_variationCards.length} item(s)',
                    Icons.confirmation_number_outlined,
                  ),
                ],
              ),
              const SizedBox(height: 10),
              ..._variationCards.asMap().entries.map((entry) {
                final idx = entry.key;
                final card = entry.value;
                return Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: _card(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Header row
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                'Variation ${idx + 1}',
                                style: theme.textTheme.titleMedium?.copyWith(
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ),
                            if (_variationCards.length > 1)
                              IconButton(
                                onPressed: () {
                                  setState(() {
                                    _variationCards.removeAt(idx).dispose();
                                  });
                                },
                                icon: Icon(
                                  Icons.delete_outline,
                                  color: theme.colorScheme.error,
                                ),
                              ),
                          ],
                        ),
                        const Divider(height: 18),

                        if (langs.isNotEmpty)
                          ...langs.map((lang) {
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
                                    controller: card.nameCtrlForCode(lang.code),
                                    decoration: _dec(
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

                        // Price
                        CustomHeaderTextWidget(text: 'Price ($currencyText)*'),
                        const SizedBox(height: 8),
                        TextField(
                          controller: card.priceController,
                          keyboardType: const TextInputType.numberWithOptions(
                            decimal: true,
                          ),
                          decoration: _dec(
                            'Enter price',
                            icon: Icons.attach_money,
                          ),
                        ),
                        _gap(),

                        // Available tickets toggle
                        Row(
                          children: [
                            Expanded(
                              child: CustomHeaderTextWidget(
                                text:
                                    '(${card.isLimitedTickets ? 'Limited' : 'Unlimited'}) Available Tickets',
                              ),
                            ),
                            CustomCheckbox(
                              value: card.isLimitedTickets,
                              onChanged: (v) =>
                                  setState(() => card.isLimitedTickets = v),
                            ),
                          ],
                        ),
                        if (card.isLimitedTickets) ...[
                          const SizedBox(height: 8),
                          TextField(
                            controller: card.availableController,
                            keyboardType: TextInputType.number,
                            decoration: _dec(
                              'Available tickets',
                              icon: Icons.confirmation_number_outlined,
                            ),
                          ),
                        ],
                        _gap(),

                        // Max tickets toggle
                        Row(
                          children: [
                            Expanded(
                              child: CustomHeaderTextWidget(
                                text:
                                    '(${card.isMaxLimited ? 'Limited' : 'Unlimited'}) Max Tickets',
                              ),
                            ),
                            CustomCheckbox(
                              value: card.isMaxLimited,
                              onChanged: (v) =>
                                  setState(() => card.isMaxLimited = v),
                            ),
                          ],
                        ),
                        if (card.isMaxLimited) ...[
                          const SizedBox(height: 8),
                          TextField(
                            controller: card.maxController,
                            keyboardType: TextInputType.number,
                            decoration: _dec(
                              'Max tickets per customer',
                              icon: Icons.person_outline,
                            ),
                          ),
                        ],
                        _gap(),

                        // Seat mapping
                        Row(
                          children: [
                            const Expanded(
                              child: CustomHeaderTextWidget(
                                text: 'Seat Mapping',
                              ),
                            ),
                            CustomCheckbox(
                              value: card.isSeatMapped,
                              onChanged: (v) =>
                                  setState(() => card.isSeatMapped = v),
                            ),
                          ],
                        ),
                        if (card.isSeatMapped) ...[
                          const SizedBox(height: 8),
                          Align(
                            alignment: Alignment.center,
                            child: ElevatedButton.icon(
                              onPressed: () => Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => SeatMappingScreen(
                                    eventId: widget.eventId,
                                    ticketId: widget.ticketId,
                                    slotUniqueId: card.slotUniqueId,
                                    pricingType: context
                                        .read<EditTicketProvider>()
                                        .ticket
                                        ?.pricingType,
                                  ),
                                ),
                              ),
                              icon: const Icon(Icons.add_circle_outline),
                              label: const Text('Edit Seats'),
                              iconAlignment: IconAlignment.end,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                );
              }),
              Align(
                alignment: Alignment.center,
                child: ElevatedButton.icon(
                  onPressed: () =>
                      setState(() => _variationCards.add(_EditVariationCard())),
                  icon: const Icon(Icons.add_circle_outline),
                  label: const Text('Add Another Variation'),
                  iconAlignment: IconAlignment.end,
                ),
              ),
              const SizedBox(height: 16),
            ],

            // ── 4. Ticket Limits (non-variation) ──────────────────────────
            if (_priceTypeIndex != 1) ...[
              _card(
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

                    // Available
                    const CustomHeaderTextWidget(
                      text: 'Total Available Tickets*',
                    ),
                    const SizedBox(height: 8),
                    SizedBox(
                      height: 52,
                      child: CustomToggleButton(
                        labels: const ['Unlimited', 'Limited'],
                        selectedIndex: _availableTicket,
                        onChanged: (i) => setState(() => _availableTicket = i),
                      ),
                    ),
                    if (_availableTicket == 1) ...[
                      const SizedBox(height: 12),
                      const CustomHeaderTextWidget(
                        text: 'Number of available tickets*',
                      ),
                      const SizedBox(height: 8),
                      TextField(
                        controller: _availableTicketsController,
                        keyboardType: TextInputType.number,
                        decoration: _dec(
                          'Enter number',
                          icon: Icons.confirmation_number_outlined,
                        ),
                      ),
                    ],

                    _gap(),

                    // Max per customer
                    const CustomHeaderTextWidget(
                      text: 'Max Tickets per Customer*',
                    ),
                    const SizedBox(height: 8),
                    SizedBox(
                      height: 52,
                      child: CustomToggleButton(
                        labels: const ['Unlimited', 'Limited'],
                        selectedIndex: _maxTicket,
                        onChanged: (i) => setState(() => _maxTicket = i),
                      ),
                    ),
                    if (_maxTicket == 1) ...[
                      const SizedBox(height: 12),
                      const CustomHeaderTextWidget(text: 'Max tickets*'),
                      const SizedBox(height: 8),
                      TextField(
                        controller: _maxTicketsController,
                        keyboardType: TextInputType.number,
                        decoration: _dec(
                          'Max tickets per customer',
                          icon: Icons.person_outline,
                        ),
                      ),
                    ],
                  ],
                ),
              ),

              // Seat Mapping (free / normal only)
              const SizedBox(height: 16),
              _card(
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
                        onChanged: (i) => setState(() => _seatMapping = i),
                      ),
                    ),
                    if (_seatMapping == 0) ...[
                      const SizedBox(height: 8),
                      Align(
                        alignment: Alignment.center,
                        child: ElevatedButton.icon(
                          onPressed: () => Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) {
                                final ticketData = context
                                    .read<EditTicketProvider>()
                                    .ticket;
                                return SeatMappingScreen(
                                  eventId: widget.eventId,
                                  ticketId: widget.ticketId,
                                  slotUniqueId: ticketData?.slotUniqueId,
                                  pricingType: ticketData?.pricingType,
                                );
                              },
                            ),
                          ),
                          icon: const Icon(Icons.add_circle_outline),
                          label: const Text('Edit Seats'),
                          iconAlignment: IconAlignment.end,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 16),
            ],

            // ── 5. Ticket Name & Description ──────────────────────────────
            if (langs.isNotEmpty)
              _card(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Ticket Content',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 12),
                    ...langs.map((lang) {
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            CustomHeaderTextWidget(
                              text: 'Ticket Name (${lang.name})*',
                            ),
                            const SizedBox(height: 8),
                            TextFormField(
                              controller: _titleCtrls[lang.code],
                              decoration: _dec(
                                'Enter ticket name in ${lang.name}',
                                icon: Icons.label_outline,
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
                  ],
                ),
              ),

            const SizedBox(height: 16),

            // ── 6. Early Bird Discount ────────────────────────────────────
            _card(
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
                      onChanged: (i) =>
                          setState(() => _earlyBirdDisabled = i == 0),
                    ),
                  ),
                  if (!_earlyBirdDisabled) ...[
                    _gap(),
                    const CustomHeaderTextWidget(text: 'Discount Type *'),
                    const SizedBox(height: 8),
                    Container(
                      height: 56,
                      decoration: BoxDecoration(
                        border: Border.all(color: theme.dividerColor),
                        borderRadius: BorderRadius.circular(14),
                        color: theme.colorScheme.surface,
                      ),
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: _selectedDiscountType,
                          hint: const Text('Select discount type'),
                          isExpanded: true,
                          borderRadius: BorderRadius.circular(14),
                          dropdownColor: theme.colorScheme.surface,
                          items: _discountOptions.map((it) {
                            return DropdownMenuItem<String>(
                              value: it,
                              child: Text(it),
                            );
                          }).toList(),
                          onChanged: (v) =>
                              setState(() => _selectedDiscountType = v),
                        ),
                      ),
                    ),
                    _gap(),
                    const CustomHeaderTextWidget(text: 'Amount *'),
                    const SizedBox(height: 8),
                    TextField(
                      controller: _discountAmountController,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                      decoration: _dec('Enter amount', icon: Icons.percent),
                    ),
                    _gap(),
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
                                  text: _discountEndDate != null
                                      ? DateFormat(
                                          'MM/dd/yyyy',
                                        ).format(_discountEndDate!)
                                      : '',
                                ),
                                decoration: _dec(
                                  'mm/dd/yyyy',
                                  icon: Icons.calendar_month,
                                ),
                                onTap: _pickDate,
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
                                  text: _discountEndTime != null
                                      ? _discountEndTime!.format(context)
                                      : '',
                                ),
                                decoration: _dec(
                                  '--:--',
                                  icon: Icons.access_time,
                                ),
                                onTap: _pickTime,
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
          ],
        ),
      ),

      // ── Save button ─────────────────────────────────────────────────────
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
          child: ElevatedButton(
            onPressed: provider.isUpdatingTicket ? null : _onSave,
            style: ElevatedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(14),
              ),
            ),
            child: provider.isUpdatingTicket
                ? const SizedBox(
                    height: 20,
                    width: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Text(
                    'Save Changes',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                  ),
          ),
        ),
      ),
    );
  }

  // ── Save ────────────────────────────────────────────────────────────────
  void _onSave() async {
    final provider = context.read<EditTicketProvider>();
    final langs = provider.languages;
    if (langs.isEmpty) {
      CustomSnackBar.show(
        type: SnackBarType.error,
        context: context,
        message: 'No languages found. Cannot save.',
      );
      return;
    }

    // Validate titles
    for (final lang in langs) {
      if ((_titleCtrls[lang.code]?.text ?? '').trim().isEmpty) {
        CustomSnackBar.show(
          type: SnackBarType.error,
          context: context,
          message: 'Ticket name in ${lang.name} is required',
        );
        return;
      }
    }

    // Build descriptions & titles map
    final Map<String, String> titles = {};
    final Map<String, String> desc = {};
    for (final lang in langs) {
      titles[lang.code] = _titleCtrls[lang.code]?.text ?? '';
      desc[lang.code] = _descCtrls[lang.code]?.text ?? '';
    }

    // Gather Early Bird info
    final ebDisabled = _earlyBirdDisabled;
    final discountType = _selectedDiscountType?.toLowerCase();
    final discountAmount = double.tryParse(_discountAmountController.text);
    final discountDate = _discountEndDate != null
        ? DateFormat('yyyy-MM-dd').format(_discountEndDate!)
        : null;
    final discountTime = _discountEndTime != null
        ? '${_discountEndTime!.hour.toString().padLeft(2, '0')}:${_discountEndTime!.minute.toString().padLeft(2, '0')}'
        : null;

    if (!ebDisabled) {
      if (discountType == null ||
          discountAmount == null ||
          discountDate == null ||
          discountTime == null) {
        CustomSnackBar.show(
          type: SnackBarType.error,
          context: context,
          message: 'Please fill all early bird fields',
        );
        return;
      }
    }

    UpdateTicketRequest request;

    if (_priceTypeIndex == 0) {
      // Free
      request = UpdateTicketRequest.free(
        eventId: widget.eventId!,
        ticketId: widget.ticketId!,
        isLimitedAvailable: _availableTicket == 1,
        ticketAvailable: int.tryParse(_availableTicketsController.text),
        isLimitedMax: _maxTicket == 1,
        maxBuyTicket: int.tryParse(_maxTicketsController.text),
        earlyBirdEnabled: !ebDisabled,
        titles: titles,
        descriptions: desc,
        freeTicketeSlotEnable: _seatMapping == 0 ? 1 : 0,
        freeTicketeSlotUniqueId: 0,
      );
    } else if (_priceTypeIndex == 2) {
      // Fixed / Normal
      final p = double.tryParse(_fixedPriceController.text);
      if (p == null) {
        CustomSnackBar.show(
          type: SnackBarType.error,
          context: context,
          message: 'Fixed price is required',
        );
        return;
      }
      request = UpdateTicketRequest.normal(
        eventId: widget.eventId!,
        ticketId: widget.ticketId!,
        price: p,
        isLimitedAvailable: _availableTicket == 1,
        ticketAvailable: int.tryParse(_availableTicketsController.text),
        isLimitedMax: _maxTicket == 1,
        maxBuyTicket: int.tryParse(_maxTicketsController.text),
        earlyBirdEnabled: !ebDisabled,
        discountType: discountType,
        earlyBirdDiscountAmount: discountAmount,
        earlyBirdDiscountDate: discountDate,
        earlyBirdDiscountTime: discountTime,
        titles: titles,
        descriptions: desc,
        slotEnableNoVaidation: _seatMapping == 0 ? 1 : 0,
        slotUniqueIdNoVaidation: 0,
      );
    } else {
      // Variation
      if (_variationCards.isEmpty) {
        CustomSnackBar.show(
          type: SnackBarType.error,
          context: context,
          message: 'Please add at least one variation',
        );
        return;
      }

      final List<double> vPrices = [];
      final List<String> vLimitAvailType = [];
      final List<int?> vLimitAvail = [];
      final List<String> vMaxBuyType = [];
      final List<int?> vMaxBuy = [];
      final List<int> vSeatMap = [];
      final List<double> vSeatMinPrice = [];
      final Map<String, List<String>> vNames = {};

      for (final l in langs) {
        vNames[l.code] = [];
      }

      for (int i = 0; i < _variationCards.length; i++) {
        final card = _variationCards[i];

        // Validations
        for (final l in langs) {
          final n = card.nameControllers[l.code]?.text ?? '';
          if (n.trim().isEmpty) {
            CustomSnackBar.show(
              type: SnackBarType.error,
              context: context,
              message: 'Variation ${i + 1} name in ${l.name} is required',
            );
            return;
          }
          vNames[l.code]!.add(n);
        }

        final p = double.tryParse(card.priceController.text);
        if (p == null) {
          CustomSnackBar.show(
            type: SnackBarType.error,
            context: context,
            message: 'Variation ${i + 1} valid price is required',
          );
          return;
        }

        vPrices.add(p);
        vLimitAvailType.add(card.isLimitedTickets ? 'limited' : 'unlimited');
        vLimitAvail.add(
          card.isLimitedTickets
              ? int.tryParse(card.availableController.text)
              : null,
        );
        vMaxBuyType.add(card.isMaxLimited ? 'limited' : 'unlimited');
        vMaxBuy.add(
          card.isMaxLimited ? int.tryParse(card.maxController.text) : null,
        );
        vSeatMap.add(card.isSeatMapped ? 1 : 0);
        vSeatMinPrice.add(p);
      }

      request = UpdateTicketRequest.variation(
        eventId: widget.eventId!,
        ticketId: widget.ticketId!,
        isLimitedAvailable: _availableTicket == 1,
        ticketAvailable: int.tryParse(_availableTicketsController.text),
        isLimitedMax: _maxTicket == 1,
        maxBuyTicket: int.tryParse(_maxTicketsController.text),
        earlyBirdEnabled: !ebDisabled,
        discountType: discountType,
        earlyBirdDiscountAmount: discountAmount,
        earlyBirdDiscountDate: discountDate,
        earlyBirdDiscountTime: discountTime,
        titles: titles,
        descriptions: desc,
        variationPrice: vPrices,
        vTicketAvailableType: vLimitAvailType,
        vTicketAvailable: vLimitAvail,
        vMaxTicketBuyType: vMaxBuyType,
        vMaxTicketBuy: vMaxBuy,
        slotEnableInput: vSeatMap,
        slotUniqueIdInput: List.filled(vSeatMap.length, 0),
        slotSeatMinPrice: vSeatMinPrice,
        variationNames: vNames,
      );
    }

    final success = await provider.updateTicket(request);
    if (!mounted) return;

    if (success) {
      CustomSnackBar.show(
        type: SnackBarType.success,
        context: context,
        message: 'Ticket updated successfully!',
      );
      // Wait for snackbar
      await Future.delayed(const Duration(seconds: 1));
      if (mounted) Navigator.pop(context, true);
    } else if (provider.updateTicketError != null) {
      CustomSnackBar.show(
        type: SnackBarType.error,
        context: context,
        message: provider.updateTicketError!,
      );
    }
  }

  Widget _buildPill(String label, IconData icon) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: Colors.grey.shade300),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
          ),
        ],
      ),
    );
  }
}
