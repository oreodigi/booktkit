import 'package:booktkit_organizer/app/app_colors.dart';
import 'package:booktkit_organizer/app/app_routes.dart';
import 'package:booktkit_organizer/app/assets_path.dart';
import 'package:booktkit_organizer/features/nav_appbar/ui/widgets/app_text_styles.dart';
import 'package:booktkit_organizer/features/auth/providers/auth_provider.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:provider/provider.dart';

class OrganizerDrawerWidget extends StatelessWidget {
  final Function(int index)? onSelectScreen;

  const OrganizerDrawerWidget({super.key, this.onSelectScreen});

  Future<void> _showLogoutDialog(BuildContext context) async {
    final theme = Theme.of(context);
    final cs = theme.colorScheme;

    return showDialog<void>(
      context: context,
      barrierDismissible: true,
      barrierColor: Colors.black.withValues(alpha: 0.5),
      builder: (BuildContext dialogContext) {
        return Dialog(
          insetPadding: const EdgeInsets.symmetric(
            horizontal: 40,
            vertical: 24,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(24),
          ),
          elevation: 10,
          child: Container(
            padding: const EdgeInsets.fromLTRB(20, 24, 20, 20),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(24),
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [cs.surface, cs.surface.withValues(alpha: 0.98)],
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.orange.withValues(alpha: 0.15),
                  blurRadius: 30,
                  spreadRadius: 2,
                  offset: const Offset(0, 12),
                ),
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.2),
                  blurRadius: 35,
                  offset: const Offset(0, 15),
                ),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Top icon badge
                Container(
                  width: 70,
                  height: 70,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [
                        Colors.orange.withValues(alpha: 0.20),
                        Colors.orange.withValues(alpha: 0.10),
                      ],
                    ),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                      color: Colors.orange.withValues(alpha: 0.3),
                      width: 1.5,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.orange.withValues(alpha: 0.2),
                        blurRadius: 12,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Icon(
                    FontAwesomeIcons.rightFromBracket,
                    color: Colors.orange.shade700,
                    size: 34,
                  ),
                ),

                const SizedBox(height: 16),

                // Title
                Text(
                  'Logout?',
                  textAlign: TextAlign.center,
                  style: theme.textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.w900,
                    letterSpacing: 0.2,
                    fontSize: 24,
                  ),
                ),

                const SizedBox(height: 10),

