import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/transactions/data/models/transactions_model.dart';
import 'package:booktkit_organizer/features/transactions/providers/transactions_provider.dart';
import 'package:booktkit_organizer/features/transactions/ui/widgets/transactions_card.dart';
import 'package:booktkit_organizer/utils/app_logger.dart';
import 'package:booktkit_organizer/utils/number_formatter.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class Transactions extends StatefulWidget {
  const Transactions({super.key});

  @override
  State<Transactions> createState() => _TransactionsState();
}

class _TransactionsState extends State<Transactions> {
  final TextEditingController _searchController = TextEditingController();
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<TransactionsProvider>().fetchTransactions().then((_) {
        _checkIfNeedMoreData();
      });
    });
  }

  void _checkIfNeedMoreData() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        final position = _scrollController.position;
        final provider = context.read<TransactionsProvider>();

        if (position.maxScrollExtent == 0 &&
            provider.hasMore &&
            !provider.isLoadingMore) {
          AppLogger.d('💳 Content not scrollable, auto-loading next page...');
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
      final provider = context.read<TransactionsProvider>();
      if (!provider.isLoadingMore && provider.hasMore) {
        AppLogger.d('⬇️ Calling fetchNextPage()');
        provider.fetchNextPage();
      }
    }
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBar(title: 'Transactions'),
      body: SafeArea(
        child: Consumer<TransactionsProvider>(
          builder: (context, provider, child) {
            if (provider.isLoading) {
              return const Center(child: CircularProgressIndicator());
            }
            if (provider.errorMessage != null) {
              return Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      Icons.error_outline,
                      size: 64,
                      color: Colors.red.withValues(alpha: 0.7),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      provider.errorMessage!,
                      style: TextStyle(color: Colors.red, fontSize: 16),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 24),
                    ElevatedButton.icon(
                      onPressed: () {
                        provider.fetchTransactions();
                      },
                      icon: const Icon(Icons.refresh),
                      label: Text('Retry'),
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 24,
                          vertical: 12,
                        ),
                      ),
                    ),
                  ],
                ),
              );
            }
            if (provider.transactions.isEmpty) {
              return Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      Icons.receipt_long_outlined,
                      size: 64,
                      color: Colors.grey.shade600,
                    ),
                    const SizedBox(height: 16),
                    Text(
                      'No transactions found',
                      style: TextStyle(
                        color: Colors.grey.shade600,
                        fontSize: 16,
                      ),
                    ),
                  ],
                ),
              );
            }
            return RefreshIndicator(
              onRefresh: () async {
                await provider.fetchTransactions(refresh: true);
                _checkIfNeedMoreData();
              },
              child: SingleChildScrollView(
                controller: _scrollController,
                padding: EdgeInsets.all(16),
                child: Column(
                  children: [
                    TextField(
                      decoration: InputDecoration(
                        labelText: 'Search Transactions',
                        prefixIcon: Icon(Icons.search),
                      ),
                    ),
                    SizedBox(height: 16),
                    ListView.separated(
                      separatorBuilder: (context, index) =>
                          SizedBox(height: 12),
                      shrinkWrap: true,
                      physics: NeverScrollableScrollPhysics(),
                      itemCount: provider.transactions.length,
                      itemBuilder: (context, index) {
                        final transaction = provider.transactions[index];
                        return TransactionCard(
                          transactionId: transaction.transcationId.toString(),
                          transactionType: transaction.transcationType,
                          paymentMethod: transaction.paymentMethod,
                          preBalance: _fmtTxCompact(transaction.preBalance, transaction),
                          amount: _fmtTxCompact(transaction.grandTotal, transaction),
                          afterBalance: _fmtTxCompact(transaction.afterBalance, transaction),
                          exactPreBalance: _needsCompactTx(transaction.preBalance)
                              ? _fmtTx(transaction.preBalance, transaction)
                              : null,
                          exactAmount: _needsCompactTx(transaction.grandTotal)
                              ? _fmtTx(transaction.grandTotal, transaction)
                              : null,
                          exactAfterBalance: _needsCompactTx(transaction.afterBalance)
                              ? _fmtTx(transaction.afterBalance, transaction)
                              : null,
                          status: transaction.paymentStatus,
                        );
                      },
                    ),
                    if (provider.isLoadingMore) ...[
                      SizedBox(height: 16),
                      Center(child: CircularProgressIndicator()),
                    ],
                    if (provider.hasMore == false) ...[
                      SizedBox(height: 16),
                      Text('No More Transactions'),
                    ],
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }

  String _fmtTx(String value, TransactionItem t) {
    final raw = value.trim();
    final sign = raw.startsWith('+') ? '+' : (raw.startsWith('-') ? '-' : '');
    final n = NumberFormatter.parseNum(raw);
    final numStr = n.toStringAsFixed(2);
    return t.currencySymbolPosition == 'right'
        ? '$sign$numStr${t.currencySymbol}'
        : '$sign${t.currencySymbol}$numStr';
  }

  String _fmtTxCompact(String value, TransactionItem t) {
    final raw = value.trim();
    final sign = raw.startsWith('+') ? '+' : (raw.startsWith('-') ? '-' : '');
    final n = NumberFormatter.parseNum(raw);
    if (!NumberFormatter.needsCompact(n)) return _fmtTx(value, t);
    final compactNum = NumberFormatter.compact(n);
    return t.currencySymbolPosition == 'right'
        ? '$sign$compactNum${t.currencySymbol}'
        : '$sign${t.currencySymbol}$compactNum';
  }

  bool _needsCompactTx(String value) =>
      NumberFormatter.needsCompact(NumberFormatter.parseNum(value));
}
