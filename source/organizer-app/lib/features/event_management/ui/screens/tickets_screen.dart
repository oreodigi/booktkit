import 'package:booktkit_organizer/app/app_routes.dart';
import 'package:booktkit_organizer/features/common/providers/currency_provider.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_snackbar.dart';
import 'package:booktkit_organizer/features/event_management/data/models/event_tickets_model.dart';
import 'package:booktkit_organizer/features/event_management/providers/edit_ticket_provider.dart';
import 'package:booktkit_organizer/features/event_management/providers/event_tickets_provider.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class TicketsScreen extends StatefulWidget {
  final int? eventId;
  final String? eventType;

  const TicketsScreen({super.key, this.eventId, this.eventType});

  @override
  State<TicketsScreen> createState() => _TicketsScreenState();
}

class _TicketsScreenState extends State<TicketsScreen> {
  final List<int> _entriesOptions = const [10, 25, 50, 100];
  int _selectedEntries = 10;

  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    if (widget.eventId != null && widget.eventType != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        context.read<EventTicketsProvider>().fetchTickets(
          eventId: widget.eventId!,
          eventType: widget.eventType!,
        );
      });
    }
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _confirmDelete(EventTicketItem ticket) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete Ticket'),
        content: Text(
          'Are you sure you want to delete "${ticket.title ?? 'this ticket'}"?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Delete', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (confirm != true || !mounted) return;

    final provider = context.read<EventTicketsProvider>();
    final success = await provider.deleteTicket(ticket.id);
    if (!mounted) return;
    CustomSnackBar.show(
      context: context,
      message: success
          ? 'Ticket deleted successfully'
          : (provider.deleteError ?? 'Failed to delete ticket'),
      type: success ? SnackBarType.success : SnackBarType.error,
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final border = theme.dividerColor.withValues(alpha: isDark ? 0.35 : 0.65);

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      appBar: const CustomAppBar(title: 'Tickets'),
      body: Consumer<EventTicketsProvider>(
        builder: (context, provider, _) {
          // ── Loading ──────────────────────────────
          if (provider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          // ── Error ────────────────────────────────
          if (provider.error != null) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      Icons.error_outline_rounded,
                      size: 56,
                      color: theme.colorScheme.error,
                    ),
                    const SizedBox(height: 14),
                    Text(
                      provider.error!,
                      textAlign: TextAlign.center,
                      style: TextStyle(color: theme.colorScheme.error),
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton.icon(
                      onPressed: widget.eventId != null
                          ? () => provider.fetchTickets(
                              eventId: widget.eventId!,
                              eventType: widget.eventType ?? 'venue',
                            )
                          : null,
                      icon: const Icon(Icons.refresh),
                      label: const Text('Retry'),
                    ),
                  ],
                ),
              ),
            );
          }

          // ── No event ID supplied ─────────────────
          if (widget.eventId == null) {
            return const Center(
              child: Text('No event selected. Please open from an event.'),
            );
          }

          final allTickets = provider.filteredTickets(_searchQuery);
          final visibleCount = allTickets.length > _selectedEntries
              ? _selectedEntries
              : allTickets.length;

          final eventTitle = provider.response?.event.title ?? 'Event Tickets';

          return SafeArea(
            child: RefreshIndicator(
              onRefresh: () async {
                await provider.fetchTickets(
                  eventId: widget.eventId!,
                  eventType: widget.eventType ?? 'venue',
                );
              },
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Event title chip
                    Row(
                      children: [
                        const Icon(Icons.event_rounded, size: 18),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            eventTitle,
                            style: theme.textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.w700,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        Chip(
                          label: Text(
                            widget.eventType?.toUpperCase() ?? '',
                            style: const TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          backgroundColor: theme.colorScheme.primary.withValues(
                            alpha: 0.12,
                          ),
                          side: BorderSide.none,
                          padding: const EdgeInsets.symmetric(horizontal: 4),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    // Controls card
                    _SurfaceCard(
                      border: border,
                      child: Column(
                        children: [
                          // Entries + Search row
                          Row(
                            children: [
                              Expanded(
                                flex: 2,
                                child: SizedBox(
                                  width: 160,
                                  child: _DropdownField<int>(
                                    value: _selectedEntries,
                                    items: _entriesOptions,
                                    label: 'Max',
                                    onChanged: (v) => setState(
                                      () => _selectedEntries =
                                          v ?? _entriesOptions.first,
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                flex: 5,
                                child: TextField(
                                  controller: _searchController,
                                  onChanged: (q) =>
                                      setState(() => _searchQuery = q),
                                  decoration: InputDecoration(
                                    hintText: 'Search tickets...',
                                    prefixIcon: const Icon(Icons.search),
                                    suffixIcon:
                                        _searchController.text.trim().isEmpty
                                        ? null
                                        : IconButton(
                                            icon: const Icon(Icons.close),
                                            onPressed: () {
                                              _searchController.clear();
                                              setState(() => _searchQuery = '');
                                            },
                                          ),
                                    filled: true,
                                    fillColor: theme.scaffoldBackgroundColor,
                                    contentPadding: const EdgeInsets.symmetric(
                                      horizontal: 12,
                                      vertical: 14,
                                    ),
                                    border: OutlineInputBorder(
                                      borderRadius: BorderRadius.circular(14),
                                      borderSide: BorderSide(color: border),
                                    ),
                                    enabledBorder: OutlineInputBorder(
                                      borderRadius: BorderRadius.circular(14),
                                      borderSide: BorderSide(color: border),
                                    ),
                                    focusedBorder: OutlineInputBorder(
                                      borderRadius: BorderRadius.circular(14),
                                      borderSide: BorderSide(
                                        color: theme.colorScheme.primary
                                            .withValues(alpha: 0.9),
                                        width: 1.2,
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          // Add Ticket button
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                padding: const EdgeInsets.symmetric(
                                  vertical: 14,
                                ),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(14),
                                ),
                              ),
                              onPressed: () {
                                Navigator.pushNamed(
                                  context,
                                  AppRoutes.addTicketScreen,
                                  arguments: AddTicketArgs(
                                    priceType: null,
                                    eventId: widget.eventId,
                                  ),
                                );
                              },
                              icon: const Icon(
                                Icons.add_circle_outline_sharp,
                                size: 20,
                              ),
                              iconAlignment: IconAlignment.end,
                              label: const Text('Add New Ticket'),
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 14),

                    // Ticket count summary
                    Text(
                      'Showing $visibleCount of ${allTickets.length} tickets',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.textTheme.bodySmall?.color?.withValues(
                          alpha: 0.6,
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),

                    // Table card
                    _SurfaceCard(
                      border: border,
                      padding: EdgeInsets.zero,
                      child: Column(
                        children: [
                          // Header
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 14,
                              vertical: 14,
                            ),
                            decoration: BoxDecoration(
                              color: theme.cardColor,
                              borderRadius: const BorderRadius.vertical(
                                top: Radius.circular(18),
                              ),
                            ),
                            child: Row(
                              children: [
                                Expanded(
                                  flex: 4,
                                  child: Text(
                                    'Title',
                                    style: theme.textTheme.labelLarge?.copyWith(
                                      fontWeight: FontWeight.w900,
                                    ),
                                  ),
                                ),
                                Expanded(
                                  flex: 2,
                                  child: Text(
                                    'Available',
                                    textAlign: TextAlign.center,
                                    style: theme.textTheme.labelLarge?.copyWith(
                                      fontWeight: FontWeight.w900,
                                    ),
                                  ),
                                ),
                                Expanded(
                                  flex: 2,
                                  child: Text(
                                    'Price',
                                    textAlign: TextAlign.center,
                                    style: theme.textTheme.labelLarge?.copyWith(
                                      fontWeight: FontWeight.w900,
                                    ),
                                  ),
                                ),
                                const SizedBox(
                                  width: 44,
                                  child: Center(
                                    child: Icon(Icons.more_horiz, size: 20),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Divider(height: 1, color: border),

                          // Empty state
                          if (allTickets.isEmpty)
                            Padding(
                              padding: const EdgeInsets.symmetric(vertical: 40),
                              child: Column(
                                children: [
                                  Icon(
                                    Icons.confirmation_number_outlined,
                                    size: 48,
                                    color: theme.iconTheme.color?.withValues(
                                      alpha: 0.3,
                                    ),
                                  ),
                                  const SizedBox(height: 12),
                                  Text(
                                    'No tickets found',
                                    style: theme.textTheme.bodyMedium?.copyWith(
                                      color: theme.textTheme.bodyMedium?.color
                                          ?.withValues(alpha: 0.5),
                                    ),
                                  ),
                                ],
                              ),
                            )
                          else
                            // Rows
                            ListView.separated(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              itemCount: visibleCount,
                              separatorBuilder: (_, _) =>
                                  Divider(height: 1, color: border),
                              itemBuilder: (context, index) {
                                final ticket = allTickets[index];
                                return _TicketRow(
                                  ticket: ticket,
                                  onDelete: () => _confirmDelete(ticket),
                                  onEdit: () {
                                    final eventId = int.tryParse(
                                      ticket.eventId,
                                    );
                                    final ticketId = ticket.id;
                                    final eventType = ticket.eventType.isEmpty
                                        ? (widget.eventType ?? 'venue')
                                        : ticket.eventType;
                                    if (eventId == null) return;
                                    // Reset provider so the screen freshly loads
                                    context.read<EditTicketProvider>().clear();
                                    Navigator.pushNamed(
                                      context,
                                      AppRoutes.editTicket,
                                      arguments: EditTicketArgs(
                                        eventId: eventId,
                                        eventType: eventType,
                                        ticketId: ticketId,
                                      ),
                                    );
                                  },
                                );
                              },
                            ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

// ─────────────────────────── UI Components ─────────────────────────────────

class _SurfaceCard extends StatelessWidget {
  final Widget child;
  final Color border;
  final EdgeInsetsGeometry? padding;

  const _SurfaceCard({required this.child, required this.border, this.padding});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    return Container(
      padding: padding ?? const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.18 : 0.06),
            blurRadius: 18,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: child,
    );
  }
}

class _DropdownField<T> extends StatelessWidget {
  final T value;
  final List<T> items;
  final String label;
  final ValueChanged<T?> onChanged;

  const _DropdownField({
    required this.value,
    required this.items,
    required this.label,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return DropdownButtonFormField<T>(
      initialValue: value,
      items: items
          .map(
            (e) => DropdownMenuItem<T>(
              value: e,
              child: Text(
                '$e',
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
            ),
          )
          .toList(),
      onChanged: onChanged,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: theme.scaffoldBackgroundColor,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(
            color: theme.dividerColor.withValues(alpha: 0.65),
          ),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(
            color: theme.colorScheme.primary.withValues(alpha: 0.9),
            width: 1.2,
          ),
        ),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 12,
          vertical: 14,
        ),
      ),
      icon: const Icon(Icons.keyboard_arrow_down_rounded),
    );
  }
}

class _TicketRow extends StatefulWidget {
  final EventTicketItem ticket;
  final VoidCallback onDelete;
  final VoidCallback onEdit;

  const _TicketRow({
    required this.ticket,
    required this.onDelete,
    required this.onEdit,
  });

  @override
  State<_TicketRow> createState() => _TicketRowState();
}

class _TicketRowState extends State<_TicketRow> {
  bool _expanded = false;

  String _availableLabel() {
    if (widget.ticket.isVariation) return 'Multiple';
    if (widget.ticket.isUnlimited) return 'Unlimited';
    return widget.ticket.ticketAvailable ?? '—';
  }

  String _priceLabel() {
    if (widget.ticket.isFree) return 'Free';
    if (widget.ticket.isVariation) return 'Varies';
    return context.read<CurrencyProvider>().format(widget.ticket.price);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final hasVariations = widget.ticket.variations.isNotEmpty;

    final availableSummary = _availableLabel();
    final priceSummary = _priceLabel();

    // Badge color based on pricing type
    Color priceBadgeColor = widget.ticket.isFree
        ? Colors.green.withValues(alpha: 0.15)
        : widget.ticket.isVariation
        ? theme.colorScheme.primary.withValues(alpha: 0.12)
        : Colors.orange.withValues(alpha: 0.15);

    Color priceBadgeText = widget.ticket.isFree
        ? Colors.green
        : widget.ticket.isVariation
        ? theme.colorScheme.primary
        : Colors.orange;

    return AnimatedContainer(
      duration: const Duration(milliseconds: 200),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: theme.cardColor,
        gradient: hasVariations
            ? LinearGradient(
                begin: Alignment.centerLeft,
                end: Alignment.centerRight,
                colors: [
                  theme.colorScheme.primary.withValues(
                    alpha: isDark ? 0.10 : 0.06,
                  ),
                  theme.cardColor,
                ],
              )
            : null,
      ),
      child: Column(
        children: [
          Row(
            children: [
              // Title + early bird chip + expand toggle
              Expanded(
                flex: 4,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      widget.ticket.title ?? '—',
                      style: theme.textTheme.bodyLarge?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (widget.ticket.isEarlyBird)
                      Padding(
                        padding: const EdgeInsets.only(top: 4),
                        child: Chip(
                          label: const Text(
                            'Early Bird',
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          backgroundColor: Colors.amber.withValues(alpha: 0.2),
                          side: BorderSide.none,
                          padding: const EdgeInsets.symmetric(horizontal: 2),
                          materialTapTargetSize:
                              MaterialTapTargetSize.shrinkWrap,
                        ),
                      ),
                    if (hasVariations)
                      InkWell(
                        borderRadius: BorderRadius.circular(999),
                        onTap: () => setState(() => _expanded = !_expanded),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 4,
                            vertical: 4,
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                _expanded ? 'Collapse' : 'Expand',
                                style: theme.textTheme.labelMedium?.copyWith(
                                  fontWeight: FontWeight.w900,
                                  color: theme.colorScheme.primary,
                                ),
                              ),
                              Icon(
                                _expanded
                                    ? Icons.keyboard_arrow_up_rounded
                                    : Icons.keyboard_arrow_down_rounded,
                                size: 18,
                                color: theme.colorScheme.primary,
                              ),
                            ],
                          ),
                        ),
                      ),
                  ],
                ),
              ),

              // Available pill
              Expanded(
                flex: 2,
                child: Center(
                  child: _Pill(
                    text: availableSummary,
                    bgColor:
                        availableSummary.toLowerCase() == 'unlimited' ||
                            availableSummary.toLowerCase() == 'multiple'
                        ? Colors.blue.withValues(alpha: 0.12)
                        : (int.tryParse(availableSummary) ?? 999) <= 20
                        ? Colors.red.withValues(alpha: 0.12)
                        : Colors.green.withValues(alpha: 0.12),
                    textColor:
                        availableSummary.toLowerCase() == 'unlimited' ||
                            availableSummary.toLowerCase() == 'multiple'
                        ? Colors.blue
                        : (int.tryParse(availableSummary) ?? 999) <= 20
                        ? Colors.red
                        : Colors.green,
                  ),
                ),
              ),

              // Price pill
              Expanded(
                flex: 2,
                child: Center(
                  child: _Pill(
                    text: priceSummary,
                    bgColor: priceBadgeColor,
                    textColor: priceBadgeText,
                  ),
                ),
              ),

              // Actions menu
              SizedBox(
                width: 44,
                child: Center(
                  child: PopupMenuButton<String>(
                    tooltip: 'Actions',
                    onSelected: (v) {
                      if (v == 'delete') widget.onDelete();
                      if (v == 'edit') widget.onEdit();
                      if (v == 'toggle' && hasVariations) {
                        setState(() => _expanded = !_expanded);
                      }
                    },
                    itemBuilder: (context) => [
                      PopupMenuItem(
                        value: 'edit',
                        child: Row(
                          children: [
                            Icon(
                              Icons.edit_outlined,
                              size: 18,
                              color: theme.iconTheme.color?.withValues(
                                alpha: 0.8,
                              ),
                            ),
                            const SizedBox(width: 8),
                            const Text('Edit'),
                          ],
                        ),
                      ),
                      if (hasVariations)
                        PopupMenuItem(
                          value: 'toggle',
                          child: Row(
                            children: [
                              Icon(
                                _expanded
                                    ? Icons.unfold_less
                                    : Icons.unfold_more,
                                size: 18,
                                color: theme.iconTheme.color?.withValues(
                                  alpha: 0.8,
                                ),
                              ),
                              const SizedBox(width: 8),
                              Text(_expanded ? 'Collapse' : 'Expand'),
                            ],
                          ),
                        ),
                      PopupMenuItem(
                        value: 'delete',
                        child: Row(
                          children: [
                            Icon(
                              Icons.delete_outline_rounded,
                              size: 18,
                              color: theme.colorScheme.error,
                            ),
                            const SizedBox(width: 8),
                            Text(
                              'Delete',
                              style: TextStyle(color: theme.colorScheme.error),
                            ),
                          ],
                        ),
                      ),
                    ],
                    child: Icon(
                      Icons.more_vert_rounded,
                      color: theme.iconTheme.color?.withValues(alpha: 0.65),
                    ),
                  ),
                ),
              ),
            ],
          ),

          // Expanded variations
          if (hasVariations && _expanded) ...[
            const SizedBox(height: 12),
            _VariationsTable(variations: widget.ticket.variations),
          ],
        ],
      ),
    );
  }
}

class _Pill extends StatelessWidget {
  final String text;
  final Color bgColor;
  final Color textColor;

  const _Pill({
    required this.text,
    required this.bgColor,
    required this.textColor,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        text,
        style: TextStyle(
          color: textColor,
          fontSize: 12,
          fontWeight: FontWeight.w800,
        ),
        overflow: TextOverflow.ellipsis,
        maxLines: 1,
      ),
    );
  }
}

class _VariationsTable extends StatelessWidget {
  final List<TicketVariation> variations;

  const _VariationsTable({required this.variations});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final border = theme.dividerColor.withValues(alpha: isDark ? 0.35 : 0.65);

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: theme.scaffoldBackgroundColor.withValues(
          alpha: isDark ? 0.35 : 0.6,
        ),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: border),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                flex: 3,
                child: Text(
                  'Type',
                  style: theme.textTheme.labelLarge?.copyWith(
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              Expanded(
                flex: 2,
                child: Text(
                  'Available',
                  textAlign: TextAlign.center,
                  style: theme.textTheme.labelLarge?.copyWith(
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              Expanded(
                flex: 2,
                child: Text(
                  'Price',
                  textAlign: TextAlign.center,
                  style: theme.textTheme.labelLarge?.copyWith(
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          ...variations.map((v) {
            final available = v.isUnlimited
                ? 'Unlimited'
                : (v.ticketAvailable ?? '—');
            return Container(
              margin: const EdgeInsets.only(bottom: 8),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
              decoration: BoxDecoration(
                color: theme.cardColor,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: border.withValues(alpha: 0.9)),
              ),
              child: Row(
                children: [
                  Expanded(
                    flex: 3,
                    child: Text(
                      v.name,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  Expanded(
                    flex: 2,
                    child: Center(
                      child: Text(
                        available,
                        textAlign: TextAlign.center,
                        style: theme.textTheme.bodySmall,
                      ),
                    ),
                  ),
                  Expanded(
                    flex: 2,
                    child: Center(
                      child: Text(
                        context.read<CurrencyProvider>().format(v.price),
                        textAlign: TextAlign.center,
                        style: theme.textTheme.bodySmall?.copyWith(
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }
}
