import 'package:booktkit_organizer/app/app_routes.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_snackbar.dart';
import 'package:booktkit_organizer/features/event_bookings/providers/event_bookings_provider.dart';
import 'package:booktkit_organizer/features/event_bookings/ui/widgets/booking_card.dart';
import 'package:booktkit_organizer/utils/app_logger.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class AllBookings extends StatefulWidget {
  const AllBookings({super.key});

  @override
  State<AllBookings> createState() => _AllBookingsState();
}

class _AllBookingsState extends State<AllBookings> {
  final ScrollController _scrollController = ScrollController();

  List<String> get paymentStatus => ['All', 'Completed', 'Pending', 'Rejected'];

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<EventBookingsProvider>().fetchBookings(refresh: true).then((
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
        final provider = context.read<EventBookingsProvider>();

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
    if (!_scrollController.hasClients) return;
    final position = _scrollController.position;
    final maxScroll = position.maxScrollExtent;
    final currentScroll = position.pixels;

    final threshold = maxScroll > 200 ? maxScroll - 200 : maxScroll * 0.8;

    if (currentScroll >= threshold) {
      final provider = context.read<EventBookingsProvider>();
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
      appBar: CustomAppBar(title: 'All Bookings'),
      body: Consumer<EventBookingsProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          if (provider.errorMessage != null) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
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
                      onPressed: () => provider.fetchBookings(refresh: true),
                      child: const Text('Retry'),
                    ),
                  ],
                ),
              ),
            );
          }

          final displayStatus = provider.statusFilter == null
              ? 'All'
              : provider.statusFilter![0].toUpperCase() +
                    provider.statusFilter!.substring(1);

          return RefreshIndicator(
            onRefresh: () async {
              await provider.fetchBookings(refresh: true);
              _checkIfNeedMoreData();
            },
            child: SingleChildScrollView(
              controller: _scrollController,
              padding: const EdgeInsets.all(16),
              physics: const AlwaysScrollableScrollPhysics(),
              child: Column(
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: SizedBox(
                          height: 50,
                          width: double.infinity,
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
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          decoration: BoxDecoration(
                            border: Border.all(color: Colors.grey.shade300),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: DropdownButtonHideUnderline(
                            child: DropdownButton<String>(
                              value: displayStatus,
                              hint: const Text('Status'),
                              borderRadius: BorderRadius.circular(12),
                              dropdownColor: Theme.of(
                                context,
                              ).dialogTheme.backgroundColor,
                              items: paymentStatus.map((item) {
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
                  const SizedBox(height: 16),
                  if (provider.bookings.isEmpty)
                    const Padding(
                      padding: EdgeInsets.only(top: 40),
                      child: Center(child: Text('No bookings found')),
                    )
                  else
                    ListView.separated(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount:
                          provider.bookings.length +
                          (provider.isLoadingMore ? 1 : 0) +
                          (!provider.hasMore && provider.bookings.isNotEmpty
                              ? 1
                              : 0),
                      separatorBuilder: (context, index) =>
                          const SizedBox(height: 12),
                      itemBuilder: (context, index) {
                        if (index == provider.bookings.length &&
                            provider.isLoadingMore) {
                          return const Padding(
                            padding: EdgeInsets.all(16.0),
                            child: Center(child: CircularProgressIndicator()),
                          );
                        }

                        if (index == provider.bookings.length &&
                            !provider.hasMore) {
                          return Padding(
                            padding: const EdgeInsets.all(16.0),
                            child: Center(
                              child: Text(
                                'No more bookings',
                                style: TextStyle(
                                  color: Colors.grey[600],
                                  fontSize: 14,
                                ),
                              ),
                            ),
                          );
                        }

                        final bookingData = provider.bookings[index];
                        return BookingCard(
                          bookingId:
                              bookingData.bookingIdString ??
                              '#${bookingData.id}',
                          // Missing explicit event title in model from JSON, using id as fallback or placeholder
                          eventTitle: 'Event Title: ${bookingData.eventTitle}',
                          customerName: bookingData.customerName,
                          customerPaid: bookingData.customerPaidStr ?? '0.00',
                          organizerReceived:
                              bookingData.orgReceivedStr ?? '0.00',
                          paidVia:
                              bookingData.paymentMethod ??
                              bookingData.gatewayType ??
                              'N/A',
                          paymentStatus: bookingData.paymentStatus,
                          scannedCount: bookingData.scannedCount,
                          totalTickets: bookingData.totalTickets,
                          onTap: () {
                            Navigator.pushNamed(
                              context,
                              AppRoutes.bookingDetails,
                              arguments: BookingDetailsArgs(
                                bookingId: bookingData.id.toString(),
                              ),
                            );
                          },
                          onDetails: () {
                            Navigator.pushNamed(
                              context,
                              AppRoutes.bookingDetails,
                              arguments: BookingDetailsArgs(
                                bookingId: bookingData.id.toString(),
                              ),
                            );
                          },
                          onInvoice: () {
                            CustomSnackBar.show(
                              context: context,
                              message: 'Invoice Downloading',
                            );
                          },
                          onDelete: () =>
                              _confirmDelete(context, bookingData.id),
                        );
                      },
                    ),
                ],
              ),
            ),
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
