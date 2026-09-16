import 'package:firebase_messaging/firebase_messaging.dart';

/// Unified notification message model
class NexPushMessage {
  final String? id;
  final String? title;
  final String? body;
  final String? imageUrl;
  final Map<String, dynamic> data;
  final DateTime receivedAt;
  final NexPushMessageType type;

  NexPushMessage({
    this.id,
    this.title,
    this.body,
    this.imageUrl,
    this.data = const {},
    required this.receivedAt,
    this.type = NexPushMessageType.notification,
  });

  factory NexPushMessage.fromRemote(RemoteMessage msg) {
    return NexPushMessage(
      id: msg.messageId,
      title: msg.notification?.title,
      body: msg.notification?.body,
      imageUrl: msg.notification?.android?.imageUrl ??
                msg.notification?.apple?.imageUrl,
      data: Map<String, dynamic>.from(msg.data),
      receivedAt: msg.sentTime ?? DateTime.now(),
    );
  }

  Map<String, dynamic> toMap() => {
    'id': id,
    'title': title,
    'body': body,
    'imageUrl': imageUrl,
    'data': data,
    'receivedAt': receivedAt.toIso8601String(),
  };

  @override
  String toString() => 'NexPushMessage(title: $title, body: $body)';
}

enum NexPushMessageType { notification, data, both }
