import 'package:booktkit_organizer/app/app_colors.dart';
import 'package:booktkit_organizer/app/assets_path.dart';
import 'package:booktkit_organizer/features/auth/providers/auth_provider.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_button_widget.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_snackbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_text_field.dart';
import 'package:booktkit_organizer/features/nav_appbar/ui/widgets/app_text_styles.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

/// Vendor Signup Screen
class VendorSignupScreen extends StatefulWidget {
  const VendorSignupScreen({super.key});

  @override
  State<VendorSignupScreen> createState() => _VendorSignupScreenState();
}

class _VendorSignupScreenState extends State<VendorSignupScreen> {
  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  final _nameController = TextEditingController();
  @override
  void dispose() {
    _usernameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _handleSignup() async {
    if (_formKey.currentState!.validate()) {
      final authProvider = context.read<AuthProvider>();
      final navigator = Navigator.of(context);

      final response = await authProvider.signup(
        username: _usernameController.text.trim(),
        email: _emailController.text.trim(),
        password: _passwordController.text,
        passwordConfirmation: _confirmPasswordController.text,
        name: _nameController.text.trim(),
      );

      if (!mounted) return;

      if (response != null && response.status) {
        CustomSnackBar.show(
          context: context,
          message: response.message,
          type: SnackBarType.success,
        );
        // Navigate back to login screen
        navigator.pop();
      } else {
        CustomSnackBar.show(
          context: context,
          message: authProvider.errorMessage ?? 'Signup failed',
          type: SnackBarType.error,
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final media = MediaQuery.of(context);
    final h = media.size.height;
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 450),
            child: SingleChildScrollView(
              padding: EdgeInsets.fromLTRB(
                16,
                16,
                16,
                media.viewInsets.bottom > 0 ? 12 : 24,
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  SizedBox(height: h * 0.02),

                  // ───── Brand Header ─────
                  const _BrandHeader(title: 'Vendor Signup'),

                  SizedBox(height: h * 0.03),

                  // ───── Form Card ─────
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: isDark ? Colors.grey.shade900 : Colors.white,
                      borderRadius: BorderRadius.circular(18),
                      border: Border.all(color: Colors.grey.shade200),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.04),
                          blurRadius: 18,
                          offset: const Offset(0, 10),
                        ),
                      ],
                    ),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text(
                            'Create your vendor account',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w900,
                              color: Colors.grey.shade900,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            'Fill in the details below to get started.',
                            style: TextStyle(
                              fontSize: 13,
                              height: 1.35,
                              fontWeight: FontWeight.w600,
                              color: Colors.grey.shade600,
                            ),
                          ),
                          const SizedBox(height: 18),

                          CustomTextField(
                            textEditingController: _nameController,
                            hintText: 'Enter Your Name',
                            headerText: 'Name*',
                          ),
                          const SizedBox(height: 14),
                          CustomTextField(
                            textEditingController: _usernameController,
                            hintText: 'Enter username',
                            headerText: 'Username*',
                          ),
                          const SizedBox(height: 14),
                          CustomTextField(
                            textEditingController: _emailController,
                            hintText: 'Enter email',
                            headerText: 'Email*',
                          ),
                          const SizedBox(height: 14),
                          CustomTextField(
                            textEditingController: _passwordController,
                            hintText: 'Enter password',
                            headerText: 'Password*',
                          ),
                          const SizedBox(height: 14),
                          CustomTextField(
                            textEditingController: _confirmPasswordController,
                            hintText: 'Confirm password',
                            headerText: 'Confirm Password*',
                          ),

                          const SizedBox(height: 22),

                          SizedBox(
                            height: 52,
                            child: CustomButtonWidget(
                              text: 'Sign Up',
                              onPressed: _handleSignup,
                              fontSize: 16,
                            ),
                          ),

                          const SizedBox(height: 14),

                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: AppColors.primaryColor.withValues(
                                alpha: 0.06,
                              ),
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(
                                color: AppColors.primaryColor.withValues(
                                  alpha: 0.14,
                                ),
                              ),
                            ),
                            child: Row(
                              children: [
                                Icon(
                                  Icons.verified_user_outlined,
                                  size: 18,
                                  color: AppColors.primaryColor,
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: Text(
                                    'By signing up, you agree to our terms and policies.',
                                    style: TextStyle(
                                      fontSize: 12,
                                      height: 1.35,
                                      fontWeight: FontWeight.w600,
                                      color: Colors.grey.shade700,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),

                  const SizedBox(height: 16),

                  // ───── Bottom Link ─────
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        "Already a member?",
                        style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w600,
                          color: Colors.grey.shade600,
                        ),
                      ),
                      TextButton(
                        onPressed: () => Navigator.pop(context),
                        child: Text(
                          'Login Now',
                          style: TextStyle(
                            fontWeight: FontWeight.w800,
                            color: AppColors.primaryColor,
                          ),
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 10),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _BrandHeader extends StatelessWidget {
  final String title;

  const _BrandHeader({required this.title});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    return Column(
      children: [
        Container(
          width: 84,
          height: 84,
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: AppColors.primaryColor.withValues(alpha: 0.08),
            borderRadius: BorderRadius.circular(22),
            border: Border.all(
              color: AppColors.primaryColor.withValues(alpha: 0.18),
            ),
          ),
          child: Image.asset(AssetsPath.appLogoMainSvg, fit: BoxFit.contain),
        ),
        const SizedBox(height: 10),
        Text(
          'Booktkit',
          style: AppTextStyles.headingLarge.copyWith(
            color: AppColors.primaryColor,
            fontSize: 26,
            fontWeight: FontWeight.w900,
          ),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 10),
        Text(
          title,
          style: TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.w900,
            color: isDark ? Colors.white : Colors.grey.shade900,
          ),
          textAlign: TextAlign.center,
        ),
      ],
    );
  }
}
