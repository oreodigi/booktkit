import 'package:booktkit_organizer/app/app_routes.dart';
import 'package:booktkit_organizer/app/assets_path.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_header_text_widget.dart';
import 'package:booktkit_organizer/features/dashboard/providers/dashboard_provider.dart';
import 'package:booktkit_organizer/features/dashboard/ui/widgets/income_chart_widget.dart';
import 'package:booktkit_organizer/features/nav_appbar/ui/widgets/app_text_styles.dart';
import 'package:booktkit_organizer/utils/number_formatter.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:provider/provider.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  @override
  void initState() {
    super.initState();
    // Fetch dashboard data when screen loads
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<DashboardProvider>().fetchDashboardData();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Consumer<DashboardProvider>(
          builder: (context, dashboardProvider, child) {
            if (dashboardProvider.isLoading) {
              return const Center(child: CircularProgressIndicator());
            }
            if (dashboardProvider.errorMessage != null) {
              return Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      dashboardProvider.errorMessage!,
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: Colors.red),
                    ),
                    const SizedBox(height: 16),
                    Padding(
                      padding: const EdgeInsets.all(16.0),
                      child: ElevatedButton(
                        onPressed: () => dashboardProvider.refreshDashboard(),
                        child: Text('Retry'),
                      ),
                    ),
                  ],
                ),
              );
            }
            final data = dashboardProvider.dashboardData;
            if (data == null) {
              return Center(child: Text('No data available'));
            }
            return RefreshIndicator(
              onRefresh: () => dashboardProvider.refreshDashboard(),
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    SummaryTiles(
                      icon: AssetsPath.walletSvg,
                      cardValue: data.formattedBalanceCompact,
                      cardTitle: 'My Balance',
                      exactValue:
                          NumberFormatter.needsCompact(
                            NumberFormatter.parseNum(data.totalBalance),
                          )
                          ? data.exactFormattedBalance
                          : null,
                      onTap: () {
                        Navigator.pushNamed(context, AppRoutes.monthlyIncome);
                      },
                    ),
                    SizedBox(height: 8),
                    SummaryTiles(
                      icon: AssetsPath.events,
                      cardValue: NumberFormatter.compact(data.totalEvents),
                      cardTitle: 'Total Event(s)',
                      exactValue: NumberFormatter.needsCompact(data.totalEvents)
                          ? data.totalEvents.toString()
                          : null,
                      onTap: () {
                        Navigator.pushNamed(context, AppRoutes.allEvents);
                      },
                    ),
                    SizedBox(height: 8),

                    SummaryTiles(
                      icon: AssetsPath.bookings,
                      cardValue: NumberFormatter.compact(
                        data.totalEventBookings,
                      ),
                      cardTitle: 'Total Event Booking(s)',
                      exactValue:
                          NumberFormatter.needsCompact(data.totalEventBookings)
                          ? data.totalEventBookings.toString()
                          : null,
                      onTap: () {
                        Navigator.pushNamed(context, AppRoutes.allBookings);
                      },
                    ),
                    SizedBox(height: 8),

                    SummaryTiles(
                      icon: AssetsPath.transactions,
                      cardValue: NumberFormatter.compact(data.transactionCount),
                      cardTitle: 'Total Transaction(s)',
                      exactValue:
                          NumberFormatter.needsCompact(data.transactionCount)
                          ? data.transactionCount.toString()
                          : null,
                      onTap: () {
                        Navigator.pushNamed(context, AppRoutes.transactions);
                      },
                    ),
                    const SizedBox(height: 16),
                    CustomHeaderTextWidget(
                      text:
                          'Event Booking Monthly Income (${DateTime.now().year})',
                    ),
                    const SizedBox(height: 20),
                    IncomeChartWidget(
                      monthArr: data.eventMonths,
                      monthlyData: data.eventIncomes,
                    ),
                    const SizedBox(height: 16),
                    CustomHeaderTextWidget(
                      text: 'Monthly Event Bookings (${DateTime.now().year})',
                    ),
                    const SizedBox(height: 20),
                    IncomeChartWidget(
                      monthArr: data.eventMonths,
                      monthlyData: data.totalBookings,
                    ),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}

class SummaryTiles extends StatelessWidget {
  final String icon;
  final String cardValue;
  final String cardTitle;
  final VoidCallback onTap;
  final String? exactValue;

  const SummaryTiles({
    super.key,
    required this.icon,
    required this.cardValue,
    required this.cardTitle,
    required this.onTap,
    this.exactValue,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return GestureDetector(
      onTap: onTap,
      child: Card(
        elevation: 0.5,
        child: Padding(
          padding: const EdgeInsets.all(12.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(10),
                      color: isDark ? Colors.grey.shade800 : Colors.white,
                    ),
                    padding: EdgeInsets.all(8),
                    width: 50,
                    height: 50,
                    child: SvgPicture.asset(icon),
                  ),
                  SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          cardValue,
                          style: AppTextStyles.headingLarge.copyWith(
                            fontSize: 26,
                            color: isDark ? Colors.white : null,
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                        if (exactValue != null) ...[
                          SizedBox(height: 2),
                          Text(
                            exactValue!,
                            style: TextStyle(
                              fontSize: 12,
                              color: isDark
                                  ? Colors.white54
                                  : Colors.grey.shade600,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ],
                    ),
                  ),
                ],
              ),
              SizedBox(height: 12),
              Text(
                cardTitle,
                style: AppTextStyles.headingLarge.copyWith(
                  fontWeight: FontWeight.w600,
                  fontSize: 20,
                  color: isDark ? Colors.white70 : null,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
