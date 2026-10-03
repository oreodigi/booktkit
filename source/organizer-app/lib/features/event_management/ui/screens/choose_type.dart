import 'package:booktkit_organizer/app/assets_path.dart';
import 'package:booktkit_organizer/app/app_routes.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/nav_appbar/ui/widgets/app_text_styles.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

class ChooseType extends StatelessWidget {
  const ChooseType({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    return Scaffold(
      appBar: CustomAppBar(title: 'Choose Event Type'),
      body: Padding(
        padding: const EdgeInsets.all(12.0),
        child: Row(
          crossAxisAlignment: .start,
          children: [
            Expanded(
              child: SizedBox(
                height: 184,
                child: GestureDetector(
                  onTap: () {
                    Navigator.pushNamed(context, AppRoutes.addOnlineEvent);
                  },
                  child: Card(
                    elevation: 0.5,
                    child: Column(
                      children: [
                        Container(
                          margin: EdgeInsets.all(16),
                          padding: EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(10),
                            color: isDark ? Colors.grey.shade800 : Colors.white,
                          ),
                          height: 100,
                          width: 100,
                          child: SvgPicture.asset(AssetsPath.onlineEv),
                        ),
                        Text(
                          'Online Event',
                          style: AppTextStyles.headingMedium.copyWith(
                            color: isDark ? Colors.grey.shade200 : null,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
            SizedBox(width: 8),
            Expanded(
              child: SizedBox(
                height: 184,
                child: GestureDetector(
                  onTap: () {
                    Navigator.pushNamed(context, AppRoutes.addVenueEvent);
                  },
                  child: Card(
                    elevation: 0.5,

                    child: Column(
                      children: [
                        Container(
                          margin: EdgeInsets.all(16),
                          padding: EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(10),
                            color: isDark ? Colors.grey.shade800 : Colors.white,
                          ),
                          height: 100,
                          width: 100,
                          child: SvgPicture.asset(AssetsPath.venueEv),
                        ),
                        Text(
                          'Venue Event',
                          style: AppTextStyles.headingMedium.copyWith(
                            color: isDark ? Colors.grey.shade200 : null,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
