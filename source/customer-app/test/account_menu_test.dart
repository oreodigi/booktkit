import 'package:booktkit_customer/features/account/ui/widgets/dashborad_list_card.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';

void main() {
  testWidgets('account menu renders Font Awesome icons and handles taps', (
    tester,
  ) async {
    String? selected;
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: DashboardListCard(
            items: const [
              {'title': 'Dashboard', 'icon': FontAwesomeIcons.gauge},
              {
                'title': 'Event Bookings',
                'icon': FontAwesomeIcons.calendarDays,
              },
            ],
            onItemTap: (_, title) => selected = title,
          ),
        ),
      ),
    );

    expect(tester.takeException(), isNull);
    expect(find.byType(FaIcon), findsNWidgets(2));
    expect(find.text('Dashboard'), findsOneWidget);
    await tester.tap(find.text('Event Bookings'));
    expect(selected, 'Event Bookings');
  });
}
