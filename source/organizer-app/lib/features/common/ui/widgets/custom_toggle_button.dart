import 'package:flutter/material.dart';
import 'package:booktkit_organizer/app/app_colors.dart';

class CustomToggleButton extends StatelessWidget {
  final int selectedIndex;
  final List<String> labels;
  final void Function(int index) onChanged;

  const CustomToggleButton({
    super.key,
    required this.selectedIndex,
    required this.labels,
    required this.onChanged,
  }) : assert(
         labels.length == 2 || labels.length == 3,
         'labels length must be 2 or 3',
       );

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: isDark ? Colors.grey.shade900 : Colors.grey.shade200,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isDark ? Colors.grey.shade800 : Colors.grey.shade300,
        ),
      ),
      child: Row(
        children: List.generate(labels.length, (index) {
          final isSelected = selectedIndex == index;

          return Expanded(
            child: GestureDetector(
              onTap: () => onChanged(index),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 250),
                margin: EdgeInsets.only(left: index == 0 ? 0 : 4),
                padding: const EdgeInsets.symmetric(vertical: 10),
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  gradient: isSelected
                      ? (isDark
                            ? LinearGradient(
                                colors: [
                                  Colors.grey.shade800,
                                  Colors.grey.shade700,
                                ],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              )
                            : const LinearGradient(
                                colors: [
                                  AppColors.primaryColor,
                                  AppColors.secondaryColor,
                                ],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              ))
                      : null,
                  color: isSelected
                      ? null
                      : (isDark ? Colors.grey.shade900 : Colors.white),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  labels[index],
                  style: TextStyle(
                    color: isSelected
                        ? Colors.white
                        : (isDark
                              ? Colors.grey.shade400
                              : Colors.grey.shade600),
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
          );
        }),
      ),
    );
  }
}
