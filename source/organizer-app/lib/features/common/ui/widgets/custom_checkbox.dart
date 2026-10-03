import 'package:flutter/material.dart';

class CustomCheckbox extends StatelessWidget {
  final bool value;
  final ValueChanged<bool>? onChanged;
  final String? label;
  final double size;
  final Color? activeColor;
  final Color? borderColor;
  final Color? checkColor;
  final bool enabled;
  final EdgeInsets padding;

  const CustomCheckbox({
    super.key,
    required this.value,
    required this.onChanged,
    this.label,
    this.size = 20,
    this.activeColor,
    this.borderColor,
    this.checkColor,
    this.enabled = true,
    this.padding = const EdgeInsets.symmetric(vertical: 8),
  });

  void _toggle() {
    if (!enabled || onChanged == null) return;
    onChanged!(!value);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final primary = activeColor ?? theme.colorScheme.primary;

    final effectiveBorderColor = value
        ? primary
        : (borderColor ?? theme.dividerColor);

    final effectiveCheckColor = checkColor ?? Colors.white;

    final bgColor = value ? primary : Colors.transparent;

    final opacity = enabled ? 1.0 : 0.5;

    return Opacity(
      opacity: opacity,
      child: InkWell(
        onTap: _toggle,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: padding,
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              AnimatedContainer(
                duration: const Duration(milliseconds: 180),
                curve: Curves.easeOut,
                width: size,
                height: size,
                decoration: BoxDecoration(
                  color: bgColor,
                  borderRadius: BorderRadius.circular(7),
                  border: Border.all(color: effectiveBorderColor, width: 1.6),
                ),
                child: AnimatedScale(
                  duration: const Duration(milliseconds: 180),
                  scale: value ? 1 : 0.6,
                  child: AnimatedOpacity(
                    duration: const Duration(milliseconds: 120),
                    opacity: value ? 1 : 0,
                    child: Icon(
                      Icons.check_rounded,
                      size: size * 0.78,
                      color: effectiveCheckColor,
                    ),
                  ),
                ),
              ),
              if (label != null) ...[
                const SizedBox(width: 10),
                Flexible(
                  child: Text(label!, style: theme.textTheme.bodyMedium),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
