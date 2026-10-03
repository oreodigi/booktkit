import 'package:booktkit_organizer/features/common/ui/widgets/form_header_text_widget.dart';
import 'package:flutter/material.dart';

class CustomTextField extends StatelessWidget {
  final String? hintText;
  final String? headerText;
  final double fontSize;
  final FontWeight fontWeight;
  final int maxLine;
  final TextEditingController textEditingController;
  const CustomTextField({
    super.key,
    this.hintText,
    this.headerText,
    this.fontSize = 16,
    this.fontWeight = FontWeight.w500,
    this.maxLine = 1,
    required this.textEditingController,
  });

  @override
  Widget build(BuildContext context) {

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        FormHeaderTextWidget(
          text: headerText ?? '',
          fontSize: fontSize,
          fontWeight: fontWeight,
        ),
        const SizedBox(height: 4),
        TextFormField(
          controller: textEditingController,
          decoration: InputDecoration(
            hintText: hintText,
            hintStyle: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w500,
            ),
          ),
          maxLines: maxLine,
        ),
      ],
    );
  }
}
