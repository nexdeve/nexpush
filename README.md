<div align="center">

<img src="https://capsule-render.vercel.app/api?type=waving&color=0:0a0e1a,50:f59e0b,100:ef4444&height=200&section=header&text=NexPush&fontSize=68&fontColor=ffffff&animation=fadeIn&fontAlignY=40&desc=Instant%20Push%20Notifications%20%7C%20No%20VPS%20Needed&descAlignY=60&descColor=fde68a&descSize=18" />

<a href="https://nexdeve.com">
  <img src="https://readme-typing-svg.demolab.com?font=Fira+Code&size=18&duration=2500&pause=800&color=F59E0B&center=true&vCenter=true&width=700&lines=Instant+push+notifications+via+FCM;Flutter+%7C+Laravel+%7C+Android+%7C+iOS+%7C+Python+%7C+Node.js;No+VPS+needed+%E2%80%94+Google+FCM+does+the+work;Works+like+Messenger%2C+WhatsApp%2C+Telegram;Powered+by+NexDeve+%7C+nexdeve.com" />
</a>

<br/>

[![Flutter](https://img.shields.io/badge/Flutter-02569B?style=for-the-badge&logo=flutter&logoColor=white)](https://pub.dev/packages/nexpush)
[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://packagist.org/packages/nexdeve/nexpush)
[![Python](https://img.shields.io/badge/Python-3776AB?style=for-the-badge&logo=python&logoColor=white)](https://pypi.org/project/nexpush)
[![Node.js](https://img.shields.io/badge/Node.js-339933?style=for-the-badge&logo=node.js&logoColor=white)](https://npmjs.com/package/@nexdeve/nexpush)
[![Android](https://img.shields.io/badge/Android-3DDC84?style=for-the-badge&logo=android&logoColor=white)](https://github.com/nexdeve/nexpush)
[![iOS](https://img.shields.io/badge/iOS-000000?style=for-the-badge&logo=apple&logoColor=white)](https://github.com/nexdeve/nexpush)

[![License](https://img.shields.io/badge/License-Apache_2.0-6366f1?style=for-the-badge)](https://opensource.org/licenses/Apache-2.0)
[![FCM](https://img.shields.io/badge/Powered%20by-Firebase%20FCM-F59E0B?style=for-the-badge&logo=firebase&logoColor=white)](https://firebase.google.com)
[![Author](https://img.shields.io/badge/By-NexDeve-076AF4?style=for-the-badge)](https://nexdeve.com)

**The simplest way to send instant push notifications — Flutter + Laravel + All Platforms**
By [nexdeve.com](https://nexdeve.com) · [Telegram Community](https://t.me/+c34_uTIBJEpkZGM9)

</div>

---

## How It Works — No VPS Needed!

```
Your Laravel Backend ──────────────► Firebase FCM (Google's Servers)
        │                                       │
        │  POST /fcm/send                       │  Instant delivery
        │  (1 API call)                         ▼
        │                              Android / iOS Device
        │                              (notification in < 1 second)
        ▼
   MySQL Database
 (stores FCM tokens)
```

> **Why no VPS?** Firebase Cloud Messaging (FCM) is Google's always-on push service — the same technology used by Messenger, WhatsApp, Telegram, and Instagram. Your Laravel backend calls FCM's API once, and Google handles the delivery to all devices instantly. **100% free for unlimited notifications.**

---

## Supported Platforms

| Platform | Package | Install |
|----------|---------|---------|
| Flutter (Android + iOS) | `nexpush` | `flutter pub add nexpush` |
| Laravel (Backend) | `nexdeve/nexpush` | `composer require nexdeve/nexpush` |
| Node.js / TypeScript | `@nexdeve/nexpush` | `npm install @nexdeve/nexpush` |
| Python | `nexpush` | `pip install nexpush` |
| Android (Native) | `ai.nextech:nexpush` | `implementation 'ai.nextech:nexpush:1.0.0'` |

---

## Quick Start

### Step 1 — Firebase Setup (5 minutes)

```
1. Go to https://console.firebase.google.com
2. Create project → Add Android/iOS app
3. Download google-services.json → put in android/app/
4. Project Settings → Cloud Messaging → Copy Server Key
```

### Step 2 — Laravel Backend

```bash
composer require nexdeve/nexpush
php artisan vendor:publish --tag=nexpush-config
php artisan migrate
```

```env
# .env
NEXPUSH_FCM_SERVER_KEY=AAAAxxxxxxx:APA91bxxxxxx...
NEXPUSH_API_KEY=your-secret-key-for-flutter-app
```

```php
// Send notification anywhere in your Laravel app

use NexDeve\NexPush\Facades\NexPush;

// Send to specific user (all their devices)
NexPush::sendToUser(
    userId: $user->id,
    title:  'New Message',
    body:   'Ahmed sent you a message',
    data:   ['chat_id' => '123', 'screen' => 'chat'],
);

// Send to single device token
NexPush::send(
    token:    $deviceToken,
    title:    'Payment Received',
    body:     'BDT 500 received from Rahim',
    imageUrl: 'https://cdn.myapp.com/payment.png',
);

// Broadcast to ALL users
NexPush::broadcast(
    title: 'New Feature',
    body:  'Check out our latest update!',
);

// Send to topic
NexPush::sendToTopic(
    topic: 'premium_users',
    title: 'Exclusive Offer',
    body:  'Special discount just for you!',
);
```

### Step 3 — Flutter App

```bash
flutter pub add nexpush firebase_core
```

```dart
// main.dart
import 'package:nexpush/nexpush.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await NexPush.init(
    config: NexPushConfig(
      serverUrl: 'https://api.myapp.com',
      serverKey: 'your-secret-key',
    ),
    onMessage: (msg) {
      // App is OPEN — show in-app notification
      showInAppBanner(title: msg.title!, body: msg.body!);
    },
    onMessageOpenedApp: (msg) {
      // User tapped notification — navigate
      final screen = msg.data['screen'] as String?;
      if (screen == 'chat') navigateToChat(msg.data['chat_id']);
    },
    onTokenRefresh: (token) {
      // New token — already auto-registered with backend
      print('Token refreshed: $token');
    },
  );

  runApp(MyApp());
}
```

```dart
// Subscribe to topics
await NexPush.subscribe('premium_users');
await NexPush.subscribe('news');
await NexPush.unsubscribe('promotions');

// Show local notification (no internet needed)
await NexPush.showLocal(
  title: 'Reminder',
  body:  'You have a meeting in 5 minutes',
  data:  {'type': 'reminder'},
);

// Get FCM token
final token = NexPush.token;
print('My FCM Token: $token');
```

### Node.js

```typescript
import { NexPush } from '@nexdeve/nexpush';

const push = new NexPush({ fcmServerKey: process.env.FCM_SERVER_KEY! });

// Send to user's devices
await push.sendToUser('user_42', 'Hello', 'You have a new message!', {}, userTokens);

// Broadcast
await push.broadcast('System Update', 'New version available!');
```

### Python

```python
from nexpush import NexPush, NexPushConfig

push = NexPush(NexPushConfig(fcm_server_key=os.environ['FCM_SERVER_KEY']))

# Send notification
result = push.send(
    token='device-fcm-token',
    title='Hello from Python!',
    body='NexPush makes push notifications easy.',
    data={'type': 'alert', 'priority': 'high'},
)
print(result)  # NexPushResult(success=True, success_count=1)

# Async send (non-blocking)
result = await push.send_async(token, 'Title', 'Body')

# Broadcast to topic
push.broadcast(title='News', body='Check out the latest update!')
```

---

## API Reference

### Flutter `NexPush`

| Method | Description |
|--------|-------------|
| `NexPush.init(config, onMessage, ...)` | Initialize — call once in `main()` |
| `NexPush.token` | Get current FCM token |
| `NexPush.send(toToken, title, body, ...)` | Send via backend |
| `NexPush.sendToTopic(topic, title, body)` | Send to topic |
| `NexPush.subscribe(topic)` | Subscribe to topic |
| `NexPush.unsubscribe(topic)` | Unsubscribe from topic |
| `NexPush.showLocal(title, body, data)` | Show local notification |
| `NexPush.hasPermission()` | Check notification permission |
| `NexPush.registerToken(token, userId)` | Register token with backend |

### Laravel `NexPush` Facade

| Method | Description |
|--------|-------------|
| `NexPush::send($token, $title, $body, $data)` | Send to single device |
| `NexPush::sendToUser($userId, $title, $body)` | Send to all user devices |
| `NexPush::sendMultiple($tokens, $title, $body)` | Send to multiple devices (batch) |
| `NexPush::sendToTopic($topic, $title, $body)` | Send to topic |
| `NexPush::broadcast($title, $body)` | Send to ALL devices |

### Laravel API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/nexpush/register` | POST | Register FCM token |
| `/api/nexpush/send` | POST | Send notification |
| `/api/nexpush/send-to-user/{id}` | POST | Send to user |
| `/api/nexpush/broadcast` | POST | Broadcast to all |
| `/api/nexpush/unregister` | DELETE | Remove token |

### `NexPushMessage` (Flutter)

```dart
msg.id          // Unique message ID
msg.title       // Notification title
msg.body        // Notification body
msg.imageUrl    // Image URL (optional)
msg.data        // Custom data map
msg.receivedAt  // DateTime received
msg.type        // notification / data / both
```

---

## Database Schema

```sql
CREATE TABLE nexpush_tokens (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NULL,
  token       VARCHAR(512) UNIQUE NOT NULL,  -- FCM device token
  platform    ENUM('android','ios','web') DEFAULT 'android',
  app_version VARCHAR(20)  NULL,
  active      TINYINT(1)   DEFAULT 1,
  last_seen   TIMESTAMP    NULL,
  created_at  TIMESTAMP    NULL,
  updated_at  TIMESTAMP    NULL,
  deleted_at  TIMESTAMP    NULL,             -- soft delete
  INDEX (user_id, active),
  INDEX (platform)
);
```

---

## Real-World Example — Chat App

```php
// MessageController.php
public function store(Request $request)
{
    $message = Message::create([
        'sender_id'    => auth()->id(),
        'receiver_id'  => $request->receiver_id,
        'content'      => $request->content,
    ]);

    // Send instant push to receiver
    NexPush::sendToUser(
        userId: $message->receiver_id,
        title:  auth()->user()->name,
        body:   $message->content,
        data:   [
            'screen'  => 'chat',
            'chat_id' => (string) $message->sender_id,
            'type'    => 'message',
        ],
    );

    return response()->json($message);
}
```

```dart
// Flutter — handle incoming message
NexPush.init(
  config: config,
  onMessage: (msg) {
    if (msg.data['type'] == 'message') {
      // Show chat bubble
      ChatOverlay.show(
        sender: msg.title!,
        text:   msg.body!,
        chatId: msg.data['chat_id'],
      );
    }
  },
  onMessageOpenedApp: (msg) {
    // Navigate to chat screen
    Navigator.pushNamed(context, '/chat',
        arguments: msg.data['chat_id']);
  },
);
```

---

## Why NexPush?

| Feature | NexPush | Others |
|---------|---------|--------|
| No VPS / server needed | ✅ | ❌ |
| Flutter + Laravel together | ✅ | ❌ |
| Messenger-like instant delivery | ✅ | ✅ |
| Topic broadcasting | ✅ | Partial |
| Local notifications | ✅ | Partial |
| Batch send (500 devices) | ✅ | ❌ |
| Async Python API | ✅ | ❌ |
| Free (FCM is free) | ✅ | ✅ |
| Open source | ✅ | ❌ |
| Powered by NexDeve | ✅ | ❌ |

---

## All NexDeve Libraries

Explore 75+ open-source tools at [github.com/nexdeve](https://github.com/nexdeve)

---

<div align="center">

<img src="https://capsule-render.vercel.app/api?type=waving&color=0:ef4444,50:f59e0b,100:0a0e1a&height=120&section=footer" />

**NexPush — Instant notifications, zero complexity.**

Made with ❤️ by [**NexDeve**](https://nexdeve.com) · [nexdeve.com](https://nexdeve.com) · [Telegram](https://t.me/+c34_uTIBJEpkZGM9)

</div>
