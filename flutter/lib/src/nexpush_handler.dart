part of '../nexpush.dart';

class _NexPushHandler {
  static Future<void> _onForeground(RemoteMessage message) async {
    final msg = NexPushMessage.fromRemote(message);

    // Show local notification while app is in foreground
    if (NexPush._config?.showForegroundNotification == true) {
      await _NexPushLocal._show(
        NexPush._localNotif,
        id: message.hashCode,
        title: msg.title ?? '',
        body: msg.body ?? '',
        payload: jsonEncode(msg.data),
        imageUrl: msg.imageUrl,
      );
    }

    NexPush._onMessage?.call(msg);
  }

  static Future<void> _onOpenedApp(RemoteMessage message) async {
    final msg = NexPushMessage.fromRemote(message);
    NexPush._onMessageOpenedApp?.call(msg);
  }
}
