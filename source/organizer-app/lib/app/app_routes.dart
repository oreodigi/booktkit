import 'package:booktkit_organizer/features/auth/ui/screens/change_password.dart';
import 'package:booktkit_organizer/features/auth/ui/screens/edit_profile.dart';
import 'package:booktkit_organizer/features/auth/ui/screens/login_screen.dart';
import 'package:booktkit_organizer/features/auth/ui/screens/signup_screen.dart';
import 'package:booktkit_organizer/features/auth/ui/screens/splash_screen.dart';
import 'package:booktkit_organizer/features/dashboard/ui/screens/dashboard_screen.dart';
import 'package:booktkit_organizer/features/event_bookings/ui/screens/all_bookings.dart';
import 'package:booktkit_organizer/features/event_bookings/ui/screens/booking_details.dart';
import 'package:booktkit_organizer/features/event_bookings/ui/screens/completed_bookings.dart';
import 'package:booktkit_organizer/features/event_bookings/ui/screens/pending_bookings.dart';
import 'package:booktkit_organizer/features/event_bookings/ui/screens/rejected_bookings.dart';
import 'package:booktkit_organizer/features/event_bookings/ui/screens/report.dart';
import 'package:booktkit_organizer/features/event_management/ui/screens/add_tickets_screen.dart';
import 'package:booktkit_organizer/features/event_management/ui/screens/add_venue_event.dart';
import 'package:booktkit_organizer/features/event_management/ui/screens/add_online_event.dart';
import 'package:booktkit_organizer/features/event_management/ui/screens/all_events.dart';
import 'package:booktkit_organizer/features/event_management/ui/screens/choose_type.dart';
import 'package:booktkit_organizer/features/event_management/ui/screens/edit_online_event.dart';
import 'package:booktkit_organizer/features/event_management/ui/screens/edit_venue_event.dart';
import 'package:booktkit_organizer/features/event_management/ui/screens/online_events.dart';
import 'package:booktkit_organizer/features/event_management/ui/screens/tickets_screen.dart';
import 'package:booktkit_organizer/features/event_management/ui/screens/edit_ticket_screen.dart';
import 'package:booktkit_organizer/features/event_management/ui/screens/tickets_settings_screen.dart';
import 'package:booktkit_organizer/features/event_management/ui/screens/venue_events.dart';
import 'package:booktkit_organizer/features/income/ui/screens/monthly_income.dart';
import 'package:booktkit_organizer/features/nav_appbar/ui/screens/app_home.dart';
import 'package:booktkit_organizer/features/support_tickets/ui/screen/add_support_ticket.dart';
import 'package:booktkit_organizer/features/support_tickets/ui/screen/all_support_tickets.dart';
import 'package:booktkit_organizer/features/transactions/ui/screens/transactions.dart';
import 'package:booktkit_organizer/features/withdraw/ui/screens/withdraw_screen.dart';
import 'package:booktkit_organizer/features/withdraw/ui/screens/withdrawal_request.dart';
import 'package:flutter/material.dart';

class AppRoutes {
  AppRoutes._();

  static const String splash = '/';
  static const String login = '/login';
  static const String signup = '/signup';
  static const String home = '/home';
  static const String dashboard = '/dashboard';
  static const String monthlyIncome = '/monthly-income';
  static const String transactions = '/transactions';
  static const String chooseEventType = '/event-management/choose-type';
  static const String addOnlineEvent = '/event-management/add-online-event';
  static const String addVenueEvent = '/event-management/add-venue-event';
  static const String allEvents = '/event-management/all-events';
  static const String venueEvents = '/event-management/venue-events';
  static const String onlineEvents = '/event-management/online-events';
  static const String manageTickets = '/event-management/manage';
  static const String eventTickets = '/event-management/tickets';
  static const String editTicket = '/event-management/edit-ticket';
  static const String addTicketScreen = '/event-management/add-ticket-screen';
  static const String editOnlineEvent = '/event-management/edit-online-event';
  static const String editVenueEvent = '/event-management/edit-venue-event';
  static const String ticketSettings = '/event-management/ticket-settings';
  static const String allBookings = '/event-bookings/all';
  static const String bookingDetails = '/event-bookings/details';
  static const String completedBookings = '/event-bookings/completed';
  static const String pendingBookings = '/event-bookings/pending';
  static const String rejectedBookings = '/event-bookings/rejected';
  static const String report = '/event-bookings/report';

  static const String withdraw = '/withdraw';
  static const String withdrawRequest = '/withdraw-request';

  static const String allSupportTickets = '/support_tickets';
  static const String addSupportTicket = '/support_tickets/add';

  static const String editProfile = '/profile/edit';
  static const String changePassword = '/profile/change-password';

