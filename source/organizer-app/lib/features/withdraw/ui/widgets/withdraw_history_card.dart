import 'package:flutter/material.dart';

class WithdrawCard extends StatelessWidget {
  final String withdrawId;
  final String methodName;
  final String totalAmount;
  final String totalCharge;
  final String receivableAmount;
  final String status;
  final VoidCallback onTap;
  final VoidCallback onDelete;
  final String? exactTotalAmount;
  final String? exactTotalCharge;
  final String? exactReceivableAmount;

  const WithdrawCard({
    super.key,
    required this.withdrawId,
    required this.methodName,
    required this.totalAmount,
    required this.totalCharge,
    required this.receivableAmount,
    required this.status,
    required this.onTap,
    required this.onDelete,
    this.exactTotalAmount,
    this.exactTotalCharge,
    this.exactReceivableAmount,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final isDark = theme.brightness == Brightness.dark;
    final statusUi = _statusStyle(status, scheme);

    return Material(
      color: isDark ? theme.cardColor : Colors.white,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Ink(
          padding: const EdgeInsets.all(12),
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
              // Top Row: ID + Status + Delete
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      'ID: $withdrawId',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  _StatusChip(
                    label: statusUi.label,
                    bg: statusUi.bg,
                    fg: statusUi.fg,
                    border: statusUi.border,
                    icon: statusUi.icon,
                  ),
                  const SizedBox(width: 8),
                  InkWell(
                    onTap: onDelete,
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      width: 36,
                      height: 36,
                      decoration: BoxDecoration(
                        color: Colors.red.shade50,
                        borderRadius: BorderRadius.circular(99),
                        border: Border.all(color: Colors.red.shade100),
                      ),
                      child: Icon(
                        Icons.delete_outline_rounded,
                        color: Colors.red.shade700,
                        size: 20,
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 8),

              // Method
              Row(
                children: [
                  Icon(
                    Icons.account_balance_wallet_outlined,
                    size: 18,
                    color: Colors.grey.shade600,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      methodName,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: Colors.grey.shade800,
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 8),
              Divider(height: 1, color: Colors.grey.shade200),
              const SizedBox(height: 8),

              // Amounts (3 columns)
              Row(
                children: [
                  Expanded(
                    child: _AmountTile(
                      label: 'Total',
                      value: totalAmount,
                      icon: Icons.payments_outlined,
                      exactValue: exactTotalAmount,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: _AmountTile(
                      label: 'Charge',
                      value: totalCharge,
                      icon: Icons.request_quote_outlined,
                      exactValue: exactTotalCharge,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: _AmountTile(
                      label: 'Receivable',
                      value: receivableAmount,
                      icon: Icons.savings_outlined,
                      exactValue: exactReceivableAmount,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  _StatusUI _statusStyle(String status, ColorScheme scheme) {
    final s = status.trim().toLowerCase();

    if (s == '1' || s == 'approve' || s == 'success') {
      return _StatusUI(
        label: 'Approved',
        icon: Icons.check_circle_rounded,
        bg: Colors.green.shade50,
        fg: Colors.green.shade800,
        border: Colors.green.shade200,
      );
    }
    if (s == '2' || s == 'decline') {
      return _StatusUI(
        label: 'Declined',
        icon: Icons.cancel_rounded,
        bg: Colors.red.shade50,
        fg: Colors.red.shade800,
        border: Colors.red.shade200,
      );
    }
    if (s == 'processing') {
      return _StatusUI(
        label: 'Processing',
        icon: Icons.autorenew_rounded,
        bg: Colors.blue.shade50,
        fg: Colors.blue.shade800,
        border: Colors.blue.shade200,
      );
    }
    if (s == '0' || s == 'pending') {
      return _StatusUI(
        label: 'Pending',
        icon: Icons.pending_rounded,
        bg: Colors.orange.shade50,
        fg: Colors.orange.shade900,
        border: Colors.orange.shade200,
      );
    }
    return _StatusUI(
      label: 'Cancelled',
      icon: Icons.cancel_rounded,
      bg: Colors.red.shade50,
      fg: Colors.red.shade800,
      border: Colors.red.shade200,
    );
  }
}

class _AmountTile extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final String? exactValue;

  const _AmountTile({
    required this.label,
    required this.value,
    required this.icon,
    this.exactValue,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      decoration: BoxDecoration(
        color: isDark ? Colors.grey.shade900 : Colors.grey.shade50,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isDark ? Colors.grey.shade800 : Colors.grey.shade200,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 16, color: Colors.grey.shade700),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    color: Colors.grey.shade700,
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
              fontWeight: FontWeight.w800,
              color: isDark ? Colors.white70 : Colors.grey.shade900,
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

class _StatusChip extends StatelessWidget {
  final String label;
  final Color bg;
  final Color fg;
  final Color border;
  final IconData icon;

  const _StatusChip({
    required this.label,
    required this.bg,
    required this.fg,
    required this.border,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: border),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: fg),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(
              color: fg,
              fontSize: 12,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

class _StatusUI {
  final String label;
  final IconData icon;
  final Color bg;
  final Color fg;
  final Color border;

  _StatusUI({
    required this.label,
    required this.icon,
    required this.bg,
    required this.fg,
    required this.border,
  });
}
