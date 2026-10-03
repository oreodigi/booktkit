import 'package:booktkit_organizer/app/app_colors.dart';
import 'package:flutter/material.dart';

/// A reusable form header text widget with consistent styling.
class FormHeaderTextWidget extends StatelessWidget {
  final String text;
  final double? fontSize;
  final FontWeight? fontWeight;

  const FormHeaderTextWidget({
    super.key,
    required this.text,
    this.fontSize = 18,
    this.fontWeight = FontWeight.w500,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    return Text(
      text,
      style: TextStyle(
        fontSize: fontSize,
        fontWeight: fontWeight,
        color: isDark? Colors.grey.shade300: AppColors.colorText,
      ),
    );
  }
}
