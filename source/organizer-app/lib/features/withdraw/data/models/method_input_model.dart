/// Model for a single dynamic form field returned by
/// GET /organizer/withdraw/get-method-input/{methodId}
class MethodInputField {
  final int id;
  final String type; // "1"=text, "2"=textarea, "3"=select, "4"=checkbox,
                     // "5"=radio, "6"=file, "7"=phone, etc.
  final String label;
  final String name;
  final String placeholder;
  final bool required;
  final int orderNumber;
  final List<String> options; // populated for select / checkbox / radio

  MethodInputField({
    required this.id,
    required this.type,
    required this.label,
    required this.name,
    required this.placeholder,
    required this.required,
    required this.orderNumber,
    required this.options,
  });

  factory MethodInputField.fromJson(Map<String, dynamic> json) {
    final rawOptions = json['options'];
    List<String> opts = [];
    if (rawOptions is List) {
      opts = rawOptions.map((o) => o.toString()).toList();
    }
    return MethodInputField(
      id: json['id'] as int? ?? 0,
      type: json['type']?.toString() ?? '1',
      label: json['label']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      placeholder: json['placeholder']?.toString() ?? '',
      required: json['required']?.toString() == '1',
      orderNumber: int.tryParse(json['order_number']?.toString() ?? '0') ?? 0,
      options: opts,
    );
  }

  /// Whether this field accepts a single line of text (phone, text, email, etc.)
  bool get isTextField =>
      type == '1' || type == '7' || type == '8' || type == '9';

  /// Textarea
  bool get isTextArea => type == '2';

  /// Select dropdown
  bool get isSelect => type == '3';

  /// Checkbox (multi-select)
  bool get isCheckbox => type == '4';

  /// Radio
  bool get isRadio => type == '5';
}
