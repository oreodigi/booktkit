import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:booktkit_customer/features/auth/providers/auth_provider.dart';
import 'package:booktkit_customer/app/app_text_styles.dart';
import 'package:booktkit_customer/features/checkout/providers/checkout_provider.dart';

class BillingDetailsSection extends StatelessWidget {
  const BillingDetailsSection({super.key});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Billing Details', style: AppTextStyles.headingMedium),
        const SizedBox(height: 8),
        Consumer2<AuthProvider, CheckoutProvider>(
          builder: (context, auth, vm, _) {
            final u = auth.customerModel;
            final loggedIn = (auth.token ?? '').isNotEmpty && u != null;
            if (loggedIn) {
              return Column(
                children: [
                  _InputRow(
                    children: [
                      _ReadOnlyField(label: 'First Name', value: u.fname ?? ''),
                      _ReadOnlyField(label: 'Last Name', value: u.lname ?? ''),
                    ],
                  ),
                  const SizedBox(height: 4),
                  _ReadOnlyField(label: 'Email', value: u.email ?? ''),
                  const SizedBox(height: 4),
                  _ReadOnlyField(label: 'Phone', value: u.phone ?? ''),
                  const SizedBox(height: 4),
                  _InputRow(
                    children: [
                      _ReadOnlyField(label: 'Country', value: u.country ?? ''),
                      _ReadOnlyField(label: 'State', value: u.state ?? ''),
                    ],
                  ),
                  const SizedBox(height: 4),
                  _InputRow(
                    children: [
                      _ReadOnlyField(label: 'City', value: u.city ?? ''),
                      _ReadOnlyField(
                        label: 'Zip/Post Code',
                        value: u.zipCode ?? '',
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  _ReadOnlyField(
                    label: 'Address',
                    value: u.address ?? '',
                    lines: 3,
                  ),
                ],
              );
            }

            // Guest checkout: editable fields bound to CheckoutViewModel
            String gv(String k) => vm.getRawField(k);
            return Column(
              children: [
                _InputRow(
                  children: [
                    _EditableField(
                      label: 'First Name',
                      initialValue: gv('fname'),
                      onChanged: (v) => vm.setRawField('fname', v),
                    ),
                    _EditableField(
                      label: 'Last Name',
                      initialValue: gv('lname'),
                      onChanged: (v) => vm.setRawField('lname', v),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                _EditableField(
                  label: 'Email',
                  initialValue: gv('email'),
                  keyboardType: TextInputType.emailAddress,
                  onChanged: (v) => vm.setRawField('email', v),
                ),
                const SizedBox(height: 4),
                _EditableField(
                  label: 'Phone',
                  initialValue: gv('phone'),
                  keyboardType: TextInputType.phone,
                  onChanged: (v) => vm.setRawField('phone', v),
                ),
                const SizedBox(height: 4),
                _InputRow(
                  children: [
                    _EditableField(
                      label: 'Country',
                      initialValue: gv('country'),
                      onChanged: (v) => vm.setRawField('country', v),
                    ),
                    _EditableField(
                      label: 'State',
                      initialValue: gv('state'),
                      onChanged: (v) => vm.setRawField('state', v),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                _InputRow(
                  children: [
                    _EditableField(
                      label: 'City',
                      initialValue: gv('city'),
                      onChanged: (v) => vm.setRawField('city', v),
                    ),
                    _EditableField(
                      label: 'Zip/Post Code',
                      initialValue: gv('zip_code'),
                      keyboardType: TextInputType.number,
                      onChanged: (v) => vm.setRawField('zip_code', v),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                _EditableField(
                  label: 'Address',
                  initialValue: gv('address'),
                  lines: 3,
                  onChanged: (v) => vm.setRawField('address', v),
                ),
              ],
            );
          },
        ),
      ],
    );
  }
}

class _InputRow extends StatelessWidget {
  final List<Widget> children;
  const _InputRow({required this.children});
  @override
  Widget build(BuildContext context) {
    return Row(
      children: children
          .map(
            (e) => Expanded(
              child: Padding(
                padding: const EdgeInsets.only(right: 8, bottom: 8),
                child: e,
              ),
            ),
          )
          .toList(),
    );
  }
}

class _ReadOnlyField extends StatelessWidget {
  final String label;
  final String value;
  final int lines;
  const _ReadOnlyField({
    required this.label,
    required this.value,
    this.lines = 1,
  });
  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: AppTextStyles.bodySmall),
        const SizedBox(height: 6),
        TextFormField(
          readOnly: true,
          initialValue: value,
          maxLines: lines,
          decoration: InputDecoration(
            isDense: true,
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 12,
              vertical: 12,
            ),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
          ),
        ),
      ],
    );
  }
}

class _EditableField extends StatelessWidget {
  final String label;
  final String initialValue;
  final int lines;
  final TextInputType? keyboardType;
  final void Function(String)? onChanged;
  const _EditableField({
    required this.label,
    required this.initialValue,
    this.lines = 1,
    this.keyboardType,
    this.onChanged,
  });
  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: AppTextStyles.bodySmall),
        const SizedBox(height: 6),
        TextFormField(
          initialValue: initialValue,
          maxLines: lines,
          keyboardType: keyboardType,
          onChanged: onChanged,
          decoration: InputDecoration(
            isDense: true,
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 12,
              vertical: 12,
            ),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
          ),
        ),
      ],
    );
  }
}
