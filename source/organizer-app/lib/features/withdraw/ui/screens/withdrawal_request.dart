// ignore_for_file: deprecated_member_use
import 'dart:async';
import 'package:dio/dio.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_snackbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_button_widget.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_header_text_widget.dart';
import 'package:booktkit_organizer/features/common/providers/currency_provider.dart';
import 'package:booktkit_organizer/features/withdraw/data/models/method_input_model.dart';
import 'package:booktkit_organizer/features/withdraw/data/models/withdraw_calculation_model.dart';
import 'package:booktkit_organizer/features/withdraw/data/models/withdraws_model.dart';
import 'package:booktkit_organizer/features/withdraw/providers/withdraw_provider.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class WithdrawalRequest extends StatefulWidget {
  const WithdrawalRequest({super.key});

  @override
  State<WithdrawalRequest> createState() => _WithdrawalRequestState();
}

class _WithdrawalRequestState extends State<WithdrawalRequest> {
  Method? _selectedMethod;
  final _amountController = TextEditingController();
  final _referenceController = TextEditingController();

  // Dynamic field controllers keyed by field name
  final Map<String, TextEditingController> _dynamicControllers = {};
  // For select/radio: keyed by field name -> selected value
  final Map<String, String?> _dynamicSelectValues = {};

  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<WithdrawProvider>().fetchWithdrawMethods();
    });

    _amountController.addListener(_onAmountChanged);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _amountController.removeListener(_onAmountChanged);
    _amountController.dispose();
    _referenceController.dispose();
    for (final c in _dynamicControllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  void _onAmountChanged() {
    if (_debounce?.isActive ?? false) _debounce!.cancel();
    _debounce = Timer(const Duration(milliseconds: 600), () {
      _triggerCalculation();
    });
  }

  void _triggerCalculation() {
    final method = _selectedMethod;
    final amountTxt = _amountController.text.trim();

    if (method == null || amountTxt.isEmpty) {
      context.read<WithdrawProvider>().clearCalculation();
      return;
    }

    final amountDouble = double.tryParse(amountTxt);
    if (amountDouble == null || amountDouble <= 0) {
      context.read<WithdrawProvider>().clearCalculation();
      return;
    }

    context.read<WithdrawProvider>().calculateWithdrawal(
      method.id,
      amountDouble,
    );
  }

  void _onMethodChanged(Method? m) {
    if (m == null) return;
    // Dispose old dynamic controllers
    for (final c in _dynamicControllers.values) {
      c.dispose();
    }
    _dynamicControllers.clear();
    _dynamicSelectValues.clear();

    setState(() => _selectedMethod = m);

    // Call calculate internally with new method
    _triggerCalculation();

    context.read<WithdrawProvider>().fetchMethodInputs(m.id).then((_) {
      if (!mounted) return;
      final inputs = context.read<WithdrawProvider>().methodInputs;
      setState(() {
        for (final f in inputs) {
          if (f.isTextField || f.isTextArea) {
            _dynamicControllers[f.name] = TextEditingController();
          } else if (f.isSelect || f.isRadio) {
            _dynamicSelectValues[f.name] = null;
          }
        }
      });
    });
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();

    final method = _selectedMethod;
    if (method == null) {
      _showError('Please select a withdraw method');
      return;
    }

    final amountTxt = _amountController.text.trim();
    if (amountTxt.isEmpty) {
      _showError('Please enter a withdraw amount');
      return;
    }

    final amountDouble = double.tryParse(amountTxt);
    if (amountDouble == null || amountDouble <= 0) {
      _showError('Please enter a valid amount');
      return;
    }

    final provider = context.read<WithdrawProvider>();

    // Construct the payload
    final Map<String, dynamic> data = {
      'withdraw_method': method.id,
      'withdraw_amount': amountTxt,
    };

    final ref = _referenceController.text.trim();
    if (ref.isNotEmpty) {
      data['additional_reference'] = ref;
    }

    // Collect dynamic fields
    for (final f in provider.methodInputs) {
      String? val;
      if (f.isTextField || f.isTextArea) {
        val = _dynamicControllers[f.name]?.text.trim();
      } else if (f.isSelect || f.isRadio) {
        val = _dynamicSelectValues[f.name];
      }

      if (f.required && (val == null || val.isEmpty)) {
        _showError('${f.label} is required');
        return;
      }
      if (val != null && val.isNotEmpty) {
        data[f.name] = val;
      }
    }

    final formData = FormData.fromMap(data);
    final success = await provider.submitWithdrawRequest(formData);

    if (success && mounted) {
      CustomSnackBar.show(
        context: context,
        message: 'Withdraw Request Sent Successfully!',
        type: SnackBarType.success,
      );
      Navigator.pop(context, true);
    } else if (mounted) {
      _showError(provider.submitError ?? 'Failed to submit request');
    }
  }

  void _showError(String msg) {
    CustomSnackBar.show(
      context: context,
      message: msg,
      type: SnackBarType.error,
    );
  }

  InputDecoration _dec(String hint, {IconData? icon}) {
    final theme = Theme.of(context);
    return InputDecoration(
      hintText: hint,
      prefixIcon: icon == null ? null : Icon(icon, size: 20),
      filled: true,
      fillColor: theme.colorScheme.surface,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide(color: theme.dividerColor),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide(color: theme.colorScheme.primary, width: 1.5),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: const CustomAppBar(title: 'Make Withdrawal Request'),
      body: Consumer<WithdrawProvider>(
        builder: (context, provider, _) {
          return SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // ── Method dropdown ──────────────────────────────────────
                const CustomHeaderTextWidget(text: 'Withdraw Method *'),
                const SizedBox(height: 8),
                _MethodDropdown(
                  provider: provider,
                  selectedMethod: _selectedMethod,
                  theme: theme,
                  onChanged: _onMethodChanged,
                ),

                // ── Charges info card ────────────────────────────────────
                if (_selectedMethod != null) ...[
                  const SizedBox(height: 12),
                  _ChargesCard(method: _selectedMethod!),
                ],

                // ── Dynamic fields from API ──────────────────────────────
                if (_selectedMethod != null) ...[
                  const SizedBox(height: 20),
                  if (provider.isLoadingInputs)
                    const Center(
                      child: Padding(
                        padding: EdgeInsets.symmetric(vertical: 24),
                        child: CircularProgressIndicator(),
                      ),
                    )
                  else if (provider.inputsError != null)
                    _ErrorRow(
                      message: provider.inputsError!,
                      onRetry: () =>
                          provider.fetchMethodInputs(_selectedMethod!.id),
                    )
                  else
                    ...provider.methodInputs.map(
                      (f) => Padding(
                        padding: const EdgeInsets.only(bottom: 16),
                        child: _DynamicField(
                          field: f,
                          controller: _dynamicControllers[f.name],
                          selectedValue: _dynamicSelectValues[f.name],
                          onSelectChanged: (v) =>
                              setState(() => _dynamicSelectValues[f.name] = v),
                          theme: theme,
                          dec: _dec,
                        ),
                      ),
                    ),
                ],

                const SizedBox(height: 4),

                // ── Amount ──────────────────────────────────────────────
                const CustomHeaderTextWidget(text: 'Withdraw Amount *'),
                const SizedBox(height: 8),
                TextField(
                  controller: _amountController,
                  keyboardType: const TextInputType.numberWithOptions(
                    decimal: true,
                  ),
                  decoration: _dec('Enter amount', icon: Icons.attach_money),
                ),

                // ── Calculation Result ──────────────────────────────────
                if (provider.isCalculating)
                  const Padding(
                    padding: EdgeInsets.only(top: 12),
                    child: Center(
                      child: SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      ),
                    ),
                  )
                else if (provider.calculationError != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 12),
                    child: _ErrorRow(
                      message: provider.calculationError!,
                      onRetry: _triggerCalculation,
                    ),
                  )
                else if (provider.calculationResult != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 12),
                    child: _CalculationCard(
                      result: provider.calculationResult!,
                      theme: theme,
                    ),
                  ),

                const SizedBox(height: 16),

                // ── Reference ───────────────────────────────────────────
                const CustomHeaderTextWidget(
                  text: 'Additional Reference (Optional)',
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: _referenceController,
                  decoration: _dec(
                    'Enter additional reference',
                    icon: Icons.notes_outlined,
                  ),
                ),

                const SizedBox(height: 24),

                // ── Submit ──────────────────────────────────────────────
                SizedBox(
                  height: 52,
                  child: provider.isSubmitting
                      ? const Center(child: CircularProgressIndicator())
                      : CustomButtonWidget(
                          onPressed: () => _submit(),
                          text: 'Send Request',
                          fontSize: 16,
                        ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

// ── Method Dropdown ────────────────────────────────────────────────────────
class _MethodDropdown extends StatelessWidget {
  final WithdrawProvider provider;
  final Method? selectedMethod;
  final ThemeData theme;
  final ValueChanged<Method?> onChanged;

  const _MethodDropdown({
    required this.provider,
    required this.selectedMethod,
    required this.theme,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 56,
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 12),
      decoration: BoxDecoration(
        border: Border.all(color: theme.dividerColor),
        borderRadius: BorderRadius.circular(14),
        color: theme.colorScheme.surface,
      ),
      child: provider.isLoadingMethods
          ? const Center(
              child: SizedBox(
                height: 22,
                width: 22,
                child: CircularProgressIndicator(strokeWidth: 2),
              ),
            )
          : provider.methodsError != null
          ? _ErrorRow(
              message: provider.methodsError!,
              onRetry: provider.fetchWithdrawMethods,
            )
          : DropdownButtonHideUnderline(
              child: DropdownButton<Method>(
                value: selectedMethod,
                hint: const Text('Select withdraw method'),
                isExpanded: true,
                borderRadius: BorderRadius.circular(14),
                dropdownColor: theme.colorScheme.surface,
                items: provider.methods.map((m) {
                  return DropdownMenuItem<Method>(
                    value: m,
                    child: Text(m.name),
                  );
                }).toList(),
                onChanged: onChanged,
              ),
            ),
    );
  }
}

// ── Dynamic field renderer ─────────────────────────────────────────────────
class _DynamicField extends StatelessWidget {
  final MethodInputField field;
  final TextEditingController? controller;
  final String? selectedValue;
  final ValueChanged<String?> onSelectChanged;
  final ThemeData theme;
  final InputDecoration Function(String, {IconData? icon}) dec;

  const _DynamicField({
    required this.field,
    required this.controller,
    required this.selectedValue,
    required this.onSelectChanged,
    required this.theme,
    required this.dec,
  });

  @override
  Widget build(BuildContext context) {
    final labelText = '${field.label}${field.required ? ' *' : ''}';

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CustomHeaderTextWidget(text: labelText),
        const SizedBox(height: 8),
        _buildInput(context),
      ],
    );
  }

  Widget _buildInput(BuildContext context) {
    // Textarea
    if (field.isTextArea) {
      return TextField(
        controller: controller,
        minLines: 3,
        maxLines: 5,
        decoration: dec(field.placeholder),
      );
    }

    // Select / dropdown
    if (field.isSelect && field.options.isNotEmpty) {
      return Container(
        height: 56,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        decoration: BoxDecoration(
          border: Border.all(color: theme.dividerColor),
          borderRadius: BorderRadius.circular(14),
          color: theme.colorScheme.surface,
        ),
        child: DropdownButtonHideUnderline(
          child: DropdownButton<String>(
            value: selectedValue,
            hint: Text(field.placeholder),
            isExpanded: true,
            borderRadius: BorderRadius.circular(14),
            dropdownColor: theme.colorScheme.surface,
            items: field.options.map((o) {
              return DropdownMenuItem<String>(value: o, child: Text(o));
            }).toList(),
            onChanged: onSelectChanged,
          ),
        ),
      );
    }

    // Radio
    if (field.isRadio && field.options.isNotEmpty) {
      return Column(
        children: field.options.map((o) {
          return ListTile(
            dense: true,
            contentPadding: EdgeInsets.zero,
            leading: Radio<String>(
              value: o,
              groupValue: selectedValue,
              onChanged: onSelectChanged,
            ),
            title: Text(o),
            onTap: () => onSelectChanged(o),
          );
        }).toList(),
      );
    }

    // Default: text / phone field
    return TextField(
      controller: controller,
      keyboardType: field.type == '7'
          ? TextInputType.phone
          : TextInputType.text,
      decoration: dec(field.placeholder),
    );
  }
}

// ── Charges info card ──────────────────────────────────────────────────────
class _ChargesCard extends StatelessWidget {
  final Method method;
  const _ChargesCard({required this.method});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final primary = theme.colorScheme.primary;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: primary.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: primary.withValues(alpha: 0.2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.info_outline, size: 16, color: primary),
              const SizedBox(width: 6),
              Text(
                '${method.name} — Charge Details',
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  color: primary,
                  fontSize: 13,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          _InfoRow(label: 'Fixed Charge', value: method.fixedCharge),
          const SizedBox(height: 6),
          _InfoRow(
            label: 'Percentage Charge',
            value: '${method.percentageCharge}%',
          ),
          const SizedBox(height: 6),
          _InfoRow(label: 'Min Limit', value: method.minLimit),
          const SizedBox(height: 6),
          _InfoRow(label: 'Max Limit', value: method.maxLimit),
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  const _InfoRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: theme.textTheme.bodySmall?.copyWith(
            color: theme.textTheme.bodySmall?.color?.withValues(alpha: 0.7),
          ),
        ),
        Text(
          value,
          style: theme.textTheme.bodySmall?.copyWith(
            fontWeight: FontWeight.w700,
          ),
        ),
      ],
    );
  }
}

