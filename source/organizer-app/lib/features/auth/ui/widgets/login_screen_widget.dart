import 'package:booktkit_organizer/app/app_colors.dart';
import 'package:booktkit_organizer/features/auth/providers/auth_provider.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/form_header_text_widget.dart';
import 'package:flutter/material.dart';

/// Reusable login form widget with username/password fields and login button
class LoginScreenWidget extends StatelessWidget {
  final VoidCallback onTap;
  final VoidCallback? onSignUpTap;
  final bool showSignUp;
  final TextEditingController usernameController;
  final TextEditingController passwordController;
  final AuthProvider authProvider;
  final GlobalKey<FormState> formKey;

  const LoginScreenWidget({
    super.key,
    required this.onTap,
    this.onSignUpTap,
    this.showSignUp = true,
    required this.usernameController,
    required this.passwordController,
    required this.authProvider,
    required this.formKey,
  });

  @override
  Widget build(BuildContext context) {
    return Form(
      key: formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ───── Username Field ─────
          const FormHeaderTextWidget(text: 'Username*'),
          const SizedBox(height: 4),
          TextFormField(
            controller: usernameController,
            validator: (value) {
              if (value == null || value.isEmpty) {
                return 'Please enter username';
              }
              return null;
            },
          ),
          const SizedBox(height: 24),

          // ───── Password Field ─────
          const FormHeaderTextWidget(text: 'Password*'),
          const SizedBox(height: 4),
          TextFormField(
            obscureText: true,
            controller: passwordController,
            validator: (value) {
              if (value == null || value.isEmpty) {
                return 'Please enter password';
              }
              if (value.length < 6) {
                return 'Password must be at least 6 characters';
              }
              return null;
            },
          ),
          const SizedBox(height: 32),

          // ───── Login Button ─────
          SizedBox(
            height: 56,
            width: double.infinity,
            child: authProvider.isLoading
                ? Center(
                    child: CircularProgressIndicator(
                      color: AppColors.primaryColor,
                    ),
                  )
                : ElevatedButton(
                    onPressed: onTap,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primaryColor,
                      foregroundColor: Colors.white,
                      elevation: 2,
                      shadowColor: AppColors.primaryColor.withValues(alpha: 0.3),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: Text(
                      'Login',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        letterSpacing: 0.5,
                      ),
                    ),
                  ),
          ),
          const SizedBox(height: 16),

          // ───── Sign Up Prompt ─────
          if (showSignUp) _buildSignUpPrompt(context),
        ],
      ),
    );
  }

  /// Sign up redirection row
  Widget _buildSignUpPrompt(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Text(
          "Don't have an account?",
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w500,
            color: isDark ? Colors.grey.shade400 : Colors.grey.shade600,
          ),
        ),
        TextButton(onPressed: onSignUpTap, child: const Text('Sign Up')),
      ],
    );
  }
}
