import 'package:booktkit_organizer/features/common/ui/widgets/custom_appbar.dart';
import 'package:booktkit_organizer/features/support_tickets/data/models/support_ticket_model.dart';
import 'package:booktkit_organizer/features/support_tickets/providers/support_tickets_provider.dart';
import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:file_picker/file_picker.dart';

// ─────────────────────────── Screen ────────────────────────────────────────

class TicketDetailsScreen extends StatefulWidget {
  final int ticketId;

  const TicketDetailsScreen({super.key, required this.ticketId});

  @override
  State<TicketDetailsScreen> createState() => _TicketDetailsScreenState();
}

class _TicketDetailsScreenState extends State<TicketDetailsScreen> {
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    final provider = context.read<SupportTicketsProvider>();
    Future.microtask(() => provider.fetchTicketDetail(widget.ticketId));
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBar(title: 'Ticket Details'),
      body: Consumer<SupportTicketsProvider>(
        builder: (context, provider, _) {
          if (provider.isDetailLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          if (provider.detailError != null) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.error_outline_rounded,
                      size: 52,
                      color: Colors.red.shade300,
                    ),
                    const SizedBox(height: 12),
                    Text(
                      provider.detailError!,
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: Colors.red),
                    ),
                    const SizedBox(height: 16),
                    ElevatedButton(
                      onPressed: () =>
                          provider.fetchTicketDetail(widget.ticketId),
                      child: const Text('Retry'),
                    ),
                  ],
                ),
              ),
            );
          }

          final ticket = provider.ticketDetail;
          if (ticket == null) return const SizedBox();

          _scrollToBottom();

          return Column(
            children: [
              _TicketHeader(ticket: ticket),
              Expanded(
                child: ticket.messages.isEmpty
                    ? Center(
                        child: Text(
                          'No messages yet.',
                          style: TextStyle(color: Colors.grey.shade500),
                        ),
                      )
                    : ListView.separated(
                        controller: _scrollController,
                        padding: const EdgeInsets.all(16),
                        itemCount: ticket.messages.length,
                        separatorBuilder: (_, _) => const SizedBox(height: 12),
                        itemBuilder: (context, index) =>
                            _MessageBubble(message: ticket.messages[index]),
                      ),
              ),
              if (ticket.status == '2')
                _ReplyInput(ticketId: widget.ticketId)
              else
                SafeArea(
                  child: Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: Theme.of(context).cardColor,
                      border: Border(
                        top: BorderSide(color: Theme.of(context).dividerColor),
                      ),
                    ),
                    child: Text(
                      ticket.status == '3'
                          ? 'This ticket is closed.'
                          : 'Replies are not available for this ticket.',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: Colors.grey.shade500,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

// ─────────────────────────── Ticket Header ─────────────────────────────────

class _TicketHeader extends StatelessWidget {
  final TicketDetail ticket;
  const _TicketHeader({required this.ticket});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final hasAttachment =
        ticket.attachment != null && ticket.attachment!.isNotEmpty;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.cardColor,
        border: Border(bottom: BorderSide(color: theme.dividerColor)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  '#${ticket.id}',
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              _StatusChip(status: ticket.status),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            ticket.subject,
            style: theme.textTheme.bodyLarge?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            ticket.email,
            style: theme.textTheme.bodyMedium?.copyWith(
              color: Colors.grey.shade600,
            ),
          ),
          if (hasAttachment) ...[
            const SizedBox(height: 12),
            // Backend returns the full URL for ticket attachment
            _DownloadButton(label: ticket.attachment!, url: ticket.attachment!),
          ],
        ],
      ),
    );
  }
}

// ─────────────────────────── Message Bubble ────────────────────────────────

class _MessageBubble extends StatelessWidget {
  final TicketMessage message;
  const _MessageBubble({required this.message});

  String _formatTime(DateTime dt) {
    final h = dt.hour.toString().padLeft(2, '0');
    final m = dt.minute.toString().padLeft(2, '0');
    return '$h:$m';
  }

