import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

/// Reusable custom icon button widget.
/// Supports both circular and rectangular shapes.
/// Can be used for actions like edit, delete, notifications, etc.
class CustomIconButtonWidget extends StatelessWidget {
  final String assetPath;
  final VoidCallback onTap;
  final double height;
  final double width;
  final double? iconHeight;
  final double? iconWidth;
  final bool showCircle;
  final bool showRectangle;

  const CustomIconButtonWidget({
    super.key,
    required this.assetPath,
    required this.onTap,
    this.height = 38.0,
    this.width = 38.0,
    this.iconHeight,
    this.iconWidth,
    this.showCircle = false,
    this.showRectangle = true,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final isCircle = showCircle && !showRectangle;
    final borderRadius = isCircle ? null : BorderRadius.circular(6);

    return InkWell(
      onTap: onTap,
      borderRadius: borderRadius ?? BorderRadius.circular(height / 2),
      child: Container(
        padding: EdgeInsets.all(4),
        height: height,
        width: width,
        decoration: BoxDecoration(
          color: isDark ? Colors.grey.shade900 : Colors.white,
          shape: isCircle ? BoxShape.circle : BoxShape.rectangle,
          border: Border.all(
            color: isDark ? Colors.grey.shade600 : Colors.grey.shade400,
          ),
          borderRadius: borderRadius,
        ),
        alignment: Alignment.center,
        child: SvgPicture.asset(
          assetPath,
          height: iconHeight,
          width: iconWidth,
        ),
      ),
    );
  }
}
