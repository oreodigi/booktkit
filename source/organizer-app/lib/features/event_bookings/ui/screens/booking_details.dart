import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/event_bookings/data/models/booking_details_model.dart';
import 'package:booktkit_organizer/features/event_bookings/providers/booking_details_provider.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

class BookingDetails extends StatefulWidget {
  final String bookingId;

  const BookingDetails({super.key, required this.bookingId});

  @override
  State<BookingDetails> createState() => _BookingDetailsState();
}

class _BookingDetailsState extends State<BookingDetails> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<BookingDetailsProvider>().fetchDetails(widget.bookingId);
    });
  }

  String _fmt(String? raw) {
    if (raw == null || raw.isEmpty) return '-';
    try {
      final dt = DateTime.parse(raw).toLocal();
      return DateFormat('MMM dd, yyyy hh:mm a').format(dt);
    } catch (_) {
      return raw;
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Scaffold(
      backgroundColor: isDark ? theme.scaffoldBackgroundColor : Colors.grey.shade50,
      appBar: CustomAppBar(title: 'Booking Details'),
      body: Consumer<BookingDetailsProvider>(
        builder: (context, provider, _) {
          if (provider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          if (provider.errorMessage != null) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.error_outline, size: 48, color: Colors.red),
                    const SizedBox(height: 12),
                    Text(
                      provider.errorMessage!,
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: Colors.red),
                    ),
                    const SizedBox(height: 16),
                    ElevatedButton(
                      onPressed: () => provider.fetchDetails(widget.bookingId),
                      child: const Text('Retry'),
                    ),
                  ],
                ),
              ),
            );
          }

          if (provider.details == null) return const SizedBox();

          final b = provider.details!.booking;
          final ev = provider.details!.eventContent;
          final sym = b.sym;
          final cur = b.cur;

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // ── Booking ID ────────────────────────────────
                _InfoCard(children: [
                  _InfoRow(
                    label: 'Booking ID',
                    value: b.bookingId,
                    valueColor: theme.colorScheme.primary,
                    isBold: true,
                  ),
                  const Divider(height: 24),
                  _InfoRow(
                    label: 'Payment Status',
                    value: b.paymentStatus.isNotEmpty
                        ? b.paymentStatus[0].toUpperCase() +
                            b.paymentStatus.substring(1)
                        : '-',
                    valueColor: _statusColor(b.paymentStatus),
                    isBold: true,
                  ),
                ]),

                const SizedBox(height: 12),

                // ── Event Information ─────────────────────────
                _SectionHeader(title: 'Event Information'),
                const SizedBox(height: 8),
                _InfoCard(children: [
                  _InfoRow(label: 'Event', value: ev.title ?? '-'),
                  const Divider(height: 24),
                  _InfoRow(label: 'Event Address', value: ev.address ?? '-'),
                  const Divider(height: 24),
                  _InfoRow(label: 'Event Date', value: b.eventDate ?? '-'),
                  const Divider(height: 24),
                  _InfoRow(label: 'Booking Date', value: _fmt(b.createdAt)),
                ]),

                const SizedBox(height: 12),

                // ── Payment Information ───────────────────────
                _SectionHeader(title: 'Payment Information'),
                const SizedBox(height: 8),
                _InfoCard(children: [
                  _InfoRow(
                    label: 'Ticket Price',
                    value: '$sym${b.priceD.toStringAsFixed(2)} $cur',
                  ),
                  const Divider(height: 24),
                  _InfoRow(
                    label: 'Early Bird Discount (-)',
                    value: '$sym${b.earlyBirdD.toStringAsFixed(2)} $cur',
                    valueColor: Colors.green,
                  ),
                  const Divider(height: 24),
                  _InfoRow(
                    label: 'Coupon Discount (-)',
                    value: '$sym${b.discountD.toStringAsFixed(2)} $cur',
                    valueColor: Colors.green,
                  ),
                  const Divider(height: 24),
                  _InfoRow(
                    label: 'Tax (${b.taxPercentage ?? '0'}%+)',
                    value: '$sym${b.taxD.toStringAsFixed(2)} $cur',
                    subtitle: 'Received by Admin',
                    valueColor: Colors.red,
                  ),
                  const Divider(height: 24),
                  _InfoRow(
                    label: 'Customer Paid',
                    value: '$sym${b.customerPaid.toStringAsFixed(2)} $cur',
                    isBold: true,
                  ),
                  const Divider(height: 24),
                  _InfoRow(
                    label: 'Commission (${b.commissionPercentage ?? '0'}%)',
                    value: '$sym${b.commissionD.toStringAsFixed(2)} $cur',
                    subtitle: 'Received by Admin',
                    valueColor: Colors.orange,
                  ),
                  const Divider(height: 24),
                  _InfoRow(
                    label: 'Received by Organization',
                    value: '$sym${b.receivedByOrg.toStringAsFixed(2)} $cur',
                    valueColor: Colors.green,
                    isBold: true,
                  ),
                  const Divider(height: 24),
                  _InfoRow(label: 'Paid Via', value: b.paymentMethod ?? '-'),
                  const Divider(height: 24),
                  _InfoRow(label: 'Quantity', value: b.quantity),
                  const Divider(height: 24),
                  _InfoRow(
                    label: 'Ticket Scan Status',
                    value: '${b.scannedTickets}/${b.totalTickets}',
                  ),
                ]),

                const SizedBox(height: 12),

                // ── Billing Details ───────────────────────────
                _SectionHeader(title: 'Billing Details'),
                const SizedBox(height: 8),
                _InfoCard(children: [
                  _InfoRow(label: 'Name', value: b.customerName),
                  const Divider(height: 24),
                  _InfoRow(label: 'Email', value: b.email ?? '-'),
                  const Divider(height: 24),
                  _InfoRow(label: 'Phone', value: b.phone ?? '-'),
                  const Divider(height: 24),
                  _InfoRow(label: 'Address', value: b.address ?? '-'),
                  const Divider(height: 24),
                  _InfoRow(label: 'City', value: b.city ?? '-'),
                  const Divider(height: 24),
                  _InfoRow(label: 'State', value: b.state ?? '-'),
                  const Divider(height: 24),
                  _InfoRow(label: 'Country', value: b.country ?? '-'),
                  const Divider(height: 24),
                  _InfoRow(label: 'Zip Code', value: b.zipCode ?? '-'),
                ]),

                const SizedBox(height: 12),

                // ── Tickets Info ──────────────────────────────
                if (b.variations.isNotEmpty) ...[
                  _SectionHeader(title: 'Tickets Info'),
                  const SizedBox(height: 8),
                  _InfoCard(children: [
                    // Table header
                    Row(
                      children: [
                        Expanded(
                          flex: 3,
                          child: Text(
                            'Ticket',
                            style: TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: 14,
                              color: isDark
                                  ? Colors.grey.shade400
                                  : Colors.grey.shade700,
                            ),
                          ),
                        ),
                        Expanded(
                          flex: 1,
                          child: Text(
                            'Qty',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: 14,
                              color: isDark
                                  ? Colors.grey.shade400
                                  : Colors.grey.shade700,
                            ),
                          ),
                        ),
                        Expanded(
                          flex: 1,
                          child: Text(
                            'Price',
                            textAlign: TextAlign.right,
                            style: TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: 14,
                              color: isDark
                                  ? Colors.grey.shade400
                                  : Colors.grey.shade700,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const Divider(height: 24),
                    // Deduplicate tickets by name+price, show combined qty
                    ..._groupVariations(b.variations).map((group) {
                      final v = group.first;
                      final totalQty =
                          group.fold<int>(0, (sum, t) => sum + t.qty);
                      return Column(
                        children: [
                          _TicketItem(
                            ticketName: v.name,
                            quantity: totalQty.toString(),
                            price: '$sym${v.effectivePrice.toStringAsFixed(2)}',
                            originalPrice: v.earlyBirdDiscount > 0
                                ? '$sym${v.price.toStringAsFixed(2)}'
                                : null,
                          ),
                          if (group != _groupVariations(b.variations).last)
                            const Divider(height: 20),
                        ],
                      );
                    }),
                  ]),
                ],

                const SizedBox(height: 24),
              ],
            ),
          );
        },
      ),
    );
  }

  /// Group variation tickets by name so repeated ticket types are merged
  List<List<TicketVariation>> _groupVariations(List<TicketVariation> vars) {
    final map = <String, List<TicketVariation>>{};
    for (final v in vars) {
      map.putIfAbsent(v.name, () => []).add(v);
    }
    return map.values.toList();
  }

  Color _statusColor(String status) {
    switch (status.toLowerCase()) {
      case 'completed':
        return Colors.green;
      case 'pending':
        return Colors.orange;
      case 'rejected':
        return Colors.red;
      default:
        return Colors.blue;
    }
  }
}

