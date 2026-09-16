library nexpush;

import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:http/http.dart' as http;

export 'src/nexpush_message.dart';
export 'src/nexpush_config.dart';

part 'src/nexpush_core.dart';
part 'src/nexpush_local.dart';
part 'src/nexpush_handler.dart';

/// NexPush — Instant push notifications for Flutter
/// Powered by FCM (Firebase Cloud Messaging)
/// No VPS needed — works with any backend
///
/// Quick start:
/// ```dart
/// await NexPush.init(
///   config: NexPushConfig(
///     serverUrl: 'https://yourapi.com',
///     serverKey: 'your-server-key',
///   ),
///   onMessage: (msg) => print('Received: ${msg.title}'),
/// );
/// ```
class NexPush {
  NexPush._();

  static final NexPush _instance = NexPush._();
  static NexPush get instance => _instance;

  static NexPushConfig? _config;
  static FirebaseMessaging? _fcm;
  static final FlutterLocalNotificationsPlugin _localNotif =
      FlutterLocalNotificationsPlugin();

  static Function(NexPushMessage)? _onMessage;
  static Function(NexPushMessage)? _onMessageOpenedApp;
  static Function(String token)? _onTokenRefresh;
  static Function(String error)? _onError;

  static String? _fcmToken;
  static bool _initialized = false;

  // ── Init ──────────────────────────────────────────────────────────────────

  /// Initialize NexPush — call once in main()
  static Future<void> init({
    required NexPushConfig config,
    Function(NexPushMessage msg)? onMessage,
    Function(NexPushMessage msg)? onMessageOpenedApp,
    Function(String token)? onTokenRefresh,
    Function(String error)? onError,
    bool requestPermission = true,
  }) async {
    _config = config;
    _onMessage = onMessage;
    _onMessageOpenedApp = onMessageOpenedApp;
    _onTokenRefresh = onTokenRefresh;
    _onError = onError;

    await Firebase.initializeApp();

    _fcm = FirebaseMessaging.instance;

    // Request permission (iOS + Android 13+)
    if (requestPermission) {
      await _requestPermission();
    }

    // Setup local notifications
    await _NexPushLocal._init(_localNotif);

    // Get FCM token
    _fcmToken = await _fcm!.getToken();
    if (_fcmToken != null) {
      debugPrint('[NexPush] FCM Token: $_fcmToken');
      if (config.autoRegister) {
        await registerToken(_fcmToken!);
      }
    }

    // Token refresh listener
    _fcm!.onTokenRefresh.listen((newToken) {
      _fcmToken = newToken;
      _onTokenRefresh?.call(newToken);
      if (config.autoRegister) {
        registerToken(newToken);
      }
    });

    // Foreground messages
    FirebaseMessaging.onMessage.listen(_NexPushHandler._onForeground);

    // Background/terminated → app opened
    FirebaseMessaging.onMessageOpenedApp.listen(_NexPushHandler._onOpenedApp);

    // Background handler (must be top-level function)
    FirebaseMessaging.onBackgroundMessage(_backgroundHandler);

    _initialized = true;
    debugPrint('[NexPush] Initialized successfully');
  }

  // ── Token ─────────────────────────────────────────────────────────────────

  /// Get current FCM token
  static String? get token => _fcmToken;

  /// Register FCM token with your Laravel backend
  static Future<bool> registerToken(
    String token, {
    String? userId,
    Map<String, String>? extraHeaders,
  }) async {
    if (_config == null) return false;

    try {
      final res = await http.post(
        Uri.parse('${_config!.serverUrl}/api/nexpush/register'),
        headers: {
          'Content-Type': 'application/json',
          'X-NexPush-Key': _config!.serverKey,
          'X-Platform': Platform.isIOS ? 'ios' : 'android',
          ...?extraHeaders,
        },
        body: jsonEncode({
          'token': token,
          'user_id': userId,
          'platform': Platform.isIOS ? 'ios' : 'android',
          'app_version': _config!.appVersion,
        }),
      );
      return res.statusCode == 200;
    } catch (e) {
      _onError?.call('Token registration failed: $e');
      return false;
    }
  }

  // ── Subscribe ─────────────────────────────────────────────────────────────

  /// Subscribe to a topic (e.g. "news", "offers", "user_123")
  static Future<void> subscribe(String topic) async {
    await _fcm?.subscribeToTopic(topic);
    debugPrint('[NexPush] Subscribed to: $topic');
  }

  /// Unsubscribe from a topic
  static Future<void> unsubscribe(String topic) async {
    await _fcm?.unsubscribeFromTopic(topic);
  }

  // ── Send (direct from Flutter via server) ─────────────────────────────────

  /// Send notification to a specific user/token via your backend
  static Future<bool> send({
    required String toToken,
    required String title,
    required String body,
    Map<String, dynamic>? data,
    NexPushSound sound = NexPushSound.default_,
    String? imageUrl,
    String? clickAction,
    NexPushPriority priority = NexPushPriority.high,
  }) async {
    if (_config == null) return false;

    try {
      final res = await http.post(
        Uri.parse('${_config!.serverUrl}/api/nexpush/send'),
        headers: {
          'Content-Type': 'application/json',
          'X-NexPush-Key': _config!.serverKey,
        },
        body: jsonEncode({
          'to': toToken,
          'title': title,
          'body': body,
          'data': data ?? {},
          'sound': sound.name,
          'image_url': imageUrl,
          'click_action': clickAction,
          'priority': priority.name,
        }),
      );
      return res.statusCode == 200;
    } catch (e) {
      _onError?.call('Send failed: $e');
      return false;
    }
  }

  /// Send to topic
  static Future<bool> sendToTopic({
    required String topic,
    required String title,
    required String body,
    Map<String, dynamic>? data,
  }) async {
    return send(toToken: '/topics/$topic', title: title, body: body, data: data);
  }

  // ── Local Notification ────────────────────────────────────────────────────

  /// Show instant local notification (no server needed)
  static Future<void> showLocal({
    required String title,
    required String body,
    Map<String, dynamic>? data,
    String? imageUrl,
    int id = 0,
  }) async {
    await _NexPushLocal._show(
      _localNotif,
      id: id,
      title: title,
      body: body,
      payload: jsonEncode(data ?? {}),
    );
  }

  // ── Permission ────────────────────────────────────────────────────────────

  static Future<bool> _requestPermission() async {
    final settings = await _fcm!.requestPermission(
      alert: true,
      badge: true,
      sound: true,
      provisional: false,
    );
    return settings.authorizationStatus == AuthorizationStatus.authorized;
  }

  /// Check if notification permission is granted
  static Future<bool> hasPermission() async {
    final settings = await _fcm!.getNotificationSettings();
    return settings.authorizationStatus == AuthorizationStatus.authorized;
  }

  static bool get isInitialized => _initialized;
}

/// Background message handler — must be top-level
@pragma('vm:entry-point')
Future<void> _backgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  debugPrint('[NexPush] Background message: ${message.messageId}');
}
