import 'package:flutter/cupertino.dart';

/// Centralized color definitions used throughout the app.
class AppColors {
  // ───── Primary Theme Colors ─────
  static const Color primaryColor = Color(0xff008585);
  static const Color secondaryColor = Color(0xff00b1b6);

  // ───── Accent & Supporting Colors ─────
  static const Color colorPink = Color(0xffF83758);
  static const Color colorText = Color(0xff182D53);
  static const Color titleColor = Color(0xff222B45);

  // ───── Dark Mode Colors ─────
  static const Color darkBackground = Color(0xff121212);
  static const Color darkSurface = Color(0xff1E1E1E);
  static const Color darkOnSurface = Color(0xffE0E0E0);
  static const Color darkInputBackground = Color(0xff2C2C2C);
  static const Color darkTitleColor = Color(0xffE0E0E0);
  static const Color darkTextColor = Color(0xffB0B0B0);

  // ───── Global Gradient Style ─────
  static const LinearGradient themeGradient = LinearGradient(
    colors: [primaryColor, secondaryColor],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient darkThemeGradient = LinearGradient(
    colors: [Color(0xff1A1A1A), Color(0xff2D2D2D)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
}
