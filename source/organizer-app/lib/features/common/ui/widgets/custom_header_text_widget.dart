import 'package:booktkit_organizer/app/app_colors.dart';
import 'package:flutter/material.dart';

/// A reusable widget for section headers with consistent styling.
class CustomHeaderTextWidget extends StatelessWidget {
  final String text;
  final double fontSize;
  final FontWeight fontWeight;
  final TextAlign? textAlign;

  const CustomHeaderTextWidget({
    super.key,
    required this.text,
    this.fontSize = 16,
    this.fontWeight = FontWeight.w600,
    this.textAlign,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    return Text(
      text,
      textAlign: textAlign,
      style: TextStyle(
        fontSize: fontSize,
        fontWeight: fontWeight,
        color: isDark ? Colors.grey.shade200 : AppColors.colorText,
      ),
    );
  }
}