  static Route<dynamic> onGenerateRoute(RouteSettings settings) {
    switch (settings.name) {
      case splash:
        return _pageRoute(const SplashScreen(), settings);
      case login:
        return _pageRoute(const LoginScreen(), settings);
      case signup:
        return _pageRoute(const VendorSignupScreen(), settings);
      case home:
        final args = settings.arguments;
        if (args is AppHomeArgs) {
          return _pageRoute(AppHome(initialIndex: args.initialIndex), settings);
        }
        return _pageRoute(const AppHome(), settings);
      case dashboard:
        return _pageRoute(const DashboardScreen(), settings);
      case monthlyIncome:
        return _pageRoute(const MonthlyIncome(), settings);
      case transactions:
        return _pageRoute(const Transactions(), settings);
      case chooseEventType:
        return _pageRoute(const ChooseType(), settings);
      case addVenueEvent:
        return _pageRoute(const AddVenueEvent(), settings);
      case addOnlineEvent:
        return _pageRoute(const AddOnlineEvent(), settings);
      case allEvents:
        return _pageRoute(const AllEvents(), settings);
      case venueEvents:
        return _pageRoute(const VenueEvents(), settings);
      case onlineEvents:
        return _pageRoute(const OnlineEvents(), settings);
      case manageTickets:
        {
          final args = settings.arguments;
          if (args is EventTicketsArgs) {
            return _pageRoute(
              TicketsScreen(eventId: args.eventId, eventType: args.eventType),
              settings,
            );
          }
          return _pageRoute(const TicketsScreen(), settings);
        }
      case eventTickets:
        {
          final args = settings.arguments;
          if (args is EventTicketsArgs) {
            return _pageRoute(
              TicketsScreen(eventId: args.eventId, eventType: args.eventType),
              settings,
            );
          }
          return _pageRoute(const TicketsScreen(), settings);
        }
      case ticketSettings:
        {
          final args = settings.arguments;
          if (args is TicketSettingsArgs) {
            return _pageRoute(
              TicketsSettingsScreen(eventId: args.eventId),
              settings,
            );
          }
          return _pageRoute(const TicketsSettingsScreen(), settings);
        }
      case editVenueEvent:
        final args = settings.arguments;
        if (args is EditEventArgs) {
          return _pageRoute(EditVenueEvent(eventId: args.eventId), settings);
        }
        return _pageRoute(const EditVenueEvent(), settings);
      case editOnlineEvent:
        final args = settings.arguments;
        if (args is EditEventArgs) {
          return _pageRoute(EditOnlineEvent(eventId: args.eventId), settings);
        }
        return _pageRoute(const EditOnlineEvent(), settings);
      case addTicketScreen:
        final args = settings.arguments;
        if (args is AddTicketArgs) {
          return _pageRoute(
            AddTicketsScreen(
              priceType: args.priceType,
              eventId: args.eventId,
            ),
            settings,
          );
        }
        return _unknownRoute(settings);
      case editTicket:
        final args = settings.arguments;
        if (args is EditTicketArgs) {
          return _pageRoute(
            EditTicketScreen(
              eventId: args.eventId,
              eventType: args.eventType,
              ticketId: args.ticketId,
            ),
            settings,
          );
        }
        return _unknownRoute(settings);
      case allBookings:
        return _pageRoute(const AllBookings(), settings);
      case bookingDetails:
        final args = settings.arguments;
        if (args is BookingDetailsArgs) {
          return _pageRoute(
            BookingDetails(bookingId: args.bookingId),
            settings,
          );
        }
        return _unknownRoute(settings);
      case completedBookings:
        return _pageRoute(const CompletedBookings(), settings);
      case pendingBookings:
        return _pageRoute(const PendingBookings(), settings);
      case rejectedBookings:
        return _pageRoute(const RejectedBookings(), settings);
      case report:
        return _pageRoute(const Report(), settings);
      case withdraw:
        return _pageRoute(const WithdrawScreen(), settings);
      case withdrawRequest:
        return _pageRoute(const WithdrawalRequest(), settings);
      case allSupportTickets:
        return _pageRoute(const AllTickets(), settings);
      case addSupportTicket:
        return _pageRoute(const AddTicket(), settings);
      case editProfile:
        return _pageRoute(const EditProfile(), settings);
      case changePassword:
        return _pageRoute(const ChangePassword(), settings);
      default:
        return _unknownRoute(settings);
    }
  }

  static MaterialPageRoute<void> _pageRoute(
    Widget page,
    RouteSettings settings,
  ) {
    return MaterialPageRoute<void>(settings: settings, builder: (_) => page);
  }

  static MaterialPageRoute<void> _unknownRoute(RouteSettings settings) {
    final name = settings.name ?? 'unknown';
    return MaterialPageRoute<void>(
      settings: settings,
      builder: (_) {
        return Scaffold(
          appBar: AppBar(title: const Text('Route not found')),
          body: Center(child: Text('No route defined for "$name"')),
        );
      },
    );
  }
}

class AppHomeArgs {
  final int initialIndex;

  const AppHomeArgs({this.initialIndex = 0});
}

class BookingDetailsArgs {
  final String bookingId;

  const BookingDetailsArgs({required this.bookingId});
}

class EditTicketArgs {
  final int eventId;
  final String eventType;
  final int ticketId;

  const EditTicketArgs({
    required this.eventId,
    required this.eventType,
    required this.ticketId,
  });
}

class AddTicketArgs {
  final int? priceType;
  final int? eventId;

  const AddTicketArgs({this.priceType, this.eventId});
}

class EditEventArgs {
  final int eventId;

  const EditEventArgs({required this.eventId});
}

class EventTicketsArgs {
  final int eventId;
  final String eventType;

  const EventTicketsArgs({required this.eventId, required this.eventType});
}

class TicketSettingsArgs {
  final int eventId;

  const TicketSettingsArgs({required this.eventId});
}