                // Message
                Text(
                  'Are you sure you want to logout from your account?',
                  textAlign: TextAlign.center,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    height: 1.4,
                    color: theme.textTheme.bodyMedium?.color?.withValues(
                      alpha: 0.7,
                    ),
                    fontWeight: FontWeight.w600,
                    fontSize: 15,
                  ),
                ),

                const SizedBox(height: 24),

                // Buttons
                Row(
                  children: [
                    // Cancel Button
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () {
                          Navigator.of(dialogContext).pop();
                        },
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 15),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                          side: BorderSide(
                            color: cs.outlineVariant,
                            width: 1.5,
                          ),
                        ),
                        child: const Text(
                          'Cancel',
                          style: TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 16,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),

                    // Logout Button
                    Expanded(
                      child: ElevatedButton(
                        onPressed: () {
                          if (!context.mounted) return;
                          Navigator.of(dialogContext).pop();
                          Navigator.of(context).pushNamedAndRemoveUntil(
                            AppRoutes.login,
                            (route) => false,
                          );
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.red,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 15),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                          elevation: 3,
                          shadowColor: Colors.red.withValues(alpha: 0.5),
                        ),
                        child: const Text(
                          'Logout',
                          style: TextStyle(
                            fontWeight: FontWeight.w900,
                            fontSize: 16,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 12),

                // Hint text
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 8,
                  ),
                  decoration: BoxDecoration(
                    color: cs.surfaceContainerHighest.withValues(alpha: 0.4),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        Icons.info_outline_rounded,
                        size: 14,
                        color: theme.textTheme.labelMedium?.color?.withValues(
                          alpha: 0.6,
                        ),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        'Your data will be saved securely',
                        style: theme.textTheme.labelMedium?.copyWith(
                          color: theme.textTheme.labelMedium?.color?.withValues(
                            alpha: 0.65,
                          ),
                          fontWeight: FontWeight.w600,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final primary = AppColors.primaryColor;

    return Drawer(
      backgroundColor: theme.scaffoldBackgroundColor,
      child: Column(
        children: [
          _DrawerHeader(primary: primary),

          // Body
          Expanded(
            child: ListView(
              padding: const EdgeInsets.fromLTRB(12, 12, 12, 12),
              children: [
                _sectionTitle('Main'),
                _DrawerTile(
                  title: 'Dashboard',
                  icon: FontAwesomeIcons.gauge,
                  onTap: () {
                    Navigator.pop(context);
                    onSelectScreen?.call(0);
                  },
                ),

                _sectionTitle('Event'),
                _DrawerExpansion(
                  title: 'Event Management',
                  icon: FontAwesomeIcons.calendarWeek,
                  children: [
                    _DrawerChildTile(
                      title: 'Add Event',
                      onTap: () => _push(context, AppRoutes.chooseEventType),
                    ),
                    _DrawerChildTile(
                      title: 'All Events',
                      onTap: () => _push(context, AppRoutes.allEvents),
                    ),
                    _DrawerChildTile(
                      title: 'Venue Events',
                      onTap: () => _push(context, AppRoutes.venueEvents),
                    ),
                    _DrawerChildTile(
                      title: 'Online Events',
                      onTap: () => _push(context, AppRoutes.onlineEvents),
                    ),
                  ],
                ),

                _sectionTitle('Bookings'),
                _DrawerExpansion(
                  title: 'Event Bookings',
                  icon: FontAwesomeIcons.solidUser,
                  children: [
                    _DrawerChildTile(
                      title: 'All Bookings',
                      onTap: () => _push(context, AppRoutes.allBookings),
                    ),
                    _DrawerChildTile(
                      title: 'Completed Bookings',
                      onTap: () => _push(context, AppRoutes.completedBookings),
                    ),
                    _DrawerChildTile(
                      title: 'Pending Bookings',
                      onTap: () => _push(context, AppRoutes.pendingBookings),
                    ),
                    _DrawerChildTile(
                      title: 'Rejected Bookings',
                      onTap: () => _push(context, AppRoutes.rejectedBookings),
                    ),
                    _DrawerChildTile(
                      title: 'Report',
                      onTap: () => _push(context, AppRoutes.report),
                    ),
                  ],
                ),

                _sectionTitle('Wallet'),
                _DrawerTile(
                  title: 'Withdraw',
                  icon: FontAwesomeIcons.wallet,
                  onTap: () => _push(context, AppRoutes.withdraw),
                ),
                _DrawerTile(
                  title: 'Transactions',
                  icon: FontAwesomeIcons.moneyBillTransfer,
                  onTap: () => _push(context, AppRoutes.transactions),
                ),

                _sectionTitle('Support'),
                // ✅ Support upore (as you wanted earlier)
                _DrawerExpansion(
                  title: 'Support Tickets',
                  icon: FontAwesomeIcons.solidCircleQuestion,
                  children: [
                    _DrawerChildTile(
                      title: 'All Tickets',
                      onTap: () => _push(context, AppRoutes.allSupportTickets),
                    ),
                    _DrawerChildTile(
                      title: 'Add a Ticket',
                      onTap: () => _push(context, AppRoutes.addSupportTicket),
                    ),
                  ],
                ),

                _sectionTitle('Account'),
                // ✅ Settings-related niche
                _DrawerTile(
                  title: 'Edit Profile',
                  icon: FontAwesomeIcons.userPen,
                  onTap: () => _push(context, AppRoutes.editProfile),
                ),
                _DrawerTile(
                  title: 'Change Password',
                  icon: FontAwesomeIcons.lock,
                  onTap: () => _push(context, AppRoutes.changePassword),
                ),

                const SizedBox(height: 24),
                Divider(color: Colors.grey.shade200),
                const SizedBox(height: 8),

                // Optional: small hint text
                Text(
                  'Version 1.0.0',
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: Colors.grey.shade500,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),

          // ✅ Logout fixed at bottom (clean button)
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
            child: SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: () {
                  _showLogoutDialog(context);
                },
                icon: const Icon(
                  FontAwesomeIcons.rightFromBracket,
                  color: Colors.red,
                  size: 16,
                ),
                label: const Text(
                  'Logout',
                  style: TextStyle(
                    color: Colors.red,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                style: OutlinedButton.styleFrom(
                  side: BorderSide(color: Colors.red.withValues(alpha: 0.35)),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _push(BuildContext context, String route) {
    Navigator.pop(context);
    Navigator.pushNamed(context, route);
  }

  Widget _sectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(6, 6, 6, 8),
      child: Text(
        title.toUpperCase(),
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w900,
          letterSpacing: 0.8,
          color: Colors.grey.shade600,
        ),
      ),
    );
  }
}

class _DrawerHeader extends StatelessWidget {
  final Color primary;

  const _DrawerHeader({required this.primary});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final org = context.watch<AuthProvider>().org;

    return Container(
      padding: const EdgeInsets.fromLTRB(16, 50, 16, 16),
      decoration: BoxDecoration(
        color: isDark ? Colors.grey.shade800 : primary,
        borderRadius: const BorderRadius.only(bottomRight: Radius.circular(12)),
      ),
      child: Row(
        children: [
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.18),
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: Colors.white.withValues(alpha: 0.25)),
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(18),
              child: org?.photo != null && org!.photo.isNotEmpty
                  ? Image.network(
                      org.photo,
                      fit: BoxFit.cover,
                      errorBuilder: (_, _, _) =>
                          Image.asset(AssetsPath.staffPng1, fit: BoxFit.cover),
                    )
                  : Image.asset(AssetsPath.staffPng1, fit: BoxFit.cover),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  org != null && org.username.isNotEmpty
                      ? '${org.username[0].toUpperCase()}${org.username.substring(1)}'
                      : 'Organizer Name',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: AppTextStyles.bodyLarge.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  org?.email ?? 'organizer@example.com',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: AppTextStyles.bodyLargeGrey.copyWith(
                    color: Colors.white.withValues(alpha: 0.85),
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _DrawerTile extends StatelessWidget {
  final String title;
  final IconData icon;
  final VoidCallback onTap;

  const _DrawerTile({
    required this.title,
    required this.icon,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(14),
          onTap: onTap,
          child: Ink(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
            decoration: BoxDecoration(
              color: isDark ? Colors.grey.shade900 : Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(
                color: isDark ? Colors.grey.shade800 : Colors.grey.shade200,
              ),
            ),
            child: Row(
              children: [
                Icon(
                  icon,
                  size: 16,
                  color: isDark ? Colors.white70 : Colors.grey.shade700,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    title,
                    style: TextStyle(
                      fontWeight: FontWeight.w800,
                      color: isDark ? Colors.white70 : Colors.grey.shade900,
                      fontSize: 14,
                    ),
                  ),
                ),
                Icon(
                  Icons.chevron_right_rounded,
                  color: isDark ? Colors.white70 : Colors.grey.shade500,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _DrawerExpansion extends StatelessWidget {
  final String title;
  final IconData icon;
  final List<Widget> children;

  const _DrawerExpansion({
    required this.title,
    required this.icon,
    required this.children,
  });

  @override
  Widget build(BuildContext context) {
    final radius = BorderRadius.circular(14);
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Container(
      margin: const EdgeInsets.only(bottom: 4),
      child: Material(
        color: isDark ? Colors.grey.shade900 : Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: radius,
          side: BorderSide(
            color: isDark ? Colors.grey.shade800 : Colors.grey.shade200,
          ),
        ),
        clipBehavior: Clip.antiAlias,
        child: Theme(
          data: Theme.of(context).copyWith(
            dividerColor: Colors.transparent,
            splashColor: isDark ? Colors.grey.shade800 : Colors.grey.shade200,
            highlightColor: isDark
                ? Colors.grey.shade800
                : Colors.grey.shade100,
          ),
          child: ExpansionTile(
            tilePadding: const EdgeInsets.symmetric(horizontal: 12),
            childrenPadding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
            title: Row(
              children: [
                Icon(
                  icon,
                  size: 16,
                  color: isDark ? Colors.white70 : Colors.grey.shade700,
                ),
                const SizedBox(width: 12),
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w800,
                    color: isDark ? Colors.white70 : Colors.grey.shade900,
                  ),
                ),
              ],
            ),
            trailing: Icon(
              Icons.expand_more_rounded,
              color: isDark ? Colors.white70 : Colors.grey.shade600,
            ),
            children: children,
          ),
        ),
      ),
    );
  }
}

class _DrawerChildTile extends StatelessWidget {
  final String title;
  final VoidCallback onTap;

  const _DrawerChildTile({required this.title, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    return Padding(
      padding: const EdgeInsets.only(top: 8),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(12),
          onTap: onTap,
          child: Ink(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
            decoration: BoxDecoration(
              color: isDark ? Colors.grey.shade900 : Colors.grey.shade50,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: isDark ? Colors.grey.shade800 : Colors.grey.shade200,
              ),
            ),
            child: Row(
              children: [
                Container(
                  width: 8,
                  height: 8,
                  decoration: BoxDecoration(
                    color: isDark ? Colors.grey.shade700 : Colors.grey.shade400,
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    title,
                    style: TextStyle(
                      fontWeight: FontWeight.w800,
                      color: isDark ? Colors.white70 : Colors.grey.shade900,
                    ),
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
