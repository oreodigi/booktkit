import 'package:booktkit_organizer/app/app_routes.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_snackbar.dart';
import 'package:booktkit_organizer/features/event_management/data/models/events_model.dart';
import 'package:booktkit_organizer/features/event_management/providers/event_management_provider.dart';
import 'package:booktkit_organizer/features/event_management/ui/widgets/event_card.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class VenueEvents extends StatefulWidget {
  const VenueEvents({super.key});

  @override
  State<VenueEvents> createState() => _VenueEventsState();
}

class _VenueEventsState extends State<VenueEvents> {
  final ScrollController _scrollController = ScrollController();
  // Screen-local language selection — not shared with other screens.
  LangItem? _selectedLang;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<EventManagementProvider>().fetchEvents(
        refresh: true,
        eventType: 'venue',
        updateLanguage: true, // resets language on screen entry
      );
    });
  }

  void _onScroll() {
    if (!_scrollController.hasClients) return;
    final pos = _scrollController.position;
    if (pos.pixels >= pos.maxScrollExtent - 200) {
      final p = context.read<EventManagementProvider>();
      if (!p.isLoadingMore && p.hasMore) p.fetchNextPage();
    }
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBar(title: 'Venue Events'),
      floatingActionButton: FloatingActionButton(
        onPressed: () => Navigator.pushNamed(context, AppRoutes.addVenueEvent),
        tooltip: 'Add Event',
        child: const Icon(Icons.add),
      ),
      body: Consumer<EventManagementProvider>(
        builder: (context, provider, _) {
          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                child: Row(
                  children: [
                    Expanded(
                      child: SizedBox(
                        height: 50,
                        child: TextField(
                          decoration: const InputDecoration(
                            contentPadding: EdgeInsets.symmetric(
                              horizontal: 12,
                            ),
                            hintText: 'Search venue events',
                            prefixIcon: Icon(Icons.search),
                          ),
                          onSubmitted: (val) => provider.setTitleFilter(val),
                        ),
                      ),
                    ),
                    if (provider.langs.length > 1) ...[
                      const SizedBox(width: 8),
                      Expanded(
                        child: Container(
                          height: 50,
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          decoration: BoxDecoration(
                            border: Border.all(color: Colors.grey.shade300),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: DropdownButtonHideUnderline(
                            child: DropdownButton<LangItem>(
                              value: _selectedLang ?? provider.defaultLang,
                              isExpanded: true,
                              borderRadius: BorderRadius.circular(12),
                              dropdownColor: Theme.of(
                                context,
                              ).dialogTheme.backgroundColor,
                              items: provider.langs.map((lang) {
                                return DropdownMenuItem<LangItem>(
                                  value: lang,
                                  child: Text(lang.name),
                                );
                              }).toList(),
                              onChanged: (lang) {
                                if (lang != null) {
                                  setState(() => _selectedLang = lang);
                                  provider.fetchEvents(
                                    refresh: true,
                                    eventType: 'venue',
                                    languageCode: lang.code,
                                    updateLanguage: true,
                                  );
                                }
                              },
                            ),
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 12),
              Expanded(child: _buildContent(context, provider)),
            ],
          );
        },
      ),
    );
  }

  Widget _buildContent(BuildContext context, EventManagementProvider provider) {
    if (provider.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (provider.errorMessage != null) {
      return Center(
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
              onPressed: () => provider.fetchEvents(
                refresh: true,
                eventType: 'venue',
                languageCode: _selectedLang?.code,
                updateLanguage: true,
              ),
              child: const Text('Retry'),
            ),
          ],
        ),
      );
    }
    if (provider.events.isEmpty) {
      return const Center(child: Text('No venue events found'));
    }

    return RefreshIndicator(
      onRefresh: () => provider.fetchEvents(
        refresh: true,
        eventType: 'venue',
        languageCode: _selectedLang?.code,
        updateLanguage: true,
      ),
      child: ListView.separated(
        controller: _scrollController,
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        physics: const AlwaysScrollableScrollPhysics(),
        itemCount:
            provider.events.length +
            (provider.isLoadingMore ? 1 : 0) +
            (!provider.hasMore && provider.events.isNotEmpty ? 1 : 0),
        separatorBuilder: (_, _) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          if (index == provider.events.length && provider.isLoadingMore) {
            return const Padding(
              padding: EdgeInsets.all(16),
              child: Center(child: CircularProgressIndicator()),
            );
          }
          if (index == provider.events.length && !provider.hasMore) {
            return Padding(
              padding: const EdgeInsets.all(16),
              child: Center(
                child: Text(
                  'No more events',
                  style: TextStyle(color: Colors.grey[600], fontSize: 14),
                ),
              ),
            );
          }
          final ev = provider.events[index];
          return EventCard(
            title: ev.title,
            type: ev.typeLabel,
            category: ev.category,
            isActive: ev.isActive,
            isFeatured: ev.isFeatured,
            onActiveChanged: (newValue) async {
              final eventId = int.tryParse(ev.eventId);
              if (eventId == null) return;
              final p = context.read<EventManagementProvider>();
              final success = await p.updateEventStatus(eventId, newValue);
              if (!context.mounted) return;
              CustomSnackBar.show(
                context: context,
                message: success
                    ? (newValue ? 'Event activated' : 'Event deactivated')
                    : (p.statusError ?? 'Failed to update status'),
                type: success ? SnackBarType.success : SnackBarType.error,
              );
            },
            onFeaturedChanged: (newValue) async {
              final eventId = int.tryParse(ev.eventId);
              if (eventId == null) return;
              final p = context.read<EventManagementProvider>();
              final success = await p.updateEventFeatured(eventId, newValue);
              if (!context.mounted) return;
              CustomSnackBar.show(
                context: context,
                message: success
                    ? (newValue
                          ? 'Event marked as featured'
                          : 'Event unfeatured')
                    : (p.featuredError ?? 'Failed to update featured'),
                type: success ? SnackBarType.success : SnackBarType.error,
              );
            },
            onManage: () {
              final eventId = int.tryParse(ev.eventId);
              if (eventId != null) {
                Navigator.pushNamed(
                  context,
                  AppRoutes.manageTickets,
                  arguments: EventTicketsArgs(
                    eventId: eventId,
                    eventType: ev.typeLabel.toLowerCase(),
                  ),
                );
              }
            },
            onEdit: () {
              final eventId = int.tryParse(ev.eventId);
              Navigator.pushNamed(
                context,
                AppRoutes.editVenueEvent,
                arguments: eventId != null
                    ? EditEventArgs(eventId: eventId)
                    : null,
              );
            },
            onTicketSettings: () {
              final eventId = int.tryParse(ev.eventId);
              if (eventId != null) {
                Navigator.pushNamed(
                  context,
                  AppRoutes.ticketSettings,
                  arguments: TicketSettingsArgs(eventId: eventId),
                );
              }
            },
            onDelete: () async {
              final eventId = int.tryParse(ev.eventId);
              if (eventId == null) return;

              final confirmed = await showDialog<bool>(
                context: context,
                builder: (_) => AlertDialog(
                  title: const Text('Delete Event'),
                  content: Text(
                    'Are you sure you want to delete "${ev.title}"? This action cannot be undone.',
                  ),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: const Text('Cancel'),
                    ),
                    TextButton(
                      onPressed: () => Navigator.pop(context, true),
                      style: TextButton.styleFrom(foregroundColor: Colors.red),
                      child: const Text('Delete'),
                    ),
                  ],
                ),
              );

              if (confirmed != true || !context.mounted) return;

              final provider = context.read<EventManagementProvider>();
              final success = await provider.deleteEvent(eventId);

              if (!context.mounted) return;
              if (success) {
                CustomSnackBar.show(
                  type: SnackBarType.success,
                  context: context,
                  message: 'Event deleted successfully',
                );
              } else {
                CustomSnackBar.show(
                  type: SnackBarType.error,
                  context: context,
                  message: provider.deleteError ?? 'Failed to delete event',
                );
              }
            },
          );
        },
      ),
    );
  }
}
