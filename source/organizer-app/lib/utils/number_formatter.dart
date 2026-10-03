/// Utility class for compact number formatting.
class NumberFormatter {
  /// Parses a numeric value from [value], stripping any currency symbols,
  /// commas, or spaces (e.g. "\$1,000,000.00" → 1000000.0).
  static double parseNum(dynamic value) {
    final raw = value?.toString() ?? '';
    // Remove everything except digits, dot and minus sign
    final cleaned = raw.replaceAll(RegExp(r'[^\d.\-]'), '');
    return double.tryParse(cleaned) ?? 0.0;
  }

  /// Formats a [value] into compact notation for values >= 1,000,000.
  ///
  /// Examples:
  ///   12900000   → "12.9M"
  ///   1200000000 → "1.2B"
  ///   3500000000000 → "3.5T"
  ///   999999     → "999999"
  static String compact(num value) {
    if (value >= 1000000000000) {
      return '${_trim(value / 1000000000000)}T';
    } else if (value >= 1000000000) {
      return '${_trim(value / 1000000000)}B';
    } else if (value >= 1000000) {
      return '${_trim(value / 1000000)}M';
    }
    return value.toStringAsFixed(0);
  }

  /// Returns [true] if the value requires compact formatting (>= 1,000,000).
  static bool needsCompact(num value) => value >= 1000000;

  static String _trim(double v) {
    final s = v.toStringAsFixed(1);
    return s.endsWith('.0') ? s.substring(0, s.length - 2) : s;
  }
}
