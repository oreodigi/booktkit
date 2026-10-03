import 'dart:io';
import 'package:booktkit_organizer/features/auth/data/models/edit_profile_response.dart';
import 'package:booktkit_organizer/features/auth/providers/edit_profile_provider.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_checkbox.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_snackbar.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

class EditProfile extends StatefulWidget {
  const EditProfile({super.key});

  @override
  State<EditProfile> createState() => _EditProfileState();
}

class _EditProfileState extends State<EditProfile> {
  final _formKey = GlobalKey<FormState>();

  String _lang = 'en';
  bool get _rtlFields => _lang == 'ar';

  bool _initialised = false;

  // Controllers — Account
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _username = TextEditingController();
  final _facebook = TextEditingController();
  final _twitter = TextEditingController();
  final _linkedin = TextEditingController();

  // English language fields
  final _enName = TextEditingController();
  final _enDesignation = TextEditingController();
  final _enCountry = TextEditingController();
  final _enCity = TextEditingController();
  final _enState = TextEditingController();
  final _enZip = TextEditingController();
  final _enAddress = TextEditingController();
  final _enDetails = TextEditingController();

  // Arabic language fields
  final _arName = TextEditingController();
  final _arDesignation = TextEditingController();
  final _arCountry = TextEditingController();
  final _arCity = TextEditingController();
  final _arState = TextEditingController();
  final _arZip = TextEditingController();
  final _arAddress = TextEditingController();
  final _arDetails = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final provider = context.read<EditProfileProvider>();
      provider.reset();
      provider.fetchProfile();
    });
  }

  @override
  void dispose() {
    _email.dispose();
    _phone.dispose();
    _username.dispose();
    _facebook.dispose();
    _twitter.dispose();
    _linkedin.dispose();
    // EN
    _enName.dispose();
    _enDesignation.dispose();
    _enCountry.dispose();
    _enCity.dispose();
    _enState.dispose();
    _enZip.dispose();
    _enAddress.dispose();
    _enDetails.dispose();
    // AR
    _arName.dispose();
    _arDesignation.dispose();
    _arCountry.dispose();
    _arCity.dispose();
    _arState.dispose();
    _arZip.dispose();
    _arAddress.dispose();
    _arDetails.dispose();
    super.dispose();
  }

  void _populateOnce(EditProfileData data) {
    if (_initialised) return;
    _initialised = true;
    _populateFields(data);
  }

  /// Populate form fields with fetched data
  void _populateFields(EditProfileData data) {
    // Populate organizer account fields
    final organizer = data.organizer;
    _email.text = organizer.email;
    _phone.text = organizer.phone;
    _username.text = organizer.username;
    _facebook.text = organizer.facebook;
    _twitter.text = organizer.twitter;
    _linkedin.text = organizer.linkedin;

    // Populate language-specific fields
    for (var info in data.organizerInfos) {
      if (info.languageId == '8') {
        // English
        _enName.text = info.name;
        _enDesignation.text = info.designation;
        _enCountry.text = info.country;
        _enCity.text = info.city;
        _enState.text = info.state;
        _enZip.text = info.zipCode;
        _enAddress.text = info.address;
        _enDetails.text = info.details ?? '';
      } else if (info.languageId == '22') {
        // Arabic
        _arName.text = info.name;
        _arDesignation.text = info.designation;
        _arCountry.text = info.country;
        _arCity.text = info.city;
        _arState.text = info.state;
        _arZip.text = info.zipCode;
        _arAddress.text = info.address;
        _arDetails.text = info.details ?? '';
      }
    }
  }

  String rtlT(String key) => (_lang == 'ar' ? _ar[key] : _en[key]) ?? key;

  static const _en = {
    'language': 'Language',
    'name': 'Name*',
    'designation': 'Designation',
    'country': 'Country',
    'city': 'City',
    'state': 'State',
    'zip': 'Zip Code',
    'address': 'Address',
    'details': 'Details',
    'required': 'Required',
    'photo': 'Photo*',
    'photoHint': 'Image size 300×300',
    'change': 'Change',
    'remove': 'Remove',
  };

  static const _ar = {
    'language': 'اللغة',
    'name': 'الاسم*',
    'designation': 'المسمى الوظيفي',
    'country': 'الدولة',
    'city': 'المدينة',
    'state': 'الولاية',
    'zip': 'الرمز البريدي',
    'address': 'العنوان',
    'details': 'تفاصيل',
    'required': 'مطلوب',
    'photo': 'الصورة*',
    'photoHint': 'المقاس 300×300',
    'change': 'تغيير',
    'remove': 'حذف',
  };

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    final provider = context.read<EditProfileProvider>();
    final success = await provider.saveProfile(
      email: _email.text.trim(),
      phone: _phone.text.trim(),
      username: _username.text.trim(),
      facebook: _facebook.text.trim(),
      twitter: _twitter.text.trim(),
      linkedin: _linkedin.text.trim(),
      enName: _enName.text.trim(),
      enDesignation: _enDesignation.text.trim(),
      enCountry: _enCountry.text.trim(),
      enCity: _enCity.text.trim(),
      enState: _enState.text.trim(),
      enZipCode: _enZip.text.trim(),
      enAddress: _enAddress.text.trim(),
      enDetails: _enDetails.text.trim(),
      arName: _arName.text.trim(),
      arDesignation: _arDesignation.text.trim(),
      arCountry: _arCountry.text.trim(),
      arCity: _arCity.text.trim(),
      arState: _arState.text.trim(),
      arZipCode: _arZip.text.trim(),
      arAddress: _arAddress.text.trim(),
      arDetails: _arDetails.text.trim(),
    );
    if (!mounted) return;
    if (success) {
      CustomSnackBar.show(
        context: context,
        message: 'Profile updated successfully!',
        type: SnackBarType.success,
      );
    } else {
      CustomSnackBar.show(
        context: context,
        message: provider.saveError ?? 'Failed to update profile',
        type: SnackBarType.error,
      );
    }
  }

  void _changePhoto() => context.read<EditProfileProvider>().pickPhoto();

  void _removePhoto() => context.read<EditProfileProvider>().removePhoto();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final primary = theme.colorScheme.primary;
    final isDark = theme.brightness == Brightness.dark;
    final provider = context.watch<EditProfileProvider>();

    // Populate controllers once data is available
    if (provider.profileData != null && !_initialised) {
      _populateOnce(provider.profileData!);
    }

    // Show fetch error
    if (provider.fetchError != null && !provider.isLoading) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          CustomSnackBar.show(
            context: context,
            message: provider.fetchError!,
            type: SnackBarType.error,
          );
        }
      });
    }

    return Scaffold(
      backgroundColor: isDark ? theme.scaffoldBackgroundColor : Colors.white,
      appBar: CustomAppBar(title: 'Edit Profile'),
      bottomNavigationBar: provider.isLoading
          ? null
          : SafeArea(
              child: Container(
                padding: const EdgeInsets.fromLTRB(16, 10, 16, 0),
                decoration: BoxDecoration(
                  color: isDark ? Colors.black : Colors.white,
                  border: Border(
                    top: BorderSide(
                      color: isDark
                          ? Colors.grey.shade800
                          : Colors.grey.shade200,
                    ),
                  ),
                ),
                child: SizedBox(
                  height: 52,
                  child: FilledButton(
                    onPressed: provider.isSaving ? null : _save,
                    style: FilledButton.styleFrom(
                      backgroundColor: isDark ? Colors.grey.shade800 : primary,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                    child: provider.isSaving
                        ? const SizedBox(
                            height: 18,
                            width: 18,
                            child: CircularProgressIndicator(
                              strokeWidth: 2.2,
                              color: Colors.white,
                            ),
                          )
                        : Text(
                            'Save Changes',
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w800,
                              color: isDark ? Colors.white70 : null,
                            ),
                          ),
                  ),
                ),
              ),
            ),
      body: provider.isLoading
          ? Center(child: CircularProgressIndicator(color: primary))
          : Form(
              key: _formKey,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                children: [
                  _PhotoHeader(
                    title: rtlT('photo'),
                    subtitle: rtlT('photoHint'),
                    primary: primary,
                    hasPhoto: provider.hasPhoto,
                    profilePhoto: provider.photo,
                    existingPhotoUrl: provider.existingPhotoUrl,
                    onChange: _changePhoto,
                    onRemove: _removePhoto,
                    changeText: rtlT('change'),
                    removeText: rtlT('remove'),
                  ),

                  const SizedBox(height: 14),

                  _Card(
                    title: 'Account',
                    children: [
                      _field(
                        context: context,
                        label: 'Email*',
                        controller: _email,
                        keyboardType: TextInputType.emailAddress,
                        validator: (v) =>
                            (v ?? '').trim().isEmpty ? 'Required' : null,
                        textAlign: TextAlign.left,
                      ),
                      const SizedBox(height: 12),
                      _field(
                        context: context,
                        label: 'Phone',
                        controller: _phone,
                        keyboardType: TextInputType.phone,
                        textAlign: TextAlign.left,
                      ),
                      const SizedBox(height: 12),
                      _field(
                        context: context,
                        label: 'Username*',
                        controller: _username,
                        validator: (v) =>
                            (v ?? '').trim().isEmpty ? 'Required' : null,
                        textAlign: TextAlign.left,
                      ),
                    ],
                  ),

                  const SizedBox(height: 14),

                  _Card(
                    title: 'Social Links',
                    children: [
                      _field(
                        context: context,
                        label: 'Facebook',
                        controller: _facebook,
                        keyboardType: TextInputType.url,
                        textAlign: TextAlign.left,
                      ),
                      const SizedBox(height: 12),
                      _field(
                        context: context,
                        label: 'Twitter',
                        controller: _twitter,
                        keyboardType: TextInputType.url,
                        textAlign: TextAlign.left,
                      ),
                      const SizedBox(height: 12),
                      _field(
                        context: context,
                        label: 'Linkedin',
                        controller: _linkedin,
                        keyboardType: TextInputType.url,
                        textAlign: TextAlign.left,
                      ),
                    ],
                  ),

                  const SizedBox(height: 14),

                  _Card(
                    title: 'Language',
                    trailing: _LangDropdown(
                      label: rtlT('language'),
                      value: _lang,
                      onChanged: (v) => setState(() => _lang = v),
                    ),
                    children: [
                      Directionality(
                        textDirection: _rtlFields
                            ? TextDirection.rtl
                            : TextDirection.ltr,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _field(
                              context: context,
                              label: rtlT('name'),
                              controller: _rtlFields ? _arName : _enName,
                              validator: (v) => (v ?? '').trim().isEmpty
                                  ? rtlT('required')
                                  : null,
                              textAlign: _rtlFields
                                  ? TextAlign.right
                                  : TextAlign.left,
                            ),
                            const SizedBox(height: 12),
                            _field(
                              context: context,
                              label: rtlT('designation'),
                              controller: _rtlFields
                                  ? _arDesignation
                                  : _enDesignation,
                              textAlign: _rtlFields
                                  ? TextAlign.right
                                  : TextAlign.left,
                            ),
                            const SizedBox(height: 12),
                            Row(
                              children: [
                                Expanded(
                                  child: _field(
                                    context: context,
                                    label: rtlT('country'),
                                    controller: _rtlFields
                                        ? _arCountry
                                        : _enCountry,
                                    textAlign: _rtlFields
                                        ? TextAlign.right
                                        : TextAlign.left,
                                  ),
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: _field(
                                    context: context,
                                    label: rtlT('city'),
                                    controller: _rtlFields ? _arCity : _enCity,
                                    textAlign: _rtlFields
                                        ? TextAlign.right
                                        : TextAlign.left,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),
                            Row(
                              children: [
                                Expanded(
                                  child: _field(
                                    context: context,
                                    label: rtlT('state'),
                                    controller: _rtlFields
                                        ? _arState
                                        : _enState,
                                    textAlign: _rtlFields
                                        ? TextAlign.right
                                        : TextAlign.left,
                                  ),
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: _field(
                                    context: context,
                                    label: rtlT('zip'),
                                    controller: _rtlFields ? _arZip : _enZip,
                                    keyboardType: TextInputType.number,
                                    textAlign: _rtlFields
                                        ? TextAlign.right
                                        : TextAlign.left,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),
                            _field(
                              context: context,
                              label: rtlT('address'),
                              controller: _rtlFields ? _arAddress : _enAddress,
                              maxLines: 2,
                              textAlign: _rtlFields
                                  ? TextAlign.right
                                  : TextAlign.left,
                            ),
                            const SizedBox(height: 12),
                            _field(
                              context: context,
                              label: rtlT('details'),
                              controller: _rtlFields ? _arDetails : _enDetails,
                              maxLines: 5,
                              textAlign: _rtlFields
                                  ? TextAlign.right
                                  : TextAlign.left,
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  CustomCheckbox(
                    value: true,
                    onChanged: (v) {},
                    label:
                        'Clone  for ${_lang == 'en' ? 'Arabic' : 'English'} language',
                  ),
                  const SizedBox(height: 12),
                ],
              ),
            ),
    );
  }

  Widget _field({
    required BuildContext context,
    required String label,
    required TextEditingController controller,
    int maxLines = 1,
    TextInputType? keyboardType,
    String? Function(String?)? validator,
    required TextAlign textAlign,
  }) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final border = OutlineInputBorder(
      borderRadius: BorderRadius.circular(14),
      borderSide: BorderSide(
        color: isDark ? Colors.grey.shade700 : Colors.grey.shade300,
      ),
    );

    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      keyboardType: keyboardType,
      validator: validator,
      textAlign: textAlign,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDark ? Colors.grey.shade900 : Colors.grey.shade50,
        border: border,
        enabledBorder: border,
        focusedBorder: border.copyWith(
          borderSide: BorderSide(color: theme.colorScheme.primary),
        ),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 14,
          vertical: 14,
        ),
      ),
    );
  }
}

class _PhotoHeader extends StatelessWidget {
  final String title;
  final String subtitle;
  final Color primary;
  final bool hasPhoto;
  final XFile? profilePhoto;
  final String? existingPhotoUrl;
  final VoidCallback onChange;
  final VoidCallback onRemove;
  final String changeText;
  final String removeText;

  const _PhotoHeader({
    required this.title,
    required this.subtitle,
    required this.primary,
    required this.hasPhoto,
    this.profilePhoto,
    this.existingPhotoUrl,
    required this.onChange,
    required this.onRemove,
    required this.changeText,
    required this.removeText,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isDark ? theme.cardColor : Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: isDark ? Colors.grey.shade800 : Colors.grey.shade200,
        ),
      ),
      child: Row(
        children: [
          Container(
            width: 72,
            height: 72,
            decoration: BoxDecoration(
              color: primary.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: primary.withValues(alpha: 0.15)),
            ),
            child: profilePhoto != null
                ? ClipRRect(
                    borderRadius: BorderRadius.circular(18),
                    child: Image.file(
                      File(profilePhoto!.path),
                      width: 72,
                      height: 72,
                      fit: BoxFit.cover,
                    ),
                  )
                : (existingPhotoUrl != null && existingPhotoUrl!.isNotEmpty)
                ? ClipRRect(
                    borderRadius: BorderRadius.circular(18),
                    child: Image.network(
                      existingPhotoUrl!,
                      width: 72,
                      height: 72,
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) {
                        return Icon(Icons.person_rounded, size: 38);
                      },
                      loadingBuilder: (context, child, loadingProgress) {
                        if (loadingProgress == null) return child;
                        return Center(
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            value: loadingProgress.expectedTotalBytes != null
                                ? loadingProgress.cumulativeBytesLoaded /
                                      loadingProgress.expectedTotalBytes!
                                : null,
                          ),
                        );
                      },
                    ),
                  )
                : Icon(Icons.camera_alt_rounded, color: primary, size: 30),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w900,
                    color: theme.textTheme.bodyLarge?.color,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  subtitle,
                  style: TextStyle(
                    fontSize: 12,
                    color: isDark ? Colors.grey.shade400 : Colors.grey.shade600,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 10,
                  runSpacing: 8,
                  children: [
                    OutlinedButton.icon(
                      onPressed: onChange,
                      icon: const Icon(Icons.upload_rounded, size: 18),
                      label: Text(changeText),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 10,
                        ),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                      ),
                    ),
                    if (hasPhoto)
                      TextButton.icon(
                        onPressed: onRemove,
                        icon: const Icon(
                          Icons.delete_outline_rounded,
                          size: 18,
                        ),
                        label: Text(removeText),
                        style: TextButton.styleFrom(
                          foregroundColor: Colors.red,
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 10,
                          ),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                        ),
                      ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Card extends StatelessWidget {
  final String title;
  final Widget? trailing;
  final List<Widget> children;

  const _Card({required this.title, required this.children, this.trailing});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isDark ? theme.cardColor : Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: isDark ? Colors.grey.shade800 : Colors.grey.shade200,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  title,
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w900,
                    color: theme.textTheme.bodyLarge?.color,
                  ),
                ),
              ),
              ?trailing,
            ],
          ),
          const SizedBox(height: 12),
          ...children,
        ],
      ),
    );
  }
}

class _LangDropdown extends StatelessWidget {
  final String label;
  final String value;
  final ValueChanged<String> onChanged;

  const _LangDropdown({
    required this.label,
    required this.value,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return SizedBox(
      width: 160,
      child: DropdownButtonFormField<String>(
        initialValue: value,
        isExpanded: true,
        dropdownColor: theme.dialogTheme.backgroundColor,
        decoration: InputDecoration(
          labelText: label,
          filled: true,
          fillColor: isDark ? Colors.grey.shade900 : null,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 12,
            vertical: 10,
          ),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: BorderSide(
              color: isDark ? Colors.grey.shade700 : Colors.grey.shade300,
            ),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: BorderSide(
              color: isDark ? Colors.grey.shade700 : Colors.grey.shade300,
            ),
          ),
        ),
        items: const [
          DropdownMenuItem(value: 'en', child: Text('English')),
          DropdownMenuItem(value: 'ar', child: Text('العربية')),
        ],
        onChanged: (v) {
          if (v != null) onChanged(v);
        },
      ),
    );
  }
}
