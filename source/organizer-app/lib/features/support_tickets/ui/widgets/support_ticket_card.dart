import 'package:flutter/material.dart';

class TicketCard extends StatelessWidget {
  final String ticketId;
  final String email;
  final String subject;
  final String status;
  final VoidCallback? onTap;
  final VoidCallback? onDelete;

  const TicketCard({
    super.key,
    required this.ticketId,
    required this.email,
    required this.subject,
    required this.status,
    this.onTap,
    this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    final border = theme.dividerColor.withValues(alpha: isDark ? 0.35 : 0.7);
    final shadowColor = Colors.black.withValues(alpha: isDark ? 0.20 : 0.06);

    final statusMeta = _statusStyle(theme, status);
    final statusColor = statusMeta.color;
    final statusBg = statusMeta.bg;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: Ink(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: theme.cardColor,
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: border),
            boxShadow: [
              BoxShadow(
                color: shadowColor,
                blurRadius: 18,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          child: Column(
            children: [
              // Top row: Ticket id + Status chip + menu/delete
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Ticket ID + small label
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Ticket ID',
                          style: theme.textTheme.labelMedium?.copyWith(
                            fontWeight: FontWeight.w700,
                            color: theme.textTheme.bodySmall?.color?.withValues(
                              alpha: 0.65,
                            ),
                            letterSpacing: 0.2,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'TKC-$ticketId',
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                      ],
                    ),
                  ),

                  // Status chip
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      color: statusBg,
                      borderRadius: BorderRadius.circular(999),
                      border: Border.all(
                        color: statusColor.withValues(alpha: 0.35),
                      ),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          width: 8,
                          height: 8,
                          decoration: BoxDecoration(
                            color: statusColor,
                            shape: BoxShape.circle,
                          ),
                        ),
                        const SizedBox(width: 6),
                        Text(
                          statusMeta.label,
                          style: theme.textTheme.labelMedium?.copyWith(
                            fontWeight: FontWeight.w800,
                            color: statusColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 8),

              // Subject (big)
              Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  'Subject: $subject',
                  style: theme.textTheme.bodyLarge?.copyWith(
                    fontWeight: FontWeight.w700,
                    height: 1.25,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ),

              const SizedBox(height: 4),

              // Email row + chevron
              Row(
                children: [
                  Container(
                    width: 34,
                    height: 34,
                    decoration: BoxDecoration(
                      color: theme.colorScheme.primary.withValues(alpha: 0.10),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(
                      Icons.mail_outline_rounded,
                      size: 18,
                      color: theme.colorScheme.primary.withValues(alpha: 0.95),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      email,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        fontWeight: FontWeight.w600,
                        color: theme.textTheme.bodyMedium?.color?.withValues(
                          alpha: 0.75,
                        ),
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  // Delete
                  if (onDelete != null)
                    Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: theme.colorScheme.error.withValues(alpha: 0.10),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: IconButton(
                        padding: const EdgeInsets.all(8),
                        constraints: const BoxConstraints(),
                        onPressed: onDelete,
                        tooltip: 'Delete',
                        icon: Icon(
                          Icons.delete_outline_rounded,
                          size: 22,
                          color: theme.colorScheme.error.withValues(alpha: 0.9),
                        ),
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

  _StatusMeta _statusStyle(ThemeData theme, String raw) {
    final s = raw.trim();
    if (s == '1') {
      final c = Colors.orange.shade700;
      return _StatusMeta('Pending', c, c.withValues(alpha: 0.12));
    }
    if (s == '2') {
      final c = Colors.blue.shade700;
      return _StatusMeta('Open', c, c.withValues(alpha: 0.12));
    }
    if (s == '3') {
      final c = Colors.grey.shade700;
      return _StatusMeta('Closed', c, c.withValues(alpha: 0.12));
    }
    final c = theme.colorScheme.primary;
    return _StatusMeta(s, c, c.withValues(alpha: 0.10));
  }
}

class _StatusMeta {
  final String label;
  final Color color;
  final Color bg;
  const _StatusMeta(this.label, this.color, this.bg);
}
