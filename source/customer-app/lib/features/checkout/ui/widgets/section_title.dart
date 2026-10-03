import 'package:flutter/material.dart';
import 'package:booktkit_customer/app/app_text_styles.dart';

class SectionTitle extends StatelessWidget {
  final String text;
  const SectionTitle(this.text, {super.key});
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 6),
        child: Text(text, style: AppTextStyles.headingSmall),
      );
}

