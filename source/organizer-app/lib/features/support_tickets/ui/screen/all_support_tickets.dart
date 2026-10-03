import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/support_tickets/providers/support_tickets_provider.dart';
import 'package:booktkit_organizer/features/support_tickets/ui/screen/support_ticket_details.dart';
import 'package:booktkit_organizer/features/support_tickets/ui/widgets/support_ticket_card.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_snackbar.dart';
import 'package:booktkit_organizer/utils/app_logger.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class AllTickets extends StatefulWidget {
  const AllTickets({super.key});

  @override
  State<AllTickets> createState() => _AllTicketsState();
}

class _AllTicketsState extends State<AllTickets> {
  final ScrollController _scrollController = ScrollController();
  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<SupportTicketsProvider>().fetchTickets(refresh: true).then((
        _,
      ) {
        _checkIfNeedMoreData();
      });
    });
  }

  void _checkIfNeedMoreData() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        final position = _scrollController.position;
        final provider = context.read<SupportTicketsProvider>();

        if (position.maxScrollExtent == 0 &&
            provider.hasMore &&
            !provider.isLoadingMore) {
          AppLogger.d('🎫 Content not scrollable, auto-loading next page...');
          provider.fetchNextPage().then((_) => _checkIfNeedMoreData());
        }
      }
    });
  }

  void _onScroll() {
    final position = _scrollController.position;
    final maxScroll = position.maxScrollExtent;
    final currentScroll = position.pixels;

    final threshold = maxScroll > 200 ? maxScroll - 200 : maxScroll * 0.8;

    if (currentScroll >= threshold) {
      final provider = context.read<SupportTicketsProvider>();
      if (!provider.isLoadingMore && provider.hasMore) {
        AppLogger.d('⬇️ Calling fetchNextPage()');
        provider.fetchNextPage();
      }
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
      appBar: CustomAppBar(title: 'All Tickets'),
      body: Consumer<SupportTicketsProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          if (provider.errorMessage != null) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Text(
                      provider.errorMessage!,
                      style: const TextStyle(color: Colors.red),
                      textAlign: TextAlign.center,
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: ElevatedButton(
                      onPressed: () => provider.fetchTickets(),
                      child: Text('Retry'),
                    ),
                  ),
                ],
              ),
            );
          }

          if (provider.tickets.isEmpty) {
            return RefreshIndicator(
              triggerMode: RefreshIndicatorTriggerMode.anywhere,
              onRefresh: () async {
                await provider.fetchTickets(refresh: true);
                _checkIfNeedMoreData();
              },
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [Center(child: Text('No tickets found'))],
              ),
            );
          }
          return SafeArea(
            child: RefreshIndicator(
              onRefresh: () async {
                await provider.fetchTickets(refresh: true);
                _checkIfNeedMoreData();
              },
              child: SingleChildScrollView(
                controller: _scrollController,

                padding: EdgeInsets.all(16),
                child: Column(
                  children: [
                    TextField(
                      decoration: InputDecoration(
                        hintText: 'Search Tickets',
                        prefixIcon: Icon(Icons.search),
                      ),
                    ),
                    SizedBox(height: 16),
                    ListView.separated(
                      separatorBuilder: (context, index) =>
                          SizedBox(height: 16),
                      shrinkWrap: true,
                      itemCount:
                          provider.tickets.length +
                          (provider.isLoadingMore ? 1 : 0) +
                          (!provider.hasMore && provider.tickets.isNotEmpty
                              ? 1
                              : 0),
                      physics: NeverScrollableScrollPhysics(),

                      itemBuilder: (context, index) {
                        if (index == provider.tickets.length &&
                            provider.isLoadingMore) {
                          return const Padding(
                            padding: EdgeInsets.all(16.0),
                            child: Center(child: CircularProgressIndicator()),
                          );
                        }

                        // End of list indicator
                        if (index == provider.tickets.length &&
                            !provider.hasMore) {
                          return Padding(
                            padding: const EdgeInsets.all(16.0),
                            child: Center(
                              child: Text(
                                'No more tickets',
                                style: TextStyle(
                                  color: Colors.grey[600],
                                  fontSize: 14,
                                ),
                              ),
                            ),
                          );
                        }
                        final ticket = provider.tickets[index];
                        return TicketCard(
                          ticketId: ticket.id.toString(),
                          email: ticket.email,
                          subject: ticket.subject,
                          status: ticket.status,
                          onTap: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) =>
                                    TicketDetailsScreen(ticketId: ticket.id),
                              ),
                            );
                          },
                          onDelete: () async {
                            final confirm = await showDialog<bool>(
                              context: context,
                              builder: (context) {
                                final theme = Theme.of(context);
                                return AlertDialog(
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  contentPadding: const EdgeInsets.all(24),
                                  content: Column(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.all(16),
                                        decoration: BoxDecoration(
                                          color: Colors.red.withValues(
                                            alpha: 0.1,
                                          ),
                                          shape: BoxShape.circle,
                                        ),
                                        child: const Icon(
                                          Icons.delete_outline_rounded,
                                          color: Colors.red,
                                          size: 32,
                                        ),
                                      ),
                                      const SizedBox(height: 20),
                                      Text(
                                        'Delete Ticket?',
                                        style: theme.textTheme.titleLarge
                                            ?.copyWith(
                                              fontWeight: FontWeight.bold,
                                            ),
                                      ),
                                      const SizedBox(height: 12),
                                      Text(
                                        'Are you sure you want to delete this ticket? This action cannot be undone.',
                                        textAlign: TextAlign.center,
                                        style: theme.textTheme.bodyMedium
                                            ?.copyWith(
                                              color: theme.textTheme.bodyMedium
                                                  ?.color
                                                  ?.withValues(alpha: 0.7),
                                              height: 1.4,
                                            ),
                                      ),
                                      const SizedBox(height: 24),
                                      Row(
                                        children: [
                                          Expanded(
                                            child: OutlinedButton(
                                              onPressed: () =>
                                                  Navigator.pop(context, false),
                                              style: OutlinedButton.styleFrom(
                                                padding: const EdgeInsets
                                                    .symmetric(vertical: 14),
                                                shape: RoundedRectangleBorder(
                                                  borderRadius:
                                                      BorderRadius.circular(12),
                                                ),
                                              ),
                                              child: const Text('Cancel'),
                                            ),
                                          ),
                                          const SizedBox(width: 12),
                                          Expanded(
                                            child: FilledButton(
                                              onPressed: () =>
                                                  Navigator.pop(context, true),
                                              style: FilledButton.styleFrom(
                                                backgroundColor: Colors.red,
                                                padding: const EdgeInsets
                                                    .symmetric(vertical: 14),
                                                shape: RoundedRectangleBorder(
                                                  borderRadius:
                                                      BorderRadius.circular(12),
                                                ),
                                              ),
                                              child: const Text('Delete'),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                );
                              },
                            );

                            if (confirm == true) {
                              if (!context.mounted) return;
                              final success = await provider.deleteTicket(
                                ticket.id,
                              );
                              if (!context.mounted) return;

                              CustomSnackBar.show(
                                context: context,
                                message: success
                                    ? 'Ticket deleted successfully.'
                                    : (provider.errorMessage ?? 'Failed to delete ticket.'),
                                type: success ? SnackBarType.success : SnackBarType.error,
                              );
                            }
                          },
                        );
                      },
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
