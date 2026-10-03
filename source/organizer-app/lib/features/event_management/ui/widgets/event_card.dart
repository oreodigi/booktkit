import 'package:booktkit_organizer/app/app_colors.dart';
import 'package:flutter/material.dart';

class EventCard extends StatelessWidget {
  final String title;
  final String type; // Venue | Online
  final String category; // Sports | Movie | Conference ...
  final bool isActive;
  final bool isFeatured;

  /// Full card tap => Edit
  final VoidCallback onEdit;

  final ValueChanged<bool> onActiveChanged;
  final ValueChanged<bool> onFeaturedChanged;

  final VoidCallback? onManage;
  final VoidCallback? onTicketSettings;
  final VoidCallback onDelete;

  const EventCard({
    super.key,
    required this.title,
    required this.type,
    required this.category,
    required this.isActive,
    required this.isFeatured,
    required this.onEdit,
    required this.onActiveChanged,
    required this.onFeaturedChanged,
    this.onManage,
    this.onTicketSettings,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final primary = theme.colorScheme.primary;
    final isDark = theme.brightness == Brightness.dark;

    final isVenue = type.toLowerCase() == 'venue';
    final typeLabel = isVenue ? 'Venue' : 'Online';
    final typeIcon = isVenue
        ? Icons.location_on_rounded
        : Icons.videocam_rounded;

    return Card(
      child: InkWell(
        onTap: onEdit,
        borderRadius: BorderRadius.circular(12),
        child: Ink(
          decoration: BoxDecoration(
            color: isDark ? AppColors.darkSurface : Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: isDark ? Colors.grey.shade800 : Colors.grey.shade200,
            ),
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: Column(
              children: [
                // ===== Header (no Manage here now) =====
                Padding(
                  padding: const EdgeInsets.fromLTRB(12, 16, 12, 12),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        width: 48,
                        height: 48,
                        decoration: BoxDecoration(
                          color: primary.withValues(alpha: 0.10),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: primary.withValues(alpha: 0.18),
                          ),
                        ),
                        child: Icon(
                          Icons.event_rounded,
                          color: primary,
                          size: 24,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              title,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: 17,
                                fontWeight: FontWeight.w900,
                                height: 1.22,
                                color: theme.textTheme.bodyLarge?.color,
                              ),
                            ),
                            const SizedBox(height: 10),
                            SingleChildScrollView(
                              scrollDirection: Axis.horizontal,
                              physics: const BouncingScrollPhysics(),
                              child: Row(
                                children: [
                                  _ChipPill(
                                    icon: typeIcon,
                                    text: typeLabel,
                                    bg: isVenue
                                        ? Colors.blue.withValues(alpha: 0.10)
                                        : Colors.purple.withValues(alpha: 0.10),
                                    border: isVenue
                                        ? Colors.blue.withValues(alpha: 0.22)
                                        : Colors.purple.withValues(alpha: 0.22),
                                    fg: isVenue
                                        ? Colors.blue.shade800
                                        : Colors.purple.shade800,
                                  ),
                                  const SizedBox(width: 8),
                                  _ChipPill(
                                    icon: Icons.category_rounded,
                                    text: category,
                                    bg: isDark
                                        ? Colors.grey.shade800
                                        : Colors.grey.shade50,
                                    border: isDark
                                        ? Colors.grey.shade700
                                        : Colors.grey.shade200,
                                    fg: isDark
                                        ? Colors.grey.shade300
                                        : Colors.grey.shade800,
                                  ),
                                  const SizedBox(width: 8),
                                  _ChipPill(
                                    icon: isFeatured
                                        ? Icons.star_rounded
                                        : Icons.star_border_rounded,
                                    text: isFeatured
                                        ? 'Featured'
                                        : 'Not featured',
                                    bg: isFeatured
                                        ? Colors.amber.withValues(alpha: 0.14)
                                        : (isDark
                                              ? Colors.grey.shade800
                                              : Colors.grey.shade50),
                                    border: isFeatured
                                        ? Colors.amber.withValues(alpha: 0.30)
                                        : (isDark
                                              ? Colors.grey.shade700
                                              : Colors.grey.shade200),
                                    fg: isFeatured
                                        ? Colors.amber.shade900
                                        : (isDark
                                              ? Colors.grey.shade400
                                              : Colors.grey.shade700),
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

                // ===== Switch Row =====
                Padding(
                  padding: const EdgeInsets.fromLTRB(12, 4, 12, 14),
                  child: Row(
                    children: [
                      Expanded(
                        child: _SwitchCompact(
                          title: 'Status',
                          valueText: isActive ? 'Active' : 'Inactive',
                          value: isActive,
                          onChanged: onActiveChanged,
                          primary: primary,
                          isDark: isDark,
                          icon: isActive
                              ? Icons.power_rounded
                              : Icons.power_off_rounded,
                          iconColor: isActive ? primary : Colors.grey.shade600,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _SwitchCompact(
                          title: 'Featured',
                          valueText: isFeatured ? 'Yes' : 'No',
                          value: isFeatured,
                          onChanged: onFeaturedChanged,
                          primary: primary,
                          isDark: isDark,
                          icon: isFeatured
                              ? Icons.star_rounded
                              : Icons.star_border_rounded,
                          iconColor: isFeatured
                              ? Colors.amber.shade800
                              : Colors.grey.shade600,
                        ),
                      ),
                    ],
                  ),
                ),

                // ===== Actions =====
                Container(
                  padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
                  decoration: BoxDecoration(
                    color: isDark ? Colors.grey.shade900 : Colors.grey.shade50,
                    border: Border(
                      top: BorderSide(
                        color: isDark
                            ? Colors.grey.shade800
                            : Colors.grey.shade200,
                      ),
                    ),
                  ),
                  child: onManage == null && onTicketSettings == null
                      // ── Online-style: Edit button + delete icon ──
                      ? Row(
                          children: [
                            Expanded(
                              child: _StopCardTap(
                                child: _ActionButton(
                                  icon: Icons.edit_rounded,
                                  label: 'Edit Event',
                                  onTap: onEdit,
                                  isDark: isDark,
                                ),
                              ),
                            ),
                            const SizedBox(width: 10),
                            SizedBox(
                              height: 40,
                              width: 40,
                              child: _StopCardTap(
                                child: IconButton(
                                  onPressed: onDelete,
                                  style: IconButton.styleFrom(
                                    backgroundColor: Colors.red.withValues(
                                      alpha: 0.10,
                                    ),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(14),
                                      side: BorderSide(
                                        color: Colors.red.withValues(
                                          alpha: 0.22,
                                        ),
                                      ),
                                    ),
                                  ),
                                  icon: Icon(
                                    Icons.delete_rounded,
                                    color: Colors.red.shade700,
                                  ),
                                  tooltip: 'Delete',
                                ),
                              ),
                            ),
                          ],
                        )
                      // ── Venue-style: Manage + Ticket Settings + delete icon ──
                      : Row(
                          children: [
                            if (onManage != null) ...[
                              Expanded(
                                flex: 2,
                                child: _StopCardTap(
                                  child: _ActionButton(
                                    icon: Icons.confirmation_number_rounded,
                                    label: 'Tickets',
                                    onTap: onManage!,
                                    isDark: isDark,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 10),
                            ],
                            if (onTicketSettings != null) ...[
                              Expanded(
                                flex: 3,
                                child: _StopCardTap(
                                  child: _ActionButton(
                                    icon: Icons.settings_rounded,
                                    label: 'Ticket View',
                                    onTap: onTicketSettings!,
                                    isDark: isDark,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 10),
                            ],
                            SizedBox(
                              height: 40,
                              width: 40,
                              child: _StopCardTap(
                                child: IconButton(
                                  onPressed: onEdit,
                                  style: IconButton.styleFrom(
                                    backgroundColor: Colors.blue.withValues(
                                      alpha: 0.10,
                                    ),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(14),
                                      side: BorderSide(
                                        color: Colors.red.withValues(
                                          alpha: 0.22,
                                        ),
                                      ),
                                    ),
                                  ),
                                  icon: Icon(
                                    Icons.edit,
                                    color: Colors.grey.shade500,
                                  ),
                                  tooltip: 'Edit',
                                ),
                              ),
                            ),
                            const SizedBox(width: 10),
                            SizedBox(
                              height: 40,
                              width: 40,
                              child: _StopCardTap(
                                child: IconButton(
                                  onPressed: onDelete,
                                  style: IconButton.styleFrom(
                                    backgroundColor: Colors.red.withValues(
                                      alpha: 0.10,
                                    ),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(14),
                                      side: BorderSide(
                                        color: Colors.red.withValues(
                                          alpha: 0.22,
                                        ),
                                      ),
                                    ),
                                  ),
                                  icon: Icon(
                                    Icons.delete_rounded,
                                    color: Colors.red.shade700,
                                  ),
                                  tooltip: 'Delete',
                                ),
                              ),
                            ),
                          ],
                        ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _ChipPill extends StatelessWidget {
  final IconData icon;
  final String text;
  final Color bg;
  final Color border;
  final Color fg;

  const _ChipPill({
    required this.icon,
    required this.text,
    required this.bg,
    required this.border,
    required this.fg,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 28,
      padding: const EdgeInsets.symmetric(horizontal: 8),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: border),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 12, color: fg),
          const SizedBox(width: 6),
          Text(
            text,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w800,
              color: fg,
              height: 1,
            ),
          ),
        ],
      ),
    );
  }
}

/// ✅ Stops tap bubbling so inner buttons/switch don't trigger card onTap
class _StopCardTap extends StatelessWidget {
  final Widget child;
  const _StopCardTap({required this.child});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () {}, // consume tap
      child: child,
    );
  }
}

class _SwitchCompact extends StatelessWidget {
  final String title;
  final String valueText;
  final bool value;
  final ValueChanged<bool> onChanged;
  final Color primary;
  final bool isDark;
  final IconData icon;
  final Color iconColor;

  const _SwitchCompact({
    required this.title,
    required this.valueText,
    required this.value,
    required this.onChanged,
    required this.primary,
    required this.isDark,
    required this.icon,
    required this.iconColor,
  });

  @override
  Widget build(BuildContext context) {
    return _StopCardTap(
      child: Container(
        height: 56,
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
        decoration: BoxDecoration(
          color: isDark ? Colors.grey.shade800 : Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isDark ? Colors.grey.shade700 : Colors.grey.shade200,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: isDark ? 0.2 : 0.03),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              width: 30,
              height: 30,
              decoration: BoxDecoration(
                color: iconColor.withValues(alpha: 0.10),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: iconColor.withValues(alpha: 0.16)),
              ),
              child: Icon(icon, size: 16, color: iconColor),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w800,
                      color: isDark
                          ? Colors.grey.shade400
                          : Colors.grey.shade700,
                      height: 1,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    valueText,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w900,
                      color: isDark
                          ? Colors.grey.shade200
                          : Colors.grey.shade900,
                      height: 1,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 6),
            FittedBox(
              fit: BoxFit.scaleDown,
              child: Switch(
                value: value,
                onChanged: onChanged,
                activeThumbColor: primary,
                materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final bool isDark;

  const _ActionButton({
    required this.icon,
    required this.label,
    required this.onTap,
    required this.isDark,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 40,
      child: OutlinedButton.icon(
        onPressed: onTap,
        icon: Icon(icon, size: 18),
        label: Text(
          label,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 12),
        ),
        style: OutlinedButton.styleFrom(
          backgroundColor: isDark ? Colors.grey.shade800 : Colors.white,
          foregroundColor: isDark ? Colors.grey.shade200 : Colors.grey.shade900,
          side: BorderSide(
            color: isDark ? Colors.grey.shade700 : Colors.grey.shade300,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 8),
        ),
      ),
    );
  }
}