// ── Error + Retry row ──────────────────────────────────────────────────────
class _ErrorRow extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  const _ErrorRow({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Row(
      children: [
        Icon(Icons.error_outline, color: theme.colorScheme.error, size: 18),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            message,
            style: TextStyle(color: theme.colorScheme.error, fontSize: 13),
            overflow: TextOverflow.ellipsis,
          ),
        ),
        TextButton(onPressed: onRetry, child: const Text('Retry')),
      ],
    );
  }
}

// ── Calculation Card ───────────────────────────────────────────────────────
class _CalculationCard extends StatelessWidget {
  final WithdrawCalculationModel result;
  final ThemeData theme;

  const _CalculationCard({required this.result, required this.theme});


  @override
  Widget build(BuildContext context) {
    final primary = theme.colorScheme.primary;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: primary.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: primary.withValues(alpha: 0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.calculate_outlined, size: 16, color: primary),
              const SizedBox(width: 6),
              Text(
                'Calculation Breakdown',
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  color: primary,
                  fontSize: 13,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          _InfoRow(
            label: 'Total Charge',
            value:
                '${result.totalCharge} ${context.read<CurrencyProvider>().text}',
          ),
          const SizedBox(height: 6),
          _InfoRow(
            label: 'Receive Balance',
            value:
                '${result.receiveBalance} ${context.read<CurrencyProvider>().text}',
          ),
          const SizedBox(height: 6),
          Divider(color: theme.dividerColor),
          const SizedBox(height: 6),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Current User Balance',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.textTheme.bodySmall?.color?.withValues(
                    alpha: 0.7,
                  ),
                ),
              ),
              Text(
                '${result.userBalance} ${context.read<CurrencyProvider>().text}',
                style: theme.textTheme.bodySmall?.copyWith(
                  fontWeight: FontWeight.w800,
                  color: primary,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
