import 'package:booktkit_organizer/features/auth/providers/auth_provider.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/support_tickets/providers/support_tickets_provider.dart';
import 'package:booktkit_organizer/features/support_tickets/ui/screen/all_support_tickets.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class AddTicket extends StatefulWidget {
  const AddTicket({super.key});

  @override
  State<AddTicket> createState() => _AddTicketState();
}

class _AddTicketState extends State<AddTicket> {
  final _formKey = GlobalKey<FormState>();

  final _emailController = TextEditingController();
  final _subjectController = TextEditingController();
  final _messageController = TextEditingController();

  String? _attachmentPath;
  String? _attachmentName;

  @override
  void initState() {
    super.initState();
    // Pre-fill email from logged-in organizer account
    final org = context.read<AuthProvider>().org;
    if (org != null) {
      _emailController.text = org.email;
    }
  }

  @override
  void dispose() {
    _emailController.dispose();
    _subjectController.dispose();
    _messageController.dispose();
    super.dispose();
  }

  Future<void> _pickFile() async {
    final result = await FilePicker.platform.pickFiles();
    if (result != null && result.files.single.path != null) {
      setState(() {
        _attachmentPath = result.files.single.path;
        _attachmentName = result.files.single.name;
      });
    }
  }

  void _removeAttachment() {
    setState(() {
      _attachmentPath = null;
      _attachmentName = null;
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final provider = context.read<SupportTicketsProvider>();
    final messenger = ScaffoldMessenger.of(context);

    final response = await provider.createTicket(
      email: _emailController.text.trim(),
      subject: _subjectController.text.trim(),
      message: _messageController.text.trim(),
      attachmentPath: _attachmentPath,
    );

    if (!mounted) return;

    if (response != null && response.success) {
      messenger.showSnackBar(
        SnackBar(content: Text(response.message)),
      );
      Navigator.push(context, MaterialPageRoute(builder: (context) => AllTickets()));
    } else {
      messenger.showSnackBar(
        SnackBar(
          content: Text(provider.errorMessage ?? 'Failed to create ticket'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final provider = context.watch<SupportTicketsProvider>();
    final isSubmitting = provider.isCreating;

    final border = theme.dividerColor.withValues(alpha: isDark ? 0.35 : 0.7);

    return Scaffold(
      appBar: CustomAppBar(title: 'Create Ticket'),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _SectionCard(
                  border: border,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Customer Info',
                        style: theme.textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      const SizedBox(height: 12),

                      // Email
                      TextFormField(
                        controller: _emailController,
                        keyboardType: TextInputType.emailAddress,
                        readOnly: true,
                        decoration: const InputDecoration(
                          labelText: 'Email',
                          prefixIcon: Icon(Icons.mail_outline_rounded),
                          suffixIcon: Icon(Icons.lock_outline_rounded, size: 18),
                          helperText: 'Fetched from your account',
                        ),
                        validator: (v) {
                          final value = (v ?? '').trim();
                          if (value.isEmpty) return 'Email is required';
                          if (!value.contains('@')) {
                            return 'Enter a valid email';
                          }
                          return null;
                        },
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 12),

                _SectionCard(
                  border: border,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Ticket Details',
                        style: theme.textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      const SizedBox(height: 12),

                      // Subject
                      TextFormField(
                        controller: _subjectController,
                        enabled: !isSubmitting,
                        decoration: const InputDecoration(
                          labelText: 'Subject',
                          hintText: 'Write a short title',
                          prefixIcon: Icon(Icons.subject_rounded),
                        ),
                        validator: (v) {
                          final value = (v ?? '').trim();
                          if (value.isEmpty) return 'Subject is required';
                          if (value.length < 5) return 'Minimum 5 characters';
                          return null;
                        },
                      ),

                      const SizedBox(height: 12),

                      // Message
                      TextFormField(
                        controller: _messageController,
                        enabled: !isSubmitting,
                        minLines: 4,
                        maxLines: 8,
                        decoration: const InputDecoration(
                          labelText: 'Message',
                          hintText: 'Describe the issue in detail...',
                          alignLabelWithHint: true,
                          prefixIcon: Padding(
                            padding: EdgeInsets.only(bottom: 56),
                            child: Icon(Icons.message_outlined),
                          ),
                        ),
                        validator: (v) {
                          final value = (v ?? '').trim();
                          if (value.isEmpty) return 'Message is required';
                          if (value.length < 10) return 'Minimum 10 characters';
                          return null;
                        },
                      ),

                      const SizedBox(height: 16),

                      // Attachment
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: isSubmitting ? null : _pickFile,
                              icon: const Icon(Icons.attach_file_rounded),
                              label: Text(
                                _attachmentName ?? 'Attach File (optional)',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                              style: OutlinedButton.styleFrom(
                                alignment: Alignment.centerLeft,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 12,
                                  vertical: 12,
                                ),
                              ),
                            ),
                          ),
                          if (_attachmentName != null && !isSubmitting)
                            IconButton(
                              onPressed: _removeAttachment,
                              icon: const Icon(
                                Icons.remove_circle_outline_rounded,
                                color: Colors.red,
                              ),
                            ),
                        ],
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 16),

                SizedBox(
                  width: double.infinity,
                  height: 52,
                  child: ElevatedButton.icon(
                    onPressed: isSubmitting ? null : _submit,
                    icon: isSubmitting
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.add_circle_outline_rounded),
                    label: Text(
                      isSubmitting ? 'Creating...' : 'Create Ticket',
                    ),
                    style: ElevatedButton.styleFrom(
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                  ),
                ),

                const SizedBox(height: 10),

                Text(
                  'Tip: Use a clear subject and include all relevant details for faster support.',
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.textTheme.bodySmall?.color?.withValues(
                      alpha: 0.7,
                    ),
                    height: 1.3,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  final Widget child;
  final Color border;

  const _SectionCard({required this.child, required this.border});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.18 : 0.06),
            blurRadius: 18,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: child,
    );
  }
}
