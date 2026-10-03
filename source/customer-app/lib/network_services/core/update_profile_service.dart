import 'dart:convert';
import 'dart:io';
import 'package:booktkit_customer/app/urls.dart';
import 'package:http/http.dart' as http;
import 'package:booktkit_customer/network_services/core/http_headers.dart';

class UpdateProfileService {
  static Future<Map<String, dynamic>> updateProfile(
    String token,
    Map<String, String> fields, {
    String? imageFilePath,
  }) async {
    final uri = Uri.parse(AppUrls.updateProfile);
    final request = http.MultipartRequest('POST', uri);
  request.headers.addAll(HttpHeadersHelper.base());
    if (token.isNotEmpty) request.headers['Authorization'] = 'Bearer $token';
    fields.forEach((k, v) {
      request.fields[k] = v;
    });
    if (imageFilePath != null && imageFilePath.isNotEmpty) {
      final file = File(imageFilePath);
      if (await file.exists()) {
        final multipart = await http.MultipartFile.fromPath(
          'photo',
          imageFilePath,
        );
        request.files.add(multipart);
      }
    }
    final streamed = await request.send();
    final res = await http.Response.fromStream(streamed);
    if (res.statusCode >= 200 && res.statusCode < 300) {
      return json.decode(res.body) as Map<String, dynamic>;
    }
    throw Exception('Failed to update profile: ${res.statusCode} ${res.body}');
  }
}
