import 'package:booktkit_organizer/features/common/providers/currency_provider.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/income/providers/income_data_provider.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class MonthlyIncome extends StatefulWidget {
  const MonthlyIncome({super.key});

  @override
  State<MonthlyIncome> createState() => _MonthlyIncomeState();
}

class _MonthlyIncomeState extends State<MonthlyIncome> {
  int selectedYear = 2026;
  final years = [2026, 2025, 2024];

  @override
  void initState() {
    super.initState();
    final provider = context.read<IncomeProvider>();
    Future.microtask(() {
      provider.fetchIncome(year: selectedYear.toString());
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    return Scaffold(
      appBar: CustomAppBar(title: 'Monthly Total Income'),
      body: Consumer<IncomeProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading) {
            return const Padding(
              padding: EdgeInsets.all(40),
              child: Center(child: CircularProgressIndicator()),
            );
          }

          if (provider.errorMessage != null) {
            return Padding(
              padding: const EdgeInsets.all(20),
              child: Text(
                provider.errorMessage!,
                style: const TextStyle(color: Colors.red),
              ),
            );
          }

          final data = provider.incomeModel?.data;

          if (data == null) {
            return const SizedBox();
          }

          final months = data.months ?? [];
          final incomes = data.incomes ?? [];

          return SafeArea(
            child: RefreshIndicator(
              onRefresh: () async {
                await provider.fetchIncome(year: selectedYear.toString());
              },
              child: SingleChildScrollView(
                padding: EdgeInsets.all(16),
                child: Column(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: isDark ? Colors.grey.shade900 : Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.06),
                            blurRadius: 18,
                            offset: const Offset(0, 10),
                          ),
                        ],
                        border: Border.all(
                          color: isDark
                              ? Colors.grey.shade700
                              : Colors.grey.shade200,
                        ),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Monthly Total Income',
                                  style: TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w800,
                                    color: isDark
                                        ? Colors.grey.shade300
                                        : Colors.grey.shade900,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'See your earnings month by month',
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.grey.shade600,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 12),

                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 2,
                            ),
                            decoration: BoxDecoration(
                              color: isDark
                                  ? Colors.grey.shade800
                                  : Colors.grey.shade50,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(
                                color: isDark
                                    ? Colors.grey.shade700
                                    : Colors.grey.shade200,
                              ),
                            ),
                            child: DropdownButtonHideUnderline(
                              child: DropdownButton<int>(
                                borderRadius: BorderRadius.circular(12),
                                value: selectedYear,
                                items: years
                                    .map(
                                      (y) => DropdownMenuItem<int>(
                                        value: y,
                                        child: Text(
                                          y.toString(),
                                          style: TextStyle(
                                            fontWeight: FontWeight.w700,
                                            color: isDark
                                                ? Colors.grey.shade200
                                                : Colors.grey.shade900,
                                          ),
                                        ),
                                      ),
                                    )
                                    .toList(),
                                onChanged: (v) {
                                  if (v == null) return;
                                  setState(() => selectedYear = v);
                                  context.read<IncomeProvider>().fetchIncome(
                                    year: v.toString(),
                                  );
                                },
                                icon: Icon(
                                  Icons.keyboard_arrow_down_rounded,
                                  color: isDark
                                      ? Colors.grey.shade200
                                      : Colors.grey.shade700,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 10,
                      ),
                      decoration: BoxDecoration(
                        color: isDark
                            ? Colors.grey.shade900
                            : Colors.grey.shade100,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(
                          color: isDark
                              ? Colors.grey.shade700
                              : Colors.grey.shade200,
                        ),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Text(
                              'Month',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w800,
                                color: isDark
                                    ? Colors.grey.shade300
                                    : Colors.grey.shade700,
                                letterSpacing: 0.2,
                              ),
                            ),
                          ),
                          Text(
                            'Income',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w800,
                              color: isDark
                                  ? Colors.grey.shade300
                                  : Colors.grey.shade700,
                              letterSpacing: 0.2,
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 10),
                    ListView.separated(
                      shrinkWrap: true,
                      physics: NeverScrollableScrollPhysics(),
                      itemCount: months.length,
                      separatorBuilder: (_, _) => const SizedBox(height: 10),
                      itemBuilder: (context, index) {
                        final month = months[index];
                        final income = index < incomes.length
                            ? incomes[index]
                            : 0.0;

                        final bool isNegative = income < 0;
                        final bool isZero = income == 0;

                        final Color pillBg = isNegative
                            ? Colors.red.withValues(alpha: 0.10)
                            : isZero
                            ? Colors.grey.withValues(alpha: 0.12)
                            : Colors.green.withValues(alpha: 0.12);

                        final Color pillFg = isNegative
                            ? Colors.red.shade700
                            : isZero
                            ? Colors.grey.shade700
                            : Colors.green.shade700;

                        final String amountText =
                            context.read<CurrencyProvider>().format(income);

                        return Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: isDark ? Colors.grey.shade900 : Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: isDark
                                  ? Colors.grey.shade700
                                  : Colors.grey.shade200,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.05),
                                blurRadius: 16,
                                offset: const Offset(0, 10),
                              ),
                            ],
                          ),
                          child: Row(
                            children: [
                              // Left: month
                              Expanded(
                                child: Row(
                                  children: [
                                    Container(
                                      width: 10,
                                      height: 10,
                                      decoration: BoxDecoration(
                                        color: isNegative
                                            ? Colors.red.shade400
                                            : isZero
                                            ? Colors.grey.shade400
                                            : Colors.green.shade400,
                                        shape: BoxShape.circle,
                                      ),
                                    ),
                                    const SizedBox(width: 10),
                                    Text(
                                      month,
                                      style: TextStyle(
                                        fontSize: 14,
                                        fontWeight: FontWeight.w800,
                                        color: isDark
                                            ? Colors.grey.shade300
                                            : Colors.grey.shade900,
                                      ),
                                    ),
                                  ],
                                ),
                              ),

                              // Right: income pill
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 12,
                                  vertical: 8,
                                ),
                                decoration: BoxDecoration(
                                  color: pillBg,
                                  borderRadius: BorderRadius.circular(999),
                                  border: Border.all(
                                    color: pillFg.withValues(alpha: 0.18),
                                  ),
                                ),
                                child: Text(
                                  amountText,
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w900,
                                    color: pillFg,
                                  ),
                                ),
                              ),
                            ],
                          ),
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
