import 'package:booktkit_organizer/app/app_routes.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_button_widget.dart';
import 'package:booktkit_organizer/features/nav_appbar/ui/widgets/app_text_styles.dart';
import 'package:booktkit_organizer/features/withdraw/data/models/withdraws_model.dart';
import 'package:booktkit_organizer/features/withdraw/providers/withdraw_provider.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_snackbar.dart';
import 'package:booktkit_organizer/features/withdraw/ui/widgets/withdraw_history_card.dart';
import 'package:booktkit_organizer/utils/number_formatter.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class WithdrawScreen extends StatefulWidget {
  const WithdrawScreen({super.key});

  @override
  State<WithdrawScreen> createState() => _WithdrawScreenState();
}

class _WithdrawScreenState extends State<WithdrawScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    final provider = context.read<WithdrawProvider>();
    Future.microtask(() => provider.fetchWithdrawals());
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBar(title: 'Withdraws'),
      body: Consumer<WithdrawProvider>(
        builder: (context, provider, _) {
          return RefreshIndicator(
            onRefresh: () => provider.fetchWithdrawals(),
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              physics: const AlwaysScrollableScrollPhysics(),
              child: Column(
                children: [
                  // ---------- Balance ----------
                  Text('Your Balance', style: AppTextStyles.bodyLargeGrey),
                  Builder(
                    builder: (context) {
                      final balanceNum =
                          NumberFormatter.parseNum(provider.balance);
                      final compactVal =
                          NumberFormatter.needsCompact(balanceNum)
                          ? _fmtCompact(context, provider.balance)
                          : (provider.currencyInfo?.format(provider.balance) ??
                                provider.balance);
                      final exactVal = NumberFormatter.needsCompact(balanceNum)
                          ? (provider.currencyInfo?.format(provider.balance) ??
                                provider.balance)
                          : null;
                      return Column(
                        children: [
                          Text(compactVal, style: AppTextStyles.bodyLargeGrey),
                          if (exactVal != null)
                            Text(
                              exactVal,
                              style: AppTextStyles.bodyLargeGrey.copyWith(
                                fontSize: 12,
                                color: Colors.grey.shade500,
                              ),
                            ),
                        ],
                      );
                    },
                  ),

                  const SizedBox(height: 16),

                  // ---------- Withdraw Now button ----------
                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: ElevatedButton(
                      onPressed: () {
                        Navigator.pushNamed(
                          context,
                          AppRoutes.withdrawRequest,
                        ).then((_) => provider.fetchWithdrawals());
                      },
                      child: const Text('Withdraw Now!'),
                    ),
                  ),
                  const SizedBox(height: 16),

                  // ---------- Search ----------
                  TextField(
                    controller: _searchController,
                    onChanged: (v) =>
                        setState(() => _searchQuery = v.toLowerCase()),
                    decoration: InputDecoration(
                      hintText: 'Search withdrawals',
                      prefixIcon: const Icon(Icons.search),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),

                  // ---------- Body ----------
                  if (provider.isLoading)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 60),
                      child: Center(child: CircularProgressIndicator()),
                    )
                  else if (provider.errorMessage != null)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 40),
                      child: Column(
                        children: [
                          Icon(
                            Icons.error_outline_rounded,
                            size: 48,
                            color: Colors.red.shade300,
                          ),
                          const SizedBox(height: 12),
                          Text(
                            provider.errorMessage!,
                            textAlign: TextAlign.center,
                            style: const TextStyle(color: Colors.red),
                          ),
                          const SizedBox(height: 16),
                          ElevatedButton(
                            onPressed: provider.fetchWithdrawals,
                            child: const Text('Retry'),
                          ),
                        ],
                      ),
                    )
                  else
                    _buildList(context, provider.withdrawals),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildList(BuildContext context, List<WithdrawData> all) {
    final filtered = _searchQuery.isEmpty
        ? all
        : all.where((w) {
            return w.withdrawId.toLowerCase().contains(_searchQuery) ||
                w.method.name.toLowerCase().contains(_searchQuery) ||
                w.status.toLowerCase().contains(_searchQuery);
          }).toList();

    if (filtered.isEmpty) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 60),
        child: Center(
          child: Text(
            'No withdrawal history found.',
            style: TextStyle(color: Colors.grey),
          ),
        ),
      );
    }

    return ListView.separated(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: filtered.length,
      separatorBuilder: (_, _) => const SizedBox(height: 12),
      itemBuilder: (context, i) {
        final w = filtered[i];
        return WithdrawCard(
          withdrawId: w.withdrawId,
          methodName: w.method.name,
          totalAmount: _fmtCompact(context, w.amount),
          totalCharge: _fmtCompact(context, w.totalCharge),
          receivableAmount: _fmtCompact(context, w.payableAmount),
          exactTotalAmount: _needsCompact(w.amount)
              ? _fmt(context, w.amount)
              : null,
          exactTotalCharge: _needsCompact(w.totalCharge)
              ? _fmt(context, w.totalCharge)
              : null,
          exactReceivableAmount: _needsCompact(w.payableAmount)
              ? _fmt(context, w.payableAmount)
              : null,
          status: w.status,
          onTap: () => _showDetails(context, w),
          onDelete: () async {
            final confirm = await showDialog<bool>(
              context: context,
              builder: (ctx) => AlertDialog(
                title: const Text('Delete Withdrawal'),
                content: const Text(
                  'Are you sure you want to delete this withdrawal request?',
                ),
                actions: [
                  TextButton(
                    onPressed: () => Navigator.pop(ctx, false),
                    child: const Text('Cancel'),
                  ),
                  TextButton(
                    onPressed: () => Navigator.pop(ctx, true),
                    child: const Text(
                      'Delete',
                      style: TextStyle(color: Colors.red),
                    ),
                  ),
                ],
              ),
            );

            if (confirm == true && context.mounted) {
              // Show loading overlay or rely on provider state
              final success = await context
                  .read<WithdrawProvider>()
                  .deleteWithdrawal(w.id);

              if (context.mounted) {
                CustomSnackBar.show(
                  context: context,
                  message: success
                      ? 'Withdrawal deleted successfully'
                      : context.read<WithdrawProvider>().deleteError ??
                            'Failed to delete',
                  type: success ? SnackBarType.success : SnackBarType.error,
                );
              }
            }
          },
        );
      },
    );
  }

  // -------------------- DETAILS DIALOG --------------------

  void _showDetails(BuildContext context, WithdrawData w) {
    showDialog(
      context: context,
      barrierDismissible: true,
      builder: (ctx) {
        return Dialog(
          insetPadding: const EdgeInsets.symmetric(horizontal: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
          ),
          child: Padding(
            padding: const EdgeInsets.all(18),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: Theme.of(
                          ctx,
                        ).colorScheme.primary.withValues(alpha: 0.10),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: Icon(
                        Icons.account_balance_wallet_rounded,
                        color: Theme.of(ctx).colorScheme.primary,
                      ),
                    ),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Text(
                        'Withdraw Details',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                    IconButton(
                      onPressed: () => Navigator.pop(ctx),
                      icon: const Icon(Icons.close_rounded),
                    ),
                  ],
                ),
                const SizedBox(height: 14),

                _detailRow('Withdraw ID', '#${w.withdrawId}'),
                _detailRow('Method', w.method.name),
                _detailRow('Total Amount', _fmt(context, w.amount)),
                _detailRow('Charge', _fmt(context, w.totalCharge)),
                _detailRow('Receivable', _fmt(context, w.payableAmount)),
                if (w.additionalReference != null &&
                    w.additionalReference!.isNotEmpty)
                  _detailRow('Reference', w.additionalReference!),

                const SizedBox(height: 10),

                // Status chip
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Status',
                      style: TextStyle(
                        fontSize: 13,
                        color: Colors.grey.shade600,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    _statusChip(ctx, w.status),
                  ],
                ),

                const SizedBox(height: 18),
                SizedBox(
                  width: double.infinity,
                  height: 46,
                  child: CustomButtonWidget(
                    fontSize: 16,
                    onPressed: () => Navigator.pop(ctx),
                    text: 'Close',
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _detailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Expanded(
            child: Text(
              label,
              style: TextStyle(
                fontSize: 13,
                color: Colors.grey.shade600,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800),
            ),
          ),
        ],
      ),
    );
  }

  Widget _statusChip(BuildContext context, String status) {
    final info = _statusInfo(status, context);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: info.bg,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: info.border),
      ),
      child: Text(
        info.text,
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w800,
          color: info.fg,
        ),
      ),
    );
  }

  String _fmt(BuildContext context, dynamic value) {
    final info = context.read<WithdrawProvider>().currencyInfo;
    if (info != null) return info.format(value);
    final n = NumberFormatter.parseNum(value);
    return n.toStringAsFixed(2);
  }

  String _fmtCompact(BuildContext context, dynamic value) {
    final info = context.read<WithdrawProvider>().currencyInfo;
    final n = NumberFormatter.parseNum(value);
    if (NumberFormatter.needsCompact(n)) {
      return info?.formatCompact(value) ?? NumberFormatter.compact(n);
    }
    return _fmt(context, value);
  }

  bool _needsCompact(dynamic value) {
    return NumberFormatter.needsCompact(NumberFormatter.parseNum(value));
  }

  _StatusInfo _statusInfo(String status, BuildContext context) {
    switch (status.trim().toLowerCase()) {
      case '0':
      case 'pending':
        return _StatusInfo(
          text: 'Pending',
          fg: Colors.orange.shade800,
          bg: Colors.orange.shade50,
          border: Colors.orange.shade200,
        );
      case 'approved':
      case '1':
      case 'success':
        return _StatusInfo(
          text: 'Approved',
          fg: Colors.green.shade800,
          bg: Colors.green.shade50,
          border: Colors.green.shade200,
        );
      case 'rejected':
      case 'decline':
      case '2':
        return _StatusInfo(
          text: 'Declined',
          fg: Colors.red.shade800,
          bg: Colors.red.shade50,
          border: Colors.red.shade200,
        );
      default:
        return _StatusInfo(
          text: status,
          fg: Theme.of(context).colorScheme.onSurface,
          bg: Theme.of(context).colorScheme.surface,
          border: Colors.grey.shade300,
        );
    }
  }
}

class _StatusInfo {
  final String text;
  final Color fg;
  final Color bg;
  final Color border;

  _StatusInfo({
    required this.text,
    required this.fg,
    required this.bg,
    required this.border,
  });
}
