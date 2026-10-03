import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, TargetPlatform, kIsWeb;

/// Booktkit-owned build configuration. There is no supplier project fallback.
class DefaultFirebaseOptions {
  DefaultFirebaseOptions._();
  static const bool enabled = bool.fromEnvironment('BOOKTKIT_FIREBASE_ENABLED');
  static const String apiKey = String.fromEnvironment('FIREBASE_API_KEY');
  static const String appId = String.fromEnvironment('FIREBASE_APP_ID');
  static const String senderId = String.fromEnvironment('FIREBASE_SENDER_ID');
  static const String projectId = String.fromEnvironment('FIREBASE_PROJECT_ID');
  static const String storageBucket = String.fromEnvironment('FIREBASE_STORAGE_BUCKET');
  static bool get isConfigured => enabled && !kIsWeb &&
      defaultTargetPlatform == TargetPlatform.android &&
      apiKey.isNotEmpty && appId.isNotEmpty &&
      senderId.isNotEmpty && projectId.isNotEmpty;
  static FirebaseOptions get currentPlatform {
    if (!isConfigured) {
      throw StateError('Booktkit Firebase is disabled or unconfigured.');
    }
    return FirebaseOptions(
      apiKey: apiKey,
      appId: appId,
      messagingSenderId: senderId,
      projectId: projectId,
      storageBucket: storageBucket.isEmpty ? null : storageBucket,
    );
  }
}