  String _fileName(String url) {
    final parts = url.split('/');
    return parts.isNotEmpty ? parts.last : 'attachment';
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isAdmin = message.isAdmin;

    return Align(
      alignment: isAdmin ? Alignment.centerLeft : Alignment.centerRight,
      child: Container(
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.78,
        ),
        decoration: BoxDecoration(
          color: isAdmin
              ? theme.cardColor
              : theme.colorScheme.primary.withValues(alpha: 0.12),
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(14),
            topRight: const Radius.circular(14),
            bottomLeft: Radius.circular(isAdmin ? 0 : 14),
            bottomRight: Radius.circular(isAdmin ? 14 : 0),
          ),
          border: Border.all(color: theme.dividerColor.withValues(alpha: 0.7)),
        ),
        child: Column(
          crossAxisAlignment: isAdmin
              ? CrossAxisAlignment.start
              : CrossAxisAlignment.end,
          children: [
            Html(
              data: message.reply,
              style: {
                'body': Style(
                  margin: Margins.zero,
                  padding: HtmlPaddings.symmetric(horizontal: 12, vertical: 8),
                ),
                'p': Style(margin: Margins.zero),
              },
            ),
            // File attachment — URL comes directly from backend
            if (message.file != null && message.file!.isNotEmpty)
              Padding(
                padding: const EdgeInsets.fromLTRB(8, 0, 8, 4),
                child: _DownloadButton(
                  label: _fileName(message.file!),
                  url: message.file!,
                ),
              ),
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 0, 12, 8),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (isAdmin) ...[
                    Icon(
                      Icons.support_agent_rounded,
                      size: 12,
                      color: Colors.grey.shade500,
                    ),
                    const SizedBox(width: 4),
                  ],
                  Text(
                    _formatTime(message.createdAt),
                    style: theme.textTheme.labelSmall?.copyWith(
                      color: Colors.grey.shade500,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────── Download Button ───────────────────────────────

class _DownloadButton extends StatelessWidget {
  final String label;
  final String url;

  const _DownloadButton({required this.label, required this.url});

  Future<void> _open(BuildContext context) async {
    final messenger = ScaffoldMessenger.of(context);
    final uri = Uri.tryParse(url);
    if (uri == null) return;

    try {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {
      messenger.showSnackBar(
        const SnackBar(content: Text('Could not open the file.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return InkWell(
      onTap: () => _open(context),
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
        decoration: BoxDecoration(
          color: theme.colorScheme.primary.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: theme.colorScheme.primary.withValues(alpha: 0.25),
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.download_rounded,
              size: 16,
              color: theme.colorScheme.primary,
            ),
            const SizedBox(width: 6),
            Flexible(
              child: Text(
                label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: theme.colorScheme.primary,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────── Reply Input ───────────────────────────────────

class _ReplyInput extends StatefulWidget {
  final int ticketId;
  const _ReplyInput({required this.ticketId});

  @override
  State<_ReplyInput> createState() => _ReplyInputState();
}

class _ReplyInputState extends State<_ReplyInput> {
  final TextEditingController _controller = TextEditingController();
  String? _filePath;
  String? _fileName;

  void _pickFile() async {
    final result = await FilePicker.platform.pickFiles();
    if (result != null && result.files.single.path != null) {
      setState(() {
        _filePath = result.files.single.path;
        _fileName = result.files.single.name;
      });
    }
  }

  void _removeFile() {
    setState(() {
      _filePath = null;
      _fileName = null;
    });
  }

  void _send() async {
    final text = _controller.text.trim();
    if (text.isEmpty) return;

    final provider = context.read<SupportTicketsProvider>();
    final messenger = ScaffoldMessenger.of(context);

    final success = await provider.replyToTicket(
      ticketId: widget.ticketId,
      reply: text.isNotEmpty ? text : ' ',
      filePath: _filePath,
    );

    if (!mounted) return;

    if (success) {
      _controller.clear();
      _removeFile();
    } else {
      messenger.showSnackBar(
        SnackBar(content: Text(provider.sendError ?? 'Failed to send reply')),
      );
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
    final isSending = context.watch<SupportTicketsProvider>().isSending;

    return SafeArea(
      child: Container(
        padding: const EdgeInsets.fromLTRB(12, 8, 12, 8),
        decoration: BoxDecoration(
          color: theme.scaffoldBackgroundColor,
          border: Border(top: BorderSide(color: theme.dividerColor)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // File attachment chip
            if (_fileName != null)
              Padding(
                padding: const EdgeInsets.only(bottom: 6),
                child: Row(
                  children: [
                    Icon(Icons.attach_file_rounded,
                        size: 16, color: theme.colorScheme.primary),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        _fileName!,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: theme.colorScheme.primary,
                        ),
                      ),
                    ),
                    GestureDetector(
                      onTap: _removeFile,
                      child: Icon(Icons.close_rounded,
                          size: 18, color: Colors.red.shade400),
                    ),
                  ],
                ),
              ),
            Row(
              children: [
                // File picker button
                IconButton(
                  onPressed: isSending ? null : _pickFile,
                  icon: const Icon(Icons.attach_file_rounded),
                  color: theme.colorScheme.primary,
                ),
                Expanded(
                  child: TextField(
                    controller: _controller,
                    enabled: !isSending,
                    minLines: 1,
                    maxLines: 4,
                    decoration: InputDecoration(
                      hintText: 'Write a reply...',
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 10,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                isSending
                    ? const SizedBox(
                        width: 24,
                        height: 24,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : IconButton(
                        onPressed: _send,
                        icon: const Icon(Icons.send_rounded),
                        color: theme.colorScheme.primary,
                      ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────── Status Chip ───────────────────────────────────

class _StatusChip extends StatelessWidget {
  final String status;
  const _StatusChip({required this.status});

  @override
  Widget build(BuildContext context) {
    Color color;
    String label;

    switch (status) {
      case '1':
        color = Colors.orange.shade700;
        label = 'Pending';
        break;
      case '2':
        color = Colors.blue.shade700;
        label = 'Open';
        break;
      case '3':
        color = Colors.grey.shade700;
        label = 'Closed';
        break;
      default:
        color = Colors.orange.shade700;
        label = 'Pending';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withValues(alpha: 0.5)),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontWeight: FontWeight.w800,
          color: color,
          fontSize: 12,
        ),
      ),
    );
  }
}
