/**
 * NexPush Node.js — Instant push notifications via FCM
 * Powered by NexDeve | nexdeve.com
 */

export interface NexPushConfig {
  fcmServerKey: string;
  projectId?: string;
  timeout?: number;
}

export interface NexPushResult {
  success: boolean;
  messageId?: string;
  successCount: number;
  failureCount: number;
  error?: string;
}

export interface SendOptions {
  token: string;
  title: string;
  body: string;
  data?: Record<string, unknown>;
  imageUrl?: string;
  sound?: string;
  priority?: 'normal' | 'high';
  clickAction?: string;
  channelId?: string;
  badge?: number;
}

const FCM_URL = 'https://fcm.googleapis.com/fcm/send';

export class NexPush {
  private headers: Record<string, string>;
  private config: Required<NexPushConfig>;

  constructor(config: NexPushConfig) {
    this.config = {
      fcmServerKey: config.fcmServerKey,
      projectId: config.projectId ?? '',
      timeout: config.timeout ?? 10000,
    };
    this.headers = {
      Authorization: `key=${this.config.fcmServerKey}`,
      'Content-Type': 'application/json',
    };
  }

  // ── Send ──────────────────────────────────────────────────────────────────

  async send(opts: SendOptions): Promise<NexPushResult> {
    const payload = this.buildPayload(opts);
    return this.dispatch(payload);
  }

  async sendToUser(
    userId: string | number,
    title: string,
    body: string,
    data: Record<string, unknown> = {},
    tokens: string[]
  ): Promise<NexPushResult> {
    if (!tokens.length) return { success: false, successCount: 0, failureCount: 0, error: 'No tokens' };
    return this.sendMultiple(tokens, title, body, data);
  }

  async sendMultiple(
    tokens: string[],
    title: string,
    body: string,
    data: Record<string, unknown> = {},
    imageUrl?: string
  ): Promise<NexPushResult> {
    const chunks = this.chunk(tokens, 500);
    let successCount = 0, failureCount = 0;

    for (const chunk of chunks) {
      const payload = this.buildMulticast(chunk, title, body, data, imageUrl);
      const result = await this.dispatch(payload);
      successCount += result.successCount;
      failureCount += result.failureCount;
    }

    return { success: failureCount === 0, successCount, failureCount };
  }

  async sendToTopic(
    topic: string,
    title: string,
    body: string,
    data: Record<string, unknown> = {}
  ): Promise<NexPushResult> {
    return this.send({ token: `/topics/${topic}`, title, body, data });
  }

  async broadcast(
    title: string,
    body: string,
    data: Record<string, unknown> = {}
  ): Promise<NexPushResult> {
    return this.sendToTopic('all', title, body, data);
  }

  // ── Helpers ───────────────────────────────────────────────────────────────

  private buildPayload(opts: SendOptions): Record<string, unknown> {
    const notification: Record<string, unknown> = {
      title: opts.title,
      body: opts.body,
    };
    if (opts.imageUrl) notification.image = opts.imageUrl;

    return {
      to: opts.token,
      priority: opts.priority ?? 'high',
      notification,
      data: { ...opts.data, nexpush: 'true', click_action: opts.clickAction ?? 'FLUTTER_NOTIFICATION_CLICK' },
      android: {
        priority: 'HIGH',
        notification: {
          channel_id: opts.channelId ?? 'nexpush_channel',
          sound: opts.sound ?? 'default',
          notification_priority: 'PRIORITY_MAX',
          visibility: 'PUBLIC',
          default_sound: true,
          default_vibrate_timings: true,
        },
      },
      apns: {
        headers: { 'apns-priority': '10' },
        payload: {
          aps: {
            alert: { title: opts.title, body: opts.body },
            sound: opts.sound ?? 'default',
            badge: opts.badge ?? 1,
            'content-available': 1,
          },
        },
      },
    };
  }

  private buildMulticast(
    tokens: string[], title: string, body: string,
    data: Record<string, unknown>, imageUrl?: string
  ): Record<string, unknown> {
    const p = this.buildPayload({ token: '', title, body, data, imageUrl });
    delete (p as Record<string, unknown>).to;
    (p as Record<string, unknown>).registration_ids = tokens;
    return p;
  }

  private async dispatch(payload: Record<string, unknown>): Promise<NexPushResult> {
    try {
      const res = await fetch(FCM_URL, {
        method: 'POST',
        headers: this.headers,
        body: JSON.stringify(payload),
        signal: AbortSignal.timeout(this.config.timeout),
      });

      const body = await res.json() as Record<string, unknown>;

      if (res.ok) {
        return {
          success: true,
          messageId: body.message_id as string,
          successCount: (body.success as number) ?? 1,
          failureCount: (body.failure as number) ?? 0,
        };
      }

      return { success: false, successCount: 0, failureCount: 1, error: body.error as string };
    } catch (e: unknown) {
      return { success: false, successCount: 0, failureCount: 1, error: String(e) };
    }
  }

  private chunk<T>(arr: T[], size: number): T[][] {
    return Array.from({ length: Math.ceil(arr.length / size) }, (_, i) =>
      arr.slice(i * size, i * size + size)
    );
  }
}

export default NexPush;
