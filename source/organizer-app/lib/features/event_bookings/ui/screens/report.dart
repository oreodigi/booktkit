import 'dart:typed_data';

import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_header_text_widget.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_snackbar.dart';
import 'package:booktkit_organizer/features/event_bookings/data/models/booking_report_model.dart';
import 'package:booktkit_organizer/features/event_bookings/providers/booking_report_provider.dart';
import 'package:booktkit_organizer/features/event_bookings/ui/widgets/report_card.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

class Report extends StatefulWidget {
  const Report({super.key});

  @override
  State<Report> createState() => _ReportState();
}

class _ReportState extends State<Report> {
  final ScrollController _scrollController = ScrollController();

  static const List<String> _statusOptions = [
    'All',
    'Completed',
    'Pending',
    'Rejected',
    'Free',
  ];

  DateTime? _fromDate;
  DateTime? _toDate;

  final _dateFormat = DateFormat('yyyy-MM-dd');
  final _displayFormat = DateFormat('MM/dd/yyyy');

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<BookingReportProvider>().fetchReport(refresh: true);
    });
  }

  void _onScroll() {
    if (!_scrollController.hasClients) return;
    final pos = _scrollController.position;
    final threshold = pos.maxScrollExtent > 200
        ? pos.maxScrollExtent - 200
        : pos.maxScrollExtent * 0.8;
    if (pos.pixels >= threshold) {
      final provider = context.read<BookingReportProvider>();
      if (!provider.isLoadingMore && provider.hasMore) {
        provider.fetchNextPage();
      }
    }
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _selectDate(bool isFrom) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2035),
    );
    if (picked != null) {
      setState(() {
        if (isFrom) {
          _fromDate = picked;
        } else {
          _toDate = picked;
        }
      });
      if (!mounted) return;
      final provider = context.read<BookingReportProvider>();
      if (isFrom) {
        provider.setFromDate(_dateFormat.format(picked));
      } else {
        provider.setToDate(_dateFormat.format(picked));
      }
    }
  }

  /// Escapes a CSV field value (wraps in quotes, escapes inner quotes)
  String _csvField(String value) {
    final escaped = value.replaceAll('"', '""');
    return '"$escaped"';
  }

  Future<void> _exportCsv(List<BookingReportItem> reports) async {
    if (reports.isEmpty) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('No data to export')));
      return;
    }

    final buffer = StringBuffer();

    // Header row — matches the format from the user sample
    buffer.writeln(
      '"Booking Id","Event","Customer Name","Discount","Early Bird Discount",'
      '"Quantity","Total","Name","Email","Phone","City","State","Country",'
      '"Zip Code","Gateway","Payment Status","Date"',
    );

    for (final r in reports) {
      final sym = r.currencySymbol ?? '';
      final p = double.tryParse(r.price) ?? 0;
      final total = p;
      final totalStr = total == total.roundToDouble()
          ? total.toInt().toString()
          : total.toString();

      final eb = double.tryParse(r.earlyBirdDiscount) ?? 0;
      final ebStr = eb == eb.roundToDouble()
          ? eb.toInt().toString()
          : eb.toString();

      final customerName = r.customerName;

      // Created at formatted as yyyy-MM-dd HH:mm:ss
      String dateStr = r.createdAt ?? '';
      try {
        if (dateStr.isNotEmpty) {
          final dt = DateTime.parse(dateStr).toLocal();
          dateStr = DateFormat('yyyy-MM-dd HH:mm:ss').format(dt);
        }
      } catch (_) {}

      buffer.writeln(
        [
          _csvField(r.bookingId),
          _csvField(r.title ?? ''),
          _csvField(customerName),
          _csvField(r.discount),
          _csvField(ebStr),
          _csvField(r.quantity),
          _csvField('$sym$totalStr'),
          _csvField(customerName),
          _csvField(r.email ?? ''),
          _csvField(r.phone ?? ''),
          _csvField(r.city ?? ''),
          _csvField(r.state ?? ''),
          _csvField(r.country ?? ''),
          _csvField(r.zipCode ?? ''),
          _csvField(r.paymentMethod ?? r.gatewayType ?? ''),
          _csvField(r.paymentStatus),
          _csvField(dateStr),
        ].join(','),
      );
    }

    try {
      final filename =
          'bookings_report_${DateFormat('yyyyMMdd_HHmmss').format(DateTime.now())}.csv';
      final csvContent = buffer.toString();
      final csvBytes = csvContent.codeUnits;

      // Show native "Save As" dialog so user can choose Downloads or any folder
      final savedPath = await FilePicker.platform.saveFile(
        dialogTitle: 'Save Bookings Report',
        fileName: filename,
        type: FileType.custom,
        allowedExtensions: ['csv'],
        bytes: Uint8List.fromList(csvBytes),
      );

      if (!mounted) return;
      if (savedPath != null) {
        CustomSnackBar.show(
          context: context,
          message: 'Saved: $savedPath',
          type: SnackBarType.success,
          duration: const Duration(seconds: 5),
        );
      } else {
        // User cancelled the picker — no message needed
      }
    } catch (e) {
      if (!mounted) return;
      CustomSnackBar.show(
        context: context,
        message: 'Export failed: $e',
        type: SnackBarType.error,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final borderColor = isDark ? Colors.grey.shade700 : Colors.grey.shade300;

    return Scaffold(
      appBar: CustomAppBar(title: 'Report'),
      floatingActionButton: AnimatedSwitcher(
        duration: const Duration(milliseconds: 250),
        transitionBuilder: (child, animation) =>
            ScaleTransition(scale: animation, child: child),
        child: (_fromDate != null || _toDate != null)
            ? Consumer<BookingReportProvider>(
                key: const ValueKey('fab'),
                builder: (context, provider, _) => FloatingActionButton.extended(
                  onPressed: () => _exportCsv(provider.reports),
                  icon: const Icon(Icons.download_rounded),
                  label: const Text('Export CSV'),
                ),
              )
            : const SizedBox.shrink(key: ValueKey('hidden')),
      ),
      body: Consumer<BookingReportProvider>(
        builder: (context, provider, _) {
          // Build dynamic payment method list from API
          final methodOptions = ['All', ...provider.allPaymentMethods];
          final currentMethod = provider.methodFilter;
          final selectedMethod =
              (currentMethod != null && methodOptions.contains(currentMethod))
              ? currentMethod
              : 'All';

          final currentStatus = provider.statusFilter;
          final displayStatus = currentStatus == null
              ? 'All'
              : currentStatus[0].toUpperCase() + currentStatus.substring(1);

          return Column(
            children: [
              // ─── Filters ───────────────────────────────────────
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                child: Column(
                  children: [
                    // Date pickers row
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              CustomHeaderTextWidget(text: 'From'),
                              const SizedBox(height: 8),
                              TextField(
                                readOnly: true,
                                controller: TextEditingController(
                                  text: _fromDate != null
                                      ? _displayFormat.format(_fromDate!)
                                      : '',
                                ),
                                decoration: InputDecoration(
                                  hintText: 'mm/dd/yyyy',
                                  suffixIcon: _fromDate != null
                                      ? IconButton(
                                          icon: const Icon(
                                            Icons.clear,
                                            size: 18,
                                          ),
                                          onPressed: () {
                                            setState(() => _fromDate = null);
                                            provider.setFromDate(null);
                                          },
                                        )
                                      : const Icon(Icons.calendar_month),
                                ),
                                onTap: () => _selectDate(true),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              CustomHeaderTextWidget(text: 'To'),
                              const SizedBox(height: 8),
                              TextField(
                                readOnly: true,
                                controller: TextEditingController(
                                  text: _toDate != null
                                      ? _displayFormat.format(_toDate!)
                                      : '',
                                ),
                                decoration: InputDecoration(
                                  hintText: 'mm/dd/yyyy',
                                  suffixIcon: _toDate != null
                                      ? IconButton(
                                          icon: const Icon(
                                            Icons.clear,
                                            size: 18,
                                          ),
                                          onPressed: () {
                                            setState(() => _toDate = null);
                                            provider.setToDate(null);
                                          },
                                        )
                                      : const Icon(Icons.calendar_month),
                                ),
                                onTap: () => _selectDate(false),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    // Status + Payment Method row
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              CustomHeaderTextWidget(text: 'Payment Status'),
                              const SizedBox(height: 8),
                              _dropdown(
                                theme: theme,
                                borderColor: borderColor,
                                value: displayStatus,
                                items: _statusOptions,
                                onChanged: (v) => provider.setStatusFilter(
                                  v == 'All' ? null : v!.toLowerCase(),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              CustomHeaderTextWidget(text: 'Payment Method'),
                              const SizedBox(height: 8),
                              _dropdown(
                                theme: theme,
                                borderColor: borderColor,
                                value: selectedMethod,
                                items: methodOptions,
                                onChanged: (v) => provider.setMethodFilter(
                                  v == 'All' ? null : v,
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
              const SizedBox(height: 12),

              // ─── Content ────────────────────────────────────────
              Expanded(child: _buildContent(provider)),
            ],
          );
        },
      ),
    );
  }

  Widget _dropdown({
    required ThemeData theme,
    required Color borderColor,
    required String value,
    required List<String> items,
    required ValueChanged<String?> onChanged,
  }) {
    return Container(
      height: 50,
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 12),
      decoration: BoxDecoration(
        border: Border.all(color: borderColor),
        borderRadius: BorderRadius.circular(8),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: value,
          borderRadius: BorderRadius.circular(12),
          dropdownColor: theme.dialogTheme.backgroundColor,
          isExpanded: true,
          items: items
              .map(
                (item) => DropdownMenuItem<String>(
                  value: item,
                  child: Text(
                    item,
                    overflow: TextOverflow.ellipsis,
                    maxLines: 1,
                  ),
                ),
              )
              .toList(),
          onChanged: onChanged,
        ),
      ),
    );
  }

  Widget _buildContent(BookingReportProvider provider) {
    if (provider.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (provider.errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                provider.errorMessage!,
                style: const TextStyle(color: Colors.red),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () => provider.fetchReport(refresh: true),
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    if (provider.reports.isEmpty) {
      return const Center(child: Text('No reports found for this filter'));
    }

    return RefreshIndicator(
      onRefresh: () => provider.fetchReport(refresh: true),
      child: ListView.separated(
        controller: _scrollController,
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        physics: const AlwaysScrollableScrollPhysics(),
        itemCount:
            provider.reports.length +
            (provider.isLoadingMore ? 1 : 0) +
            (!provider.hasMore && provider.reports.isNotEmpty ? 1 : 0),
        separatorBuilder: (_, _) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          if (index == provider.reports.length && provider.isLoadingMore) {
            return const Padding(
              padding: EdgeInsets.all(16),
              child: Center(child: CircularProgressIndicator()),
            );
          }
          if (index == provider.reports.length && !provider.hasMore) {
            return Padding(
              padding: const EdgeInsets.all(16),
              child: Center(
                child: Text(
                  'No more reports',
                  style: TextStyle(color: Colors.grey[600], fontSize: 14),
                ),
              ),
            );
          }

          final r = provider.reports[index];
          return ReportCard(
            bookingId: r.bookingId,
            event: r.title ?? 'Unknown Event',
            customerName: r.customerName,
            email: r.email ?? '-',
            phone: r.phone ?? '-',
            discount: r.formattedDiscount,
            earlyBirdDiscount: r.earlyBirdDiscount,
            quantity: r.quantity,
            total: r.formattedTotal,
            city: r.city ?? '-',
            state: r.state ?? '-',
            country: r.country ?? '-',
            zipCode: r.zipCode ?? '-',
            gateway: r.paymentMethod ?? r.gatewayType ?? 'Free',
            paymentStatus: r.paymentStatus,
            date: r.eventDate ?? r.createdAt ?? '-',
          );
        },
      ),
    );
  }
}
