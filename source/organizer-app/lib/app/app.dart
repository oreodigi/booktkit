import 'package:booktkit_organizer/app/theme_data.dart';
import 'package:booktkit_organizer/app/app_routes.dart';
import 'package:booktkit_organizer/app/locale_provider.dart';
import 'package:booktkit_organizer/app/theme_provider.dart';
import 'package:booktkit_organizer/features/auth/providers/auth_provider.dart';
import 'package:booktkit_organizer/features/auth/providers/edit_profile_provider.dart';
import 'package:booktkit_organizer/features/dashboard/providers/dashboard_provider.dart';
import 'package:booktkit_organizer/features/event_bookings/providers/booking_details_provider.dart';
import 'package:booktkit_organizer/features/event_bookings/providers/booking_report_provider.dart';
import 'package:booktkit_organizer/features/event_management/providers/categories_provider.dart';
import 'package:booktkit_organizer/features/event_management/providers/edit_ticket_provider.dart';
import 'package:booktkit_organizer/features/event_management/providers/event_management_provider.dart';
import 'package:booktkit_organizer/features/event_management/providers/event_tickets_provider.dart';
import 'package:booktkit_organizer/features/event_management/providers/add_event_provider.dart';
import 'package:booktkit_organizer/features/event_management/providers/seat_mapping_provider.dart';
import 'package:booktkit_organizer/features/event_bookings/providers/event_bookings_provider.dart';
import 'package:booktkit_organizer/features/event_management/providers/ticket_settings_provider.dart';
import 'package:booktkit_organizer/features/income/providers/income_data_provider.dart';
import 'package:booktkit_organizer/features/support_tickets/providers/support_tickets_provider.dart';
import 'package:booktkit_organizer/features/transactions/providers/transactions_provider.dart';
import 'package:booktkit_organizer/features/common/providers/currency_provider.dart';
import 'package:booktkit_organizer/features/withdraw/providers/withdraw_provider.dart';
import 'package:flutter/material.dart';
import 'package:flutter_quill/flutter_quill.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:provider/provider.dart';

final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

class BooktkitOrganizer extends StatefulWidget {
  final ThemeProvider themeProvider;
  final LocaleProvider localeProvider;

  const BooktkitOrganizer({
    super.key,
    required this.themeProvider,
    required this.localeProvider,
  });

  @override
  State<BooktkitOrganizer> createState() => _BooktkitOrganizerState();
}

class _BooktkitOrganizerState extends State<BooktkitOrganizer> {
  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider.value(value: widget.themeProvider),
        ChangeNotifierProvider.value(value: widget.localeProvider),
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => EditProfileProvider()),
        ChangeNotifierProvider(create: (_) => DashboardProvider()),
        ChangeNotifierProvider(create: (_) => TransactionsProvider()),
        ChangeNotifierProvider(create: (_) => IncomeProvider()),
        ChangeNotifierProvider(create: (_) => WithdrawProvider()),
        ChangeNotifierProvider(create: (_) => SupportTicketsProvider()),
        ChangeNotifierProvider(create: (_) => EventBookingsProvider()),
        ChangeNotifierProvider(create: (_) => BookingReportProvider()),
        ChangeNotifierProvider(create: (_) => BookingDetailsProvider()),
        ChangeNotifierProvider(create: (_) => EventManagementProvider()),
        ChangeNotifierProvider(create: (_) => EventTicketsProvider()),
        ChangeNotifierProvider(create: (_) => TicketSettingsProvider()),
        ChangeNotifierProvider(create: (_) => CategoriesProvider()),
        ChangeNotifierProvider(create: (_) => AddEventProvider()),
        ChangeNotifierProvider(create: (_) => EditTicketProvider()),
        ChangeNotifierProvider(create: (_) => SeatMappingProvider()),
        ChangeNotifierProvider(create: (_) => CurrencyProvider()),
      ],
      child: Consumer2<ThemeProvider, LocaleProvider>(
        builder: (context, themeProvider, localeProvider, child) {
          return MaterialApp(
            navigatorKey: navigatorKey,
            debugShowCheckedModeBanner: false,
            theme: AppThemeData.lightTheme,
            darkTheme: AppThemeData.darkTheme,
            themeMode: themeProvider.themeMode,
            locale: localeProvider.locale,
            supportedLocales: const [
              Locale('en', 'US'),
              Locale('ar', 'SA'),
              Locale('es', 'ES'),
            ],
            localizationsDelegates: const [
              GlobalMaterialLocalizations.delegate,
              GlobalWidgetsLocalizations.delegate,
              GlobalCupertinoLocalizations.delegate,
              FlutterQuillLocalizations.delegate,
            ],
            localeResolutionCallback: (deviceLocale, supportedLocales) {
              return localeProvider.locale;
            },
            initialRoute: AppRoutes.splash,
            onGenerateRoute: AppRoutes.onGenerateRoute,
            builder: (context, child) {
              return Directionality(
                textDirection: localeProvider.isRTL
                    ? TextDirection.rtl
                    : TextDirection.ltr,
                child: child!,
              );
            },
          );
        },
      ),
    );
  }
}
