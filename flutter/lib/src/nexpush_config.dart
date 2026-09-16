/// NexPush configuration
class NexPushConfig {
  /// Your Laravel backend URL (e.g. "https://api.myapp.com")
  final String serverUrl;

  /// Your NexPush server key (set in Laravel .env)
  final String serverKey;

  /// Auto-register FCM token with backend on init
  final bool autoRegister;

  /// App version for analytics
  final String appVersion;

  /// Show notification when app is in foreground
  final bool showForegroundNotification;

  const NexPushConfig({
    required this.serverUrl,
    required this.serverKey,
    this.autoRegister = true,
    this.appVersion = '1.0.0',
    this.showForegroundNotification = true,
  });
}

enum NexPushSound { default_, silent, custom }
enum NexPushPriority { normal, high }
