import 'package:booktkit_organizer/app/app_routes.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_snackbar.dart';
import 'package:booktkit_organizer/features/event_bookings/providers/event_bookings_provider.dart';
import 'package:booktkit_organizer/features/event_bookings/ui/widgets/booking_card.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class PendingBookings extends StatefulWidget {
  const PendingBookings({super.key});

  @override
  State<PendingBookings> createState() => _PendingBookingsState();
}

class _PendingBookingsState extends State<PendingBookings> {
  final ScrollController _scrollController = ScrollController();
  final List<String> statusOptions = [
    'All',
    'Completed',
    'Pending',
    'Rejected',
  ];

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<EventBookingsProvider>().fetchBookings(
        refresh: true,
        status: 'pending',
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
      final provider = context.read<EventBookingsProvider>();
      if (!provider.isLoadingMore && provider.hasMore) {
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
      appBar: CustomAppBar(title: 'Pending Bookings'),
      body: Consumer<EventBookingsProvider>(
        builder: (context, provider, _) {
          final displayStatus = provider.statusFilter == null
              ? 'All'
              : provider.statusFilter![0].toUpperCase() +
                    provider.statusFilter!.substring(1);

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
                            hintText: 'Search...',
                            prefixIcon: Icon(Icons.search),
                          ),
                          onSubmitted: (val) => provider.setSearchQuery(val),
                        ),
                      ),
                    ),
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
                          child: DropdownButton<String>(
                            value: displayStatus,
                            borderRadius: BorderRadius.circular(12),
                            dropdownColor: Theme.of(
                              context,
                            ).dialogTheme.backgroundColor,
                            items: statusOptions.map((item) {
                              return DropdownMenuItem<String>(
                                value: item,
                                child: Text(item),
                              );
                            }).toList(),
                            onChanged: (value) {
                              if (value != null) {
                                provider.setStatusFilter(
                                  value == 'All' ? null : value.toLowerCase(),
                                );
                              }
                            },
                          ),
                        ),
                      ),
                    ),
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

  Widget _buildContent(BuildContext context, EventBookingsProvider provider) {
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
                onPressed: () =>
                    provider.fetchBookings(refresh: true, status: 'pending'),
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    if (provider.bookings.isEmpty) {
      return const Center(child: Text('No bookings found for this filter'));
    }

    return RefreshIndicator(
      onRefresh: () => provider.fetchBookings(
        refresh: true,
        status: provider.statusFilter ?? 'pending',
      ),
      child: ListView.separated(
        controller: _scrollController,
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        physics: const AlwaysScrollableScrollPhysics(),
        itemCount:
            provider.bookings.length +
            (provider.isLoadingMore ? 1 : 0) +
            (!provider.hasMore && provider.bookings.isNotEmpty ? 1 : 0),
        separatorBuilder: (_, _) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          if (index == provider.bookings.length && provider.isLoadingMore) {
            return const Padding(
              padding: EdgeInsets.all(16),
              child: Center(child: CircularProgressIndicator()),
            );
          }
          if (index == provider.bookings.length && !provider.hasMore) {
            return Padding(
              padding: const EdgeInsets.all(16),
              child: Center(
                child: Text(
                  'No more bookings',
                  style: TextStyle(color: Colors.grey[600], fontSize: 14),
                ),
              ),
            );
          }
          final b = provider.bookings[index];
          return BookingCard(
            bookingId: b.bookingIdString ?? '#${b.id}',
            eventTitle: 'Event Title: ${b.eventTitle}',
            customerName: b.customerName,
            customerPaid: b.customerPaidStr ?? '0.00',
            organizerReceived: b.orgReceivedStr ?? '0.00',
            paidVia: b.paymentMethod ?? b.gatewayType ?? 'N/A',
            paymentStatus: b.paymentStatus,
            scannedCount: b.scannedCount,
            totalTickets: b.totalTickets,
            onTap: () => Navigator.pushNamed(
              context,
              AppRoutes.bookingDetails,
              arguments: BookingDetailsArgs(bookingId: b.id.toString()),
            ),
            onDetails: () => Navigator.pushNamed(
              context,
              AppRoutes.bookingDetails,
              arguments: BookingDetailsArgs(bookingId: b.id.toString()),
            ),
            onInvoice: () => CustomSnackBar.show(
              context: context,
              message: 'Invoice Downloading',
            ),
            onDelete: () => _confirmDelete(context, b.id),
          );
        },
      ),
    );
  }

  Future<void> _confirmDelete(BuildContext ctx, int bookingId) async {
    final provider = ctx.read<EventBookingsProvider>();
    final confirmed = await showDialog<bool>(
      context: ctx,
      builder: (c) => AlertDialog(
        title: const Text('Delete Booking'),
        content: const Text(
          'Are you sure you want to delete this booking? This action cannot be undone.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(c, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(c, true),
            child: const Text('Delete', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;
    final success = await provider.deleteBooking(bookingId);
    if (!mounted) return;
    if (success) {
      CustomSnackBar.show(
        context: context,
        message: 'Booking deleted successfully',
      );
    } else {
      CustomSnackBar.show(
        type: SnackBarType.error,
        context: context,
        message: provider.errorMessage ?? 'Failed to delete booking',
      );
    }
  }
}
