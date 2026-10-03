import 'package:flutter/material.dart';

class TransactionCard extends StatelessWidget {
  final String transactionId;
  final String transactionType;
  final String paymentMethod;

  final String preBalance;
  final String amount;
  final String afterBalance;

  final String status;
  final String? exactPreBalance;
  final String? exactAmount;
  final String? exactAfterBalance;

  const TransactionCard({
    super.key,
    required this.transactionId,
    required this.transactionType,
    required this.paymentMethod,
    required this.preBalance,
    required this.amount,
    required this.afterBalance,
    required this.status,
    this.exactPreBalance,
    this.exactAmount,
    this.exactAfterBalance,
  });

  String _getTransactionTypeName() {
    final typeId = transactionType.trim();
    switch (typeId) {
      case '1':
        return 'Event Booking';
      case '2':
        return 'Product Purchase';
      case '3':
        return 'Withdraw';
      case '4':
        return 'Balance Add';
      case '5':
        return 'Balance Subtract';
      default:
        return transactionType; // fallback to original value
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final primary = theme.colorScheme.primary;
    final isDark = theme.brightness == Brightness.dark;

    final statusUi = _statusStyle(status, primary);

    final isIncome = amount.trim().startsWith('+');
    final amountColor = isIncome ? Colors.green.shade700 : Colors.red.shade700;

    return Material(
      color: isDark ? theme.cardColor : Colors.white,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isDark ? Colors.grey.shade800 : Colors.grey.shade200,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: isDark ? 0.18 : 0.04),
              blurRadius: 10,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top row: ID + Status
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Text(
                    'Transaction ID #$transactionId',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w900,
                      height: 1.1,
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                _StatusChip(
                  label: status,
                  bg: statusUi.bg,
                  fg: statusUi.fg,
                  border: statusUi.border,
                ),
              ],
            ),

            const SizedBox(height: 10),

            // Type + method row
            Row(
              children: [
                Expanded(
                  child: _MetaLine(
                    icon: Icons.swap_horiz_rounded,
                    label: 'Type',
                    value: _getTransactionTypeName(),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _MetaLine(
                    icon: Icons.payments_outlined,
                    label: 'Method',
                    value: paymentMethod,
                  ),
                ),
              ],
            ),

            const SizedBox(height: 12),
            Divider(height: 1, color: Colors.grey.shade200),
            const SizedBox(height: 12),

            // Balances (3 columns)
            Row(
              children: [
                Expanded(
                  child: _StatBox(
                    label: 'Previous',
                    value: preBalance,
                    icon: Icons.account_balance_wallet_outlined,
                    valueColor: Colors.grey.shade900,
                    exactValue: exactPreBalance,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _StatBox(
                    label: 'Amount',
                    value: amount,
                    icon: isIncome
                        ? Icons.trending_up_rounded
                        : Icons.trending_down_rounded,
                    valueColor: amountColor,
                    exactValue: exactAmount,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _StatBox(
                    label: 'After',
                    value: afterBalance,
                    icon: Icons.account_balance_outlined,
                    valueColor: Colors.grey.shade900,
                    exactValue: exactAfterBalance,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  _StatusUI _statusStyle(String status, Color primary) {
    final s = status.toLowerCase().trim();

    if (s == '1') {
      return _StatusUI(
        bg: const Color(0xFFE7F7EE),
        fg: const Color(0xFF167D3D),
        border: const Color(0xFFBDECCF),
      );
    }
    if (s == '2') {
      return _StatusUI(
        bg: const Color(0xFFFFF6E6),
        fg: const Color(0xFF9A5B00),
        border: const Color(0xFFFFE1B3),
      );
    }
    if (s == '1') {
      return _StatusUI(
        bg: primary.withValues(alpha: 0.10),
        fg: primary,
        border: primary.withValues(alpha: 0.22),
      );
    }
    if (s == '0' || s == 'decline') {
      return _StatusUI(
        bg: const Color(0xFFFFEAEA),
        fg: const Color(0xFFC62828),
        border: const Color(0xFFFFC5C5),
      );
    }
    return _StatusUI(
      bg: Colors.grey.shade100,
      fg: Colors.grey.shade700,
      border: Colors.grey.shade200,
    );
  }
}

class _StatusChip extends StatelessWidget {
  final String label;
  final Color bg;
  final Color fg;
  final Color border;

  const _StatusChip({
    required this.label,
    required this.bg,
    required this.fg,
    required this.border,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: border),
      ),
      child: Text(
        label == '1'
            ? 'Paid'
            : label == '0'
            ? 'Unpaid'
            : label == '2'
            ? 'Declined'
            : label,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: TextStyle(color: fg, fontWeight: FontWeight.w800, fontSize: 12),
      ),
    );
  }
}

class _MetaLine extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _MetaLine({
    required this.icon,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Row(
      children: [
        Icon(icon, size: 18, color: Colors.grey.shade600),
        const SizedBox(width: 8),
        Expanded(
          child: RichText(
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            text: TextSpan(
              style: const TextStyle(fontSize: 13),
              children: [
                TextSpan(
                  text: '$label: ',
                  style: TextStyle(
                    color: Colors.grey.shade600,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                TextSpan(
                  text: value,
                  style: TextStyle(
                    color: isDark ? Colors.white70 : Colors.grey.shade900,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _StatBox extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color valueColor;
  final String? exactValue;

  const _StatBox({
    required this.label,
    required this.value,
    required this.icon,
    required this.valueColor,
    this.exactValue,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: isDark ? Colors.grey.shade900 : Colors.grey.shade50,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isDark ? Colors.grey.shade800 : Colors.grey.shade200,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 16, color: Colors.grey.shade600),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 11,
                    color: isDark ? Colors.grey.shade400 : Colors.grey.shade600,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: 14,
              height: 1.1,
              fontWeight: FontWeight.w900,
              color: isDark ? Colors.white70 : valueColor,
            ),
          ),
          if (exactValue != null) ...[  
            const SizedBox(height: 2),
            Text(
              exactValue!,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w600,
                color: isDark ? Colors.white38 : Colors.grey.shade500,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _StatusUI {
  final Color bg;
  final Color fg;
  final Color border;

  _StatusUI({required this.bg, required this.fg, required this.border});
}
