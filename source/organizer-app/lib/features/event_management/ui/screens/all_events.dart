import 'package:booktkit_organizer/app/app_routes.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_snackbar.dart';
import 'package:booktkit_organizer/features/event_management/data/models/events_model.dart';
import 'package:booktkit_organizer/features/event_management/providers/event_management_provider.dart';
import 'package:booktkit_organizer/features/event_management/ui/widgets/event_card.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class AllEvents extends StatefulWidget {
  const AllEvents({super.key});

  @override
  State<AllEvents> createState() => _AllEventsState();
}

class _AllEventsState extends State<AllEvents> {
  final ScrollController _scrollController = ScrollController();
  final TextEditingController _searchController = TextEditingController();

  final List<String> eventTypeOptions = ['All', 'Venue', 'Online'];
  String _selectedType = 'All';
  // Screen-local language selection — not shared with other screens.
  LangItem? _selectedLang;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      // updateLanguage: true resets _currentLanguageCode so previous screen's
      // language does not bleed into this screen.
      context.read<EventManagementProvider>().fetchEvents(
        refresh: true,
        updateLanguage: true,
      );
    });
  }

  void _onScroll() {
    if (!_scrollController.hasClients) return;
    final position = _scrollController.position;
    final threshold = position.maxScrollExtent > 200
        ? position.maxScrollExtent - 200
        : position.maxScrollExtent * 0.8;
    if (position.pixels >= threshold) {
      final provider = context.read<EventManagementProvider>();
      if (!provider.isLoadingMore && provider.hasMore) {
        provider.fetchNextPage();
      }
    }
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBar(title: 'All Events'),
      floatingActionButton: FloatingActionButton(
        onPressed: () =>
            Navigator.pushNamed(context, AppRoutes.chooseEventType),
        tooltip: 'Add Event',
        child: const Icon(Icons.add),
      ),
      body: Consumer<EventManagementProvider>(
        builder: (context, provider, _) {
          return Column(
            children: [
              // ── Filters ─────────────────────────────
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                child: Column(
                  children: [
                    TextField(
                      controller: _searchController,
                      decoration: const InputDecoration(
                        contentPadding: EdgeInsets.symmetric(horizontal: 12),
                        hintText: 'Search events',
                        prefixIcon: Icon(Icons.search),
                      ),
                      onSubmitted: (val) => provider.setTitleFilter(val),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: Container(
                            height: 50,
                            padding: const EdgeInsets.symmetric(horizontal: 12),
                            decoration: BoxDecoration(
                              border: Border.all(color: Colors.grey.shade300),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: DropdownButtonHideUnderline(
                              child: DropdownButton<String>(
                                value: _selectedType,
                                borderRadius: BorderRadius.circular(12),
                                dropdownColor: Theme.of(
                                  context,
                                ).dialogTheme.backgroundColor,
                                items: eventTypeOptions.map((item) {
                                  return DropdownMenuItem<String>(
                                    value: item,
                                    child: Text(item),
                                  );
                                }).toList(),
                                onChanged: (value) {
                                  if (value != null) {
                                    setState(() => _selectedType = value);
                                    provider.setEventTypeFilter(
                                      value == 'All' ? null : value,
                                    );
                                  }
                                },
                              ),
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
                                  dropdownColor: Theme.of(context).dialogTheme.backgroundColor,
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
                  ],
                ),
              ),
              const SizedBox(height: 12),

              // ── Content ─────────────────────────────
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
                onPressed: () => provider.fetchEvents(
                  refresh: true,
                  languageCode: _selectedLang?.code,
                  updateLanguage: true,
                ),
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    if (provider.events.isEmpty) {
      return const Center(child: Text('No events found'));
    }

    return RefreshIndicator(
      onRefresh: () => provider.fetchEvents(
        refresh: true,
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
            onManage: ev.eventType == 'online'
                ? null
                : () {
                    final eventId = int.tryParse(ev.eventId);
                    if (eventId != null) {
                      Navigator.pushNamed(
                        context,
                        AppRoutes.manageTickets,
                        arguments: EventTicketsArgs(
                          eventId: eventId,
                          eventType: ev.eventType,
                        ),
                      );
                    }
                  },
            onEdit: () {
              final eventId = int.tryParse(ev.eventId);
              Navigator.pushNamed(
                context,
                ev.eventType == 'venue'
                    ? AppRoutes.editVenueEvent
                    : AppRoutes.editOnlineEvent,
                arguments: eventId != null
                    ? EditEventArgs(eventId: eventId)
                    : null,
              );
            },
            onTicketSettings: ev.eventType == 'online'
                ? null
                : () {
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
