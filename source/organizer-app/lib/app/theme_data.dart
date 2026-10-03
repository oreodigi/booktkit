import 'package:booktkit_organizer/app/app_colors.dart';
import 'package:flutter/material.dart';

/// App-wide theme configuration for MaterialApp (Light & Dark modes)
class AppThemeData {
  // Prevent instantiation if this class is only meant to be static
  AppThemeData._();

  /// Light theme used throughout the app
  static final ThemeData lightTheme = ThemeData(
    fontFamily: 'Inter',
    // fontFamily: GoogleFonts.inter(fontWeight: FontWeight.w700).fontFamily,
    scaffoldBackgroundColor: Colors.white,
    colorScheme: ColorScheme.fromSeed(
      seedColor: AppColors.primaryColor,
      brightness: Brightness.light,
    ),

    // ───── AppBar Styling ─────
    appBarTheme: AppBarTheme(
      elevation: 1,
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.white.withAlpha(10),
      shadowColor: Colors.grey.shade50.withAlpha(100),
    ),

    // ───── Text Button Styling ─────
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(
        foregroundColor: AppColors.primaryColor,
        textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
      ),
    ),

    // ───── Elevated Button Styling ─────
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: AppColors.primaryColor,
        foregroundColor: Colors.white,
        fixedSize: const Size.fromWidth(double.maxFinite),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
        padding: const EdgeInsets.symmetric(vertical: 16),
      ),
    ),

    // ───── Global Input Field Styling ─────
    inputDecorationTheme: InputDecorationTheme(
      fillColor: Colors.white,
      filled: true,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      hintStyle: TextStyle(
        color: Colors.grey.shade800,
        fontWeight: FontWeight.w600,
      ),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: BorderSide(color: Colors.grey.shade300),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: BorderSide(color: Colors.grey.shade300),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: BorderSide(color: Colors.grey.shade300),
      ),
    ),

    // ───── Global Text Theme ─────
    textTheme: const TextTheme(
      titleLarge: TextStyle(fontSize: 28, fontWeight: FontWeight.w700),
    ),

    // ───── Dialog Styling ─────
    dialogTheme: const DialogThemeData(backgroundColor: Colors.white),

    // ───── Dropdown Menu Styling ─────
    dropdownMenuTheme: DropdownMenuThemeData(
      menuStyle: MenuStyle(
        backgroundColor: WidgetStateProperty.all(Colors.white),
        surfaceTintColor: WidgetStateProperty.all(Colors.white),
      ),
    ),

    // ───── Popup Menu Styling ─────
    popupMenuTheme: PopupMenuThemeData(
      color: Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
    ),

    // ───── Menu Theme Styling ─────
    menuTheme: MenuThemeData(
      style: MenuStyle(
        backgroundColor: WidgetStateProperty.all(Colors.white),
        surfaceTintColor: WidgetStateProperty.all(Colors.white),
      ),
    ),

    checkboxTheme: // New, recommended code
    CheckboxThemeData(
      fillColor: WidgetStateProperty.resolveWith((states) {
        if (states.contains(WidgetState.selected)) {
          return AppColors.primaryColor;
        }
        return Colors.white;
      }),
      checkColor: WidgetStateProperty.all(Colors.white),
      overlayColor: WidgetStateProperty.all(AppColors.primaryColor),
    ),
  );

  /// Dark theme used throughout the app
  static final ThemeData darkTheme = ThemeData(
    fontFamily: 'Inter',
    scaffoldBackgroundColor: AppColors.darkBackground,
    colorScheme: ColorScheme.fromSeed(
      seedColor: AppColors.primaryColor,
      brightness: Brightness.dark,
      surface: AppColors.darkSurface,
      onSurface: AppColors.darkOnSurface,
    ),

    // ───── AppBar Styling ─────
    appBarTheme: AppBarTheme(
      elevation: 1,
      backgroundColor: AppColors.darkSurface,
      surfaceTintColor: AppColors.darkSurface.withAlpha(10),
      shadowColor: Colors.black.withAlpha(100),
    ),

    // ───── Text Button Styling ─────
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(
        foregroundColor: AppColors.primaryColor,
        textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
      ),
    ),

    // ───── Elevated Button Styling ─────
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: Colors.grey.shade700,
        foregroundColor: Colors.white,
        fixedSize: const Size.fromWidth(double.maxFinite),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
        padding: const EdgeInsets.symmetric(vertical: 16),
      ),
    ),

    // ───── Global Input Field Styling ─────
    inputDecorationTheme: InputDecorationTheme(
      fillColor: AppColors.darkInputBackground,
      filled: true,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      hintStyle: TextStyle(
        color: Colors.grey.shade400,
        fontWeight: FontWeight.w600,
      ),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: BorderSide(color: Colors.grey.shade700),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: BorderSide(color: Colors.grey.shade700),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: BorderSide(color: AppColors.primaryColor),
      ),
    ),

    // ───── Global Text Theme ─────
    textTheme: const TextTheme(
      titleLarge: TextStyle(
        fontSize: 28,
        fontWeight: FontWeight.w700,
        color: AppColors.darkOnSurface,
      ),
      bodyLarge: TextStyle(color: AppColors.darkOnSurface),
      bodyMedium: TextStyle(color: AppColors.darkOnSurface),
      bodySmall: TextStyle(color: AppColors.darkOnSurface),
    ),

    // ───── Dialog Styling ─────
    dialogTheme: const DialogThemeData(backgroundColor: AppColors.darkSurface),

    // ───── Dropdown Menu Styling ─────
    dropdownMenuTheme: DropdownMenuThemeData(
      menuStyle: MenuStyle(
        backgroundColor: WidgetStateProperty.all(AppColors.darkSurface),
        surfaceTintColor: WidgetStateProperty.all(AppColors.darkSurface),
      ),
    ),

    // ───── Popup Menu Styling ─────
    popupMenuTheme: PopupMenuThemeData(
      color: AppColors.darkSurface,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
    ),

    // ───── Menu Theme Styling ─────
    menuTheme: MenuThemeData(
      style: MenuStyle(
        backgroundColor: WidgetStateProperty.all(AppColors.darkSurface),
        surfaceTintColor: WidgetStateProperty.all(AppColors.darkSurface),
      ),
    ),

    checkboxTheme: CheckboxThemeData(
      fillColor: WidgetStateProperty.resolveWith((states) {
        if (states.contains(WidgetState.selected)) {
          return AppColors.primaryColor;
        }
        return AppColors.darkInputBackground;
      }),
      checkColor: WidgetStateProperty.all(Colors.white),
      overlayColor: WidgetStateProperty.all(AppColors.primaryColor),
    ),
  );
}
