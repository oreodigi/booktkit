import 'package:booktkit_organizer/app/app_colors.dart';
import 'package:booktkit_organizer/app/assets_path.dart';
import 'package:booktkit_organizer/app/theme_provider.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_icon_button_widgets.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

/// A reusable custom-styled app bar with optional back button, title, logo,
/// language selection, and theme toggle buttons.
class CustomAppBar extends StatefulWidget implements PreferredSizeWidget {
  /// The title text to display in the app bar.
  final String title;

  /// Optional callback for the back button. If null, it defaults to `Navigator.pop(context)`.
  final VoidCallback? onTap;

  /// Whether to show the back button. Defaults to true.
  final bool showBackButton;

  /// Path to the back button SVG icon. Defaults to `AssetsPath.backIconSvg`.
  final String icon;

  /// Path to the language button SVG icon. Defaults to `AssetsPath.languageSvg`.
  final String languageIcon;

  /// Path to the light theme button SVG icon. Defaults to `AssetsPath.lightSvg`.
  final String lightThemeIcon;

  /// Path to the dark theme button SVG icon. Defaults to `AssetsPath.darkSvg`.
  final String darkThemeIcon;

  /// Path to the app logo SVG icon. Defaults to `AssetsPath.appLogoSvg`.
  final String? appLogo;

  /// Whether to show the title text. Defaults to true.
  final bool showTitle;

  /// Whether to show the app logo. Defaults to false.
  final bool showLogo;

  /// Whether to show the language selection button. Defaults to false.
  final bool showLanguageBtn;

  /// Whether to show the theme toggle button. Defaults to false.
  final bool showThemeBtn;

  /// Optional extra action widgets shown on the right side of the AppBar.
  final List<Widget>? actions;

  const CustomAppBar({
    super.key,
    required this.title,
    this.onTap,
    this.showBackButton = true,
    this.icon = AssetsPath.backIconSvg,
    this.languageIcon = AssetsPath.languageSvg,
    this.lightThemeIcon = AssetsPath.lightSvg,
    this.darkThemeIcon = AssetsPath.darkSvg,
    this.appLogo = AssetsPath.appLogoSvg,
    this.showTitle = true,
    this.showLogo = false,
    this.showLanguageBtn = false,
    this.showThemeBtn = false,
    this.actions,
  });

  @override
  State<CustomAppBar> createState() => _CustomAppBarState();

  @override
  Size get preferredSize => const Size.fromHeight(kToolbarHeight);
}

class _CustomAppBarState extends State<CustomAppBar> {
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDarkMode = theme.brightness == Brightness.dark;
    final isRTL = Directionality.of(context) == TextDirection.rtl;

    return Container(
      // The container provides a custom background and shadow for the app bar.
      decoration: BoxDecoration(
        color: isDarkMode ? Colors.grey.shade900 : Colors.white,
        borderRadius: const BorderRadius.vertical(bottom: Radius.circular(16)),
        boxShadow: [
          BoxShadow(
            color: isDarkMode ? Colors.black54 : Colors.grey.shade100,
            blurStyle: BlurStyle.solid,
            spreadRadius: 1,
            offset: const Offset(0, 1),
          ),
        ],
      ),
      child: AppBar(
        automaticallyImplyLeading: false,
        centerTitle: true,
        elevation: 0,
        forceMaterialTransparency: true,
        foregroundColor: Colors.transparent,
        title: Stack(
          alignment: Alignment.center,
          children: [
            // Conditionally show the title text.
            if (widget.showTitle)
              Text(
                widget.title,
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w600,
                  color: isDarkMode
                      ? AppColors.darkTitleColor
                      : AppColors.titleColor,
                ),
              ),
            // Conditionally show the app logo, wrapped in a `GestureDetector` to navigate home.
            if (widget.showLogo)
              GestureDetector(
                onTap: () {},
                child: Image.asset(widget.appLogo ?? '', width: 140),
              ),
            // Conditionally show the back button on the left (RTL: right).
            if (widget.showBackButton)
              Align(
                alignment: isRTL ? Alignment.centerRight : Alignment.centerLeft,
                child: Transform(
                  alignment: Alignment.center,
                  transform: isRTL
                      ? Matrix4.rotationY(3.14159)
                      : Matrix4.identity(),
                  child: CustomIconButtonWidget(
                    assetPath: widget.icon,
                    // Use the provided `onTap` or default to `Navigator.pop`.
                    onTap: widget.onTap ?? () => Navigator.pop(context),
                  ),
                ),
              ),
            // // Conditionally show the language button on the right (RTL: left).
            // if (widget.showLanguageBtn)
            //   Align(
            //     alignment: isRTL ? Alignment.centerLeft : Alignment.centerRight,
            //     child: CustomIconButtonWidget(
            //       assetPath: widget.languageIcon,
            //       onTap: () => _showLanguageDialog(context),
            //     ),
            //   ),
            // Conditionally show the theme toggle button on the right (RTL: left).
            if (widget.showThemeBtn)
              Align(
                alignment: isRTL ? Alignment.centerLeft : Alignment.centerRight,
                child: Padding(
                  // Adjust padding if the language button is also present to prevent overlap.
                  padding: widget.showLanguageBtn
                      ? (isRTL
                            ? const EdgeInsets.only(left: 48.0)
                            : const EdgeInsets.only(right: 48.0))
                      : EdgeInsets.zero,
                  child: CustomIconButtonWidget(
                    // Dynamically switch the icon based on the current theme state.
                    assetPath: isDarkMode
                        ? widget.darkThemeIcon
                        : widget.lightThemeIcon,
                    onTap: () {
                      // Toggle the theme using the ThemeProvider
                      Provider.of<ThemeProvider>(
                        context,
                        listen: false,
                      ).toggleTheme();
                    },
                  ),
                ),
              ),
            // Conditionally show extra action buttons on the right
            if (widget.actions != null && widget.actions!.isNotEmpty)
              Align(
                alignment: isRTL ? Alignment.centerLeft : Alignment.centerRight,
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: widget.actions!,
                ),
              ),
          ],
        ),
      ),
    );
  }

  //
  // /// A helper method to display the language selection dialog.
  // void _showLanguageDialog(BuildContext context) {
  //   final localeProvider = Provider.of<LocaleProvider>(context, listen: false);
  //   final List<String> languages = ['English', 'Arabic', 'Spanish'];
  //
  //   showDialog(
  //     context: context,
  //     builder: (context) => DropdownAlertDialog(
  //       dialogType: DialogType.dropdown,
  //       drpDownTitle: 'Select Language',
  //       btnTitle: 'Save',
  //       title: 'Select Language',
  //       items: languages,
  //       initialValue: localeProvider.currentLanguageName,
  //       onConfirm: (selectedValue) {
  //         if (selectedValue != null) {
  //           localeProvider.setLocale(selectedValue);
  //         }
  //       },
  //     ),
  //   );
  // }
}
