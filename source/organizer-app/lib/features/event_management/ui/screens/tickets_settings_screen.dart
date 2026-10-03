import 'dart:io';
import 'package:dotted_border/dotted_border.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_header_text_widget.dart';
import 'package:booktkit_organizer/features/common/ui/widgets/custom_snackbar.dart';
import 'package:booktkit_organizer/features/event_management/providers/ticket_settings_provider.dart';
import 'package:booktkit_organizer/features/nav_appbar/ui/widgets/app_text_styles.dart';
import 'package:flutter/material.dart';
import 'package:flutter_quill/flutter_quill.dart';
import 'package:flutter_quill_delta_from_html/flutter_quill_delta_from_html.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

class TicketsSettingsScreen extends StatefulWidget {
  final int? eventId;

  const TicketsSettingsScreen({super.key, this.eventId});

  @override
  State<TicketsSettingsScreen> createState() => _TicketsSettingsScreenState();
}

class _TicketsSettingsScreenState extends State<TicketsSettingsScreen> {
  XFile? _newTicketImage;
  XFile? _newTicketLogo;
  final ImagePicker _picker = ImagePicker();
  late final QuillController _quillController;
  final FocusNode _quillFocus = FocusNode();
  bool _initialized = false;

  @override
  void initState() {
    super.initState();
    _quillController = QuillController.basic();
    if (widget.eventId != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        context.read<TicketSettingsProvider>().fetchSettings(widget.eventId!);
      });
    }
  }

  @override
  void dispose() {
    _quillController.dispose();
    _quillFocus.dispose();
    super.dispose();
  }

  /// Called once after settings are loaded to populate the Quill editor.
  void _initFromProvider(TicketSettingsProvider provider) {
    if (!_initialized && provider.settings != null) {
      final html = provider.settings!.instructions;
      if (html.isNotEmpty) {
        final delta = HtmlToDelta().convert(html);
        _quillController.document = Document.fromDelta(delta);
      }
      _initialized = true;
    }
  }

  /// Extract HTML from Quill document for saving.
  String _getInstructionsHtml() {
    final text = _quillController.document.toPlainText().trim();
    return text;
  }

  Future<void> _pickImage(bool isLogo) async {
    final picked = await _picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
    );
    if (picked != null) {
      setState(() {
        if (isLogo) {
          _newTicketLogo = picked;
        } else {
          _newTicketImage = picked;
        }
      });
    }
  }

  Future<void> _save() async {
    if (widget.eventId == null) return;
    final provider = context.read<TicketSettingsProvider>();
    await provider.saveSettings(
      eventId: widget.eventId!,
      ticketImagePath: _newTicketImage?.path,
      ticketLogoPath: _newTicketLogo?.path,
      instructions: _getInstructionsHtml(),
    );
    if (!mounted) return;
    if (provider.savedSuccessfully) {
      CustomSnackBar.show(
        context: context,
        message: 'Ticket settings updated successfully',
      );
      provider.resetSaveState();
      // Reload to show updated images
      setState(() {
        _newTicketImage = null;
        _newTicketLogo = null;
        _initialized = false;
      });
      provider.fetchSettings(widget.eventId!);
    } else if (provider.saveError != null) {
      CustomSnackBar.show(
        context: context,
        type: SnackBarType.error,
        message: provider.saveError!,
      );
      provider.resetSaveState();
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Scaffold(
      appBar: const CustomAppBar(title: 'Ticket Settings'),
      body: Consumer<TicketSettingsProvider>(
        builder: (context, provider, _) {
          _initFromProvider(provider);

          if (provider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          if (provider.error != null) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      Icons.error_outline_rounded,
                      size: 56,
                      color: theme.colorScheme.error,
                    ),
                    const SizedBox(height: 14),
                    Text(
                      provider.error!,
                      textAlign: TextAlign.center,
                      style: TextStyle(color: theme.colorScheme.error),
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton.icon(
                      onPressed: widget.eventId != null
                          ? () => provider.fetchSettings(widget.eventId!)
                          : null,
                      icon: const Icon(Icons.refresh),
                      label: const Text('Retry'),
                    ),
                  ],
                ),
              ),
            );
          }

          if (widget.eventId == null) {
            return const Center(
              child: Text('No event selected. Please open from an event card.'),
            );
          }

          final settings = provider.settings;

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // ── Ticket Image ──────────────────────────────────
                CustomHeaderTextWidget(text: 'Ticket Image *'),
                const SizedBox(height: 8),
                _ImagePickerTile(
                  label: 'Tap to upload Ticket Image',
                  hint: 'Best Size: 255×390',
                  icon: FontAwesomeIcons.ticket,
                  newFile: _newTicketImage,
                  networkUrl: settings?.ticketImage,
                  isDark: isDark,
                  onPick: () => _pickImage(false),
                  onRemoveNew: () => setState(() => _newTicketImage = null),
                ),

                const SizedBox(height: 20),

                // ── Ticket Logo ───────────────────────────────────
                CustomHeaderTextWidget(text: 'Ticket Logo *'),
                const SizedBox(height: 8),
                _ImagePickerTile(
                  label: 'Tap to upload Ticket Logo',
                  hint: 'Best Size: 200×50',
                  icon: FontAwesomeIcons.cameraRetro,
                  newFile: _newTicketLogo,
                  networkUrl: settings?.ticketLogo,
                  isDark: isDark,
                  onPick: () => _pickImage(true),
                  onRemoveNew: () => setState(() => _newTicketLogo = null),
                ),

                const SizedBox(height: 20),

                // ── Instructions (Quill) ──────────────────────────
                CustomHeaderTextWidget(text: 'Instructions'),
                const SizedBox(height: 8),
                _QuillSection(
                  controller: _quillController,
                  focusNode: _quillFocus,
                  isDark: isDark,
                ),

                const SizedBox(height: 90),
              ],
            ),
          );
        },
      ),
      bottomNavigationBar: SafeArea(
        child: Consumer<TicketSettingsProvider>(
          builder: (context, provider, _) => Container(
            decoration: BoxDecoration(
              color: isDark ? Colors.black : Colors.white,
              boxShadow: [
                BoxShadow(
                  color: isDark ? Colors.grey.shade900 : Colors.grey.shade200,
                  blurRadius: 10,
                  offset: const Offset(0, -5),
                ),
              ],
            ),
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: ElevatedButton(
              onPressed: provider.isSaving || widget.eventId == null
                  ? null
                  : _save,
              child: provider.isSaving
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text('Update Settings'),
            ),
          ),
        ),
      ),
    );
  }
}

