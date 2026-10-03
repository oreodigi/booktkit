import 'package:booktkit_organizer/app/app_colors.dart';
import 'package:booktkit_organizer/app/app_routes.dart';
import 'package:booktkit_organizer/app/assets_path.dart';
import 'package:booktkit_organizer/features/auth/providers/auth_provider.dart';
import 'package:booktkit_organizer/services/api_client.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'dart:async';

import 'package:shared_preferences/shared_preferences.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _scaleAnimation;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();

    // Set up animations
    _controller = AnimationController(
      duration: const Duration(milliseconds: 1500),
      vsync: this,
    );

    _scaleAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).chain(CurveTween(curve: Curves.easeOut)).animate(_controller);

    _fadeAnimation = Tween<double>(
      begin: 0.8,
      end: 1.0,
    ).chain(CurveTween(curve: Curves.easeIn)).animate(_controller);

    _controller.forward();
    _checkLoginStatus();
  }

  Future<void> _checkLoginStatus() async {
    // Initialize API client token from storage
    await ApiClient().initializeToken();

    // // Initialize app configuration
    // if (!mounted) return;
    // final appConfigProvider = context.read<AppConfigProvider>();
    // await appConfigProvider.initialize();

    // Wait for animation to complete
    await Future.delayed(const Duration(seconds: 2));

    if (!mounted) return;

    final prefs = await SharedPreferences.getInstance();
    final isLoggedIn = prefs.getBool('is_logged_in') ?? false;
    final token = prefs.getString('token');

    if (isLoggedIn && token != null && token.isNotEmpty && mounted) {
      // Load user data
      final authProvider = context.read<AuthProvider>();
      await authProvider.loadUserData();

      if (!mounted) return;

      // Navigate to dashboard
      Navigator.pushNamed(context, AppRoutes.home);
    } else {
      // Navigate to login screen
      if (!mounted) return;
      Navigator.pushNamed(context, AppRoutes.login);
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: isDark
              ? AppColors.darkThemeGradient
              : AppColors.themeGradient,
        ),
        child: Center(
          child: AnimatedBuilder(
            animation: _controller,
            builder: (context, child) {
              return Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Spacer(),
                  // Outer transparent circle
                  Container(
                    width: 160,
                    height: 160,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: isDark
                          ? Colors.white.withAlpha(20)
                          : Colors.white.withAlpha(30),
                    ),
                    alignment: Alignment.center,
                    child: Container(
                      width: 120,
                      height: 120,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: isDark
                            ? Colors.white.withAlpha(40)
                            : Colors.white.withAlpha(100),
                      ),
                      alignment: Alignment.center,
                      child: Transform.scale(
                        scale: _scaleAnimation.value,
                        child: Container(
                          width: 90,
                          height: 90,
                          decoration: const BoxDecoration(
                            shape: BoxShape.circle,
                          ),
                          child: Padding(
                            padding: const EdgeInsets.all(16.0),
                            child: Image.asset(AssetsPath.appLogoMainSvg),
                          ),
                        ),
                      ),
                    ),
                  ),
                  const Spacer(),
                  FadeTransition(
                    opacity: _fadeAnimation,
                    child: const Text(
                      'Booktkit',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 32,
                        fontWeight: FontWeight.w600,
                        letterSpacing: 1.2,
                      ),
                    ),
                  ),
                  const SizedBox(height: 50),
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(body: Center(child: Text('Welcome to Booktkit')));
  }
}
