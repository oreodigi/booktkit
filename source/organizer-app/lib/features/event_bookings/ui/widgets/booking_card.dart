import 'package:flutter/material.dart';

class BookingCard extends StatelessWidget {
  // ───── Data ─────
  final String bookingId;
  final String eventTitle;
  final String customerName;
  final String customerPaid;
  final String organizerReceived;
  final String paidVia;
  final String paymentStatus;

  // Ticket scan
  final int scannedCount;
  final int totalTickets;

  // ───── Actions ─────
  final VoidCallback onTap;
  final VoidCallback onDetails;
  final VoidCallback onInvoice;
  final VoidCallback onDelete;

  const BookingCard({
    super.key,
    required this.bookingId,
    required this.eventTitle,
    required this.customerName,
    required this.customerPaid,
    required this.organizerReceived,
    required this.paidVia,
    required this.paymentStatus,
    required this.scannedCount,
    required this.totalTickets,
    required this.onTap,
    required this.onDetails,
    required this.onInvoice,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final paymentColor = _paymentColor(paymentStatus);
    final scanColor = _scanColor(scannedCount, totalTickets);

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Ink(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: isDark ? theme.cardColor : Colors.white,
            borderRadius: BorderRadius.circular(18),
            border: Border.all(
              color: isDark ? Colors.grey.shade800 : Colors.grey.shade200,
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: isDark ? 0.18 : 0.04),
                blurRadius: 14,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // ───────── Header ─────────
              Row(
                children: [
                  _pill(
                    context: context,
                    icon: Icons.confirmation_number_outlined,
                    text: bookingId,
                  ),
                  const Spacer(),
                  _statusPill(
                    text: paymentStatus,
                    color: paymentColor,
                    icon: Icons.verified_rounded,
                  ),
                ],
              ),

              const SizedBox(height: 12),

              // ───────── Event Title ─────────
              Text(
                eventTitle,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w900,
                  height: 1.25,
                ),
              ),

              const SizedBox(height: 14),

              // ───────── Info Grid ─────────
              Row(
                children: [
                  Expanded(
                    child: _infoTile(
                      context: context,
                      label: 'Customer',
                      value: customerName,
                      icon: Icons.person_outline,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _infoTile(
                      context: context,
                      label: 'Customer Paid',
                      value: customerPaid,
                      icon: Icons.payments_outlined,
                      valueColor: Colors.green.shade700,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(
                    child: _infoTile(
                      context: context,
                      label: 'Org. Received',
                      value: organizerReceived,
                      icon: Icons.account_balance_wallet_outlined,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _infoTile(
                      context: context,
                      label: 'Paid Via',
                      value: paidVia,
                      icon: Icons.credit_card_outlined,
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 14),

              // ───────── Scan Status Row (UPDATED) ─────────
              Row(
                children: [
                  _statusPill(
                    text: 'Ticket Scan Status',
                    color: scanColor,
                    icon: Icons.qr_code_scanner_rounded,
                  ),
                  const Spacer(),
                  _scanCounter(
                    scanned: scannedCount,
                    total: totalTickets,
                    color: scanColor,
                  ),
                ],
              ),

              const SizedBox(height: 14),
              Divider(
                height: 1,
                color: isDark ? Colors.grey.shade800 : Colors.grey.shade200,
              ),
              const SizedBox(height: 12),

              // ───────── Actions ─────────
              Row(
                children: [
                  Expanded(
                    child: _actionButton(
                      label: 'Details',
                      icon: Icons.info_outline,
                      onPressed: onDetails,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: _actionButton(
                      label: 'Invoice',
                      icon: Icons.receipt_long,
                      onPressed: onInvoice,
                    ),
                  ),
                  const SizedBox(width: 10),
                  _dangerButton(onDelete, context),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  // ───────────────── Widgets ─────────────────

  Widget _scanCounter({
    required int scanned,
    required int total,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Text(
        '$scanned / $total',
        style: TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.w900,
          color: color,
        ),
      ),
    );
  }

  Widget _pill({
    required IconData icon,
    required String text,
    required BuildContext context,
  }) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: isDark ? Colors.grey.shade800 : Colors.grey.shade100,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        children: [
          Icon(
            icon,
            size: 16,
            color: isDark ? Colors.grey.shade400 : Colors.grey.shade700,
          ),
          const SizedBox(width: 6),
          Text(
            text,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800),
          ),
        ],
      ),
    );
  }

  Widget _statusPill({
    required String text,
    required Color color,
    required IconData icon,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Row(
        children: [
          Icon(icon, size: 16, color: color),
          const SizedBox(width: 6),
          Text(
            text,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w900,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  Widget _infoTile({
    required String label,
    required String value,
    required IconData icon,
    Color? valueColor,
    required BuildContext context,
  }) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDark ? Colors.grey.shade900 : Colors.grey.shade50,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isDark ? Colors.grey.shade800 : Colors.grey.shade200,
        ),
      ),
      child: Row(
        children: [
          Icon(
            icon,
            size: 18,
            color: isDark ? Colors.grey.shade400 : Colors.grey.shade700,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 12,
                    color: isDark ? Colors.grey.shade500 : Colors.grey.shade600,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w900,
                    color:
                        valueColor ??
                        (isDark ? Colors.grey.shade200 : Colors.black87),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _actionButton({
    required String label,
    required IconData icon,
    required VoidCallback onPressed,
  }) {
    return SizedBox(
      height: 44,
      child: OutlinedButton.icon(
        onPressed: onPressed,
        icon: Icon(icon, size: 18),
        label: Text(label),
        style: OutlinedButton.styleFrom(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
    );
  }

  Widget _dangerButton(VoidCallback onDelete, BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    return SizedBox(
      height: 44,
      width: 44,
      child: IconButton(
        onPressed: onDelete,
        style: OutlinedButton.styleFrom(
          foregroundColor: Colors.red,
          backgroundColor: isDark
              ? Colors.red.shade900.withValues(alpha: 0.2)
              : Colors.red.shade50,
          side: BorderSide(
            color: isDark ? Colors.red.shade800 : Colors.red.shade200,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
        icon: const Icon(Icons.delete_outline),
      ),
    );
  }

  // ───────────────── Logic ─────────────────

  Color _paymentColor(String status) {
    final s = status.toLowerCase();
    if (s.contains('completed')) return Colors.green.shade700;
    if (s.contains('rejected') || s.contains('unpaid')) {
      return Colors.red.shade700;
    }
    return Colors.orange.shade700;
  }

  Color _scanColor(int scanned, int total) {
    if (scanned == 0) return Colors.orange.shade700;
    if (scanned >= total) return Colors.green.shade700;
    return Colors.blue.shade700;
  }
}