// ─────────────────────── Quill Section ──────────────────────────────────────

class _QuillSection extends StatelessWidget {
  final QuillController controller;
  final FocusNode focusNode;
  final bool isDark;

  const _QuillSection({
    required this.controller,
    required this.focusNode,
    required this.isDark,
  });

  @override
  Widget build(BuildContext context) {
    final border = isDark ? Colors.grey.shade700 : Colors.grey.shade300;

    return Container(
      decoration: BoxDecoration(
        border: Border.all(color: border),
        borderRadius: BorderRadius.circular(12),
        color: isDark ? Colors.grey.shade900 : Colors.white,
      ),
      child: Column(
        children: [
          // Toolbar
          QuillSimpleToolbar(
            controller: controller,
            config: QuillSimpleToolbarConfig(
              showFontFamily: false,
              showFontSize: false,
              showSubscript: false,
              showSuperscript: false,
              showInlineCode: false,
              showCodeBlock: false,
              showSearchButton: false,
              showClipboardCopy: false,
              showClipboardCut: false,
              showClipboardPaste: false,
              decoration: BoxDecoration(
                color: isDark ? Colors.grey.shade800 : Colors.grey.shade100,
                borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(12),
                ),
              ),
            ),
          ),
          Divider(height: 1, color: border),
          // Editor
          SizedBox(
            height: 240,
            child: QuillEditor.basic(
              controller: controller,
              focusNode: focusNode,
              config: QuillEditorConfig(
                padding: const EdgeInsets.all(14),
                placeholder: 'Enter ticket instructions...',
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ─────────────────────── Image Picker Tile ───────────────────────────────────

class _ImagePickerTile extends StatelessWidget {
  final String label;
  final String hint;
  final IconData icon;
  final XFile? newFile;
  final String? networkUrl;
  final bool isDark;
  final VoidCallback onPick;
  final VoidCallback onRemoveNew;

  const _ImagePickerTile({
    required this.label,
    required this.hint,
    required this.icon,
    required this.newFile,
    required this.networkUrl,
    required this.isDark,
    required this.onPick,
    required this.onRemoveNew,
  });

  bool get _hasNewImage => newFile != null;
  bool get _hasNetworkImage => networkUrl != null && networkUrl!.isNotEmpty;
  bool get _hasAny => _hasNewImage || _hasNetworkImage;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        GestureDetector(
          onTap: _hasAny ? null : onPick,
          child: DottedBorder(
            options: RoundedRectDottedBorderOptions(
              color: Colors.grey,
              dashPattern: const [10, 5],
              strokeWidth: 1,
              radius: const Radius.circular(10),
            ),
            child: SizedBox(
              height: 160,
              width: double.infinity,
              child: _hasAny
                  ? Stack(
                      children: [
                        Center(
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(8),
                            child: _hasNewImage
                                ? Image.file(
                                    File(newFile!.path),
                                    height: 150,
                                    fit: BoxFit.cover,
                                  )
                                : Image.network(
                                    networkUrl!,
                                    height: 150,
                                    fit: BoxFit.cover,
                                    loadingBuilder: (_, child, progress) =>
                                        progress == null
                                        ? child
                                        : const Center(
                                            child: CircularProgressIndicator(),
                                          ),
                                    errorBuilder: (_, _, e) => const Icon(
                                      Icons.broken_image_outlined,
                                      size: 48,
                                    ),
                                  ),
                          ),
                        ),
                        // Change button
                        Positioned(
                          bottom: 6,
                          left: 6,
                          child: GestureDetector(
                            onTap: onPick,
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                                vertical: 5,
                              ),
                              decoration: BoxDecoration(
                                color: Colors.black54,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: const Text(
                                'Change',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                          ),
                        ),
                        // Remove only for locally-picked new images
                        if (_hasNewImage)
                          Positioned(
                            top: 4,
                            right: 4,
                            child: GestureDetector(
                              onTap: onRemoveNew,
                              child: Container(
                                padding: const EdgeInsets.all(4),
                                decoration: const BoxDecoration(
                                  color: Colors.red,
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(
                                  Icons.close,
                                  color: Colors.white,
                                  size: 16,
                                ),
                              ),
                            ),
                          ),
                      ],
                    )
                  : Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(icon, size: 48, color: Colors.grey.shade500),
                        const SizedBox(height: 12),
                        Text(label, style: AppTextStyles.bodyLargeGrey),
                      ],
                    ),
            ),
          ),
        ),
        const SizedBox(height: 5),
        Text(
          'Note: $hint',
          style: AppTextStyles.bodySmall.copyWith(color: Colors.amber),
        ),
      ],
    );
  }
}
