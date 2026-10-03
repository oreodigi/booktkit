import 'package:booktkit_organizer/app/app_colors.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/svg.dart';

class OrganizerNavBarItem extends BottomNavigationBarItem {
  OrganizerNavBarItem({
    required String label,
    required String iconPath,
    required int index,
    required int selectedIndex,
  }) : super(
    label: label,
    icon: SvgPicture.asset(
      height: 40,
      iconPath,
      semanticsLabel: label,
      colorFilter: ColorFilter.mode(
        selectedIndex == index ? AppColors.primaryColor : Colors.grey.shade800,
        BlendMode.srcIn,
      ),
    ),
  );
}