// ─── Local Widgets ────────────────────────────────────────────────────────────

class _SectionHeader extends StatelessWidget {
  final String title;
  const _SectionHeader({required this.title});

  @override
  Widget build(BuildContext context) {
    return Text(
      title,
      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, height: 1.2),
    );
  }
}

class _InfoCard extends StatelessWidget {
  final List<Widget> children;
  const _InfoCard({required this.children});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? theme.cardColor : Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
            color: isDark ? Colors.grey.shade800 : Colors.grey.shade200),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.18 : 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: children),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  final String? subtitle;
  final Color? valueColor;
  final bool isBold;

  const _InfoRow({
    required this.label,
    required this.value,
    this.subtitle,
    this.valueColor,
    this.isBold = false,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Text(
                label,
                style: TextStyle(
                  fontSize: 14,
                  color: isDark ? Colors.grey.shade400 : Colors.grey.shade700,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                value,
                textAlign: TextAlign.right,
                style: TextStyle(
                  fontSize: 14,
                  color: valueColor ??
                      (isDark ? Colors.grey.shade200 : Colors.black87),
                  fontWeight: isBold ? FontWeight.w700 : FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
        if (subtitle != null) ...[
          const SizedBox(height: 4),
          Text(
            subtitle!,
            style: TextStyle(
              fontSize: 12,
              color: isDark ? Colors.grey.shade500 : Colors.grey.shade600,
              fontStyle: FontStyle.italic,
            ),
          ),
        ],
      ],
    );
  }
}

class _TicketItem extends StatelessWidget {
  final String ticketName;
  final String quantity;
  final String price;
  final String? originalPrice;

  const _TicketItem({
    required this.ticketName,
    required this.quantity,
    required this.price,
    this.originalPrice,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          flex: 3,
          child: Text(
            ticketName,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: isDark ? Colors.grey.shade200 : Colors.black87,
            ),
          ),
        ),
        Expanded(
          flex: 1,
          child: Text(
            quantity,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: isDark ? Colors.grey.shade200 : Colors.black87,
            ),
          ),
        ),
        Expanded(
          flex: 1,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                price,
                textAlign: TextAlign.right,
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  color: Colors.green,
                ),
              ),
              if (originalPrice != null) ...[
                const SizedBox(height: 2),
                Text(
                  originalPrice!,
                  textAlign: TextAlign.right,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                    color: isDark ? Colors.grey.shade600 : Colors.grey.shade500,
                    decoration: TextDecoration.lineThrough,
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }
}
