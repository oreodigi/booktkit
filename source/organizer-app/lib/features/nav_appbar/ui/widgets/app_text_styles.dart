import 'package:booktkit_organizer/app/app_colors.dart';
import 'package:flutter/material.dart';

class AppTextStyles {
  AppTextStyles._();
  static const TextStyle headingLarge = TextStyle(
    fontSize: 24,
    fontWeight: FontWeight.bold,
    color: AppColors.colorText,
  );

  static const TextStyle headingMedium = TextStyle(
    fontSize: 20,
    fontWeight: FontWeight.w600,
    color: AppColors.colorText,
  );

  static const TextStyle headingSmall = TextStyle(
    fontSize: 18,
    fontWeight: FontWeight.w500,
    color: AppColors.colorText,
  );

  // Body text
  static const TextStyle bodyLarge = TextStyle(
    fontSize: 16,
    color: AppColors.colorText,
    fontWeight: FontWeight.w600,
  );
  static TextStyle bodyLargeGrey = TextStyle(
    fontSize: 16,
    color: Colors.grey.shade800,
    fontWeight: FontWeight.w500,
  );

  static TextStyle bodySmall = TextStyle(
    fontSize: 14,
    color: Colors.grey.shade600,
  );

  // Label / Caption
  static const TextStyle label = TextStyle(
    fontSize: 12,
    color: AppColors.colorText,
    fontWeight: FontWeight.w400,
  );

  // Example: colored text
  static const TextStyle errorText = TextStyle(
    fontSize: 14,
    color: Colors.red,
    fontWeight: FontWeight.w500,
  );
}
