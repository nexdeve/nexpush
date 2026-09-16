"""
NexPush Python — Instant push notifications via FCM
Powered by NexDeve | nexdeve.com
"""

import json
import asyncio
import aiohttp
import requests
from typing import Optional, Union
from dataclasses import dataclass, field


@dataclass
class NexPushConfig:
    fcm_server_key: str
    project_id: str = ""
    log: bool = True
    timeout: int = 10


@dataclass
class NexPushResult:
    success: bool
    message_id: Optional[str] = None
    success_count: int = 0
    failure_count: int = 0
    error: Optional[str] = None


FCM_URL = "https://fcm.googleapis.com/fcm/send"


class NexPush:
    """
    NexPush — Python FCM push notification sender

    Usage:
        push = NexPush(NexPushConfig(fcm_server_key="your-key"))
        push.send(token="device-token", title="Hello", body="World!")
        push.send_to_user(user_id=42, title="Hi", body="Message")
        push.broadcast(title="News", body="Important update!")
    """

    def __init__(self, config: NexPushConfig):
        self.config = config
        self._headers = {
            "Authorization": f"key={config.fcm_server_key}",
            "Content-Type": "application/json",
        }

    # ── Sync API ──────────────────────────────────────────────────────────────

    def send(
        self,
        token: str,
        title: str,
        body: str,
        data: dict = {},
        image_url: str = None,
        sound: str = "default",
        priority: str = "high",
        click_action: str = "FLUTTER_NOTIFICATION_CLICK",
    ) -> NexPushResult:
        payload = self._build_payload(
            token, title, body, data, image_url, sound, priority, click_action
        )
        return self._dispatch(payload)

    def send_to_user(
        self,
        user_id: Union[int, str],
        title: str,
        body: str,
        data: dict = {},
        db_session=None,
    ) -> NexPushResult:
        """
        Send to all devices of a user.
        Requires a DB session with nexpush_tokens table.
        """
        if db_session is None:
            return NexPushResult(success=False, error="db_session required")

        tokens = db_session.execute(
            "SELECT token FROM nexpush_tokens WHERE user_id=:uid AND active=1",
            {"uid": user_id}
        ).fetchall()

        if not tokens:
            return NexPushResult(success=False, error="No tokens found")

        return self.send_multiple(
            [t[0] for t in tokens], title, body, data
        )

    def send_multiple(
        self,
        tokens: list,
        title: str,
        body: str,
        data: dict = {},
        image_url: str = None,
    ) -> NexPushResult:
        success = failure = 0
        for chunk in [tokens[i:i+500] for i in range(0, len(tokens), 500)]:
            payload = self._build_multicast(chunk, title, body, data, image_url)
            result = self._dispatch(payload)
            success += result.success_count
            failure += result.failure_count
        return NexPushResult(
            success=failure == 0,
            success_count=success,
            failure_count=failure
        )

    def send_to_topic(
        self,
        topic: str,
        title: str,
        body: str,
        data: dict = {},
    ) -> NexPushResult:
        return self.send(f"/topics/{topic}", title, body, data)

    def broadcast(
        self,
        title: str,
        body: str,
        data: dict = {},
    ) -> NexPushResult:
        return self.send_to_topic("all", title, body, data)

    # ── Async API ─────────────────────────────────────────────────────────────

    async def send_async(
        self,
        token: str,
        title: str,
        body: str,
        data: dict = {},
    ) -> NexPushResult:
        payload = self._build_payload(token, title, body, data)
        return await self._dispatch_async(payload)

    async def send_multiple_async(
        self,
        tokens: list,
        title: str,
        body: str,
        data: dict = {},
    ) -> list:
        tasks = [self.send_async(t, title, body, data) for t in tokens]
        return await asyncio.gather(*tasks)

    # ── Internal ──────────────────────────────────────────────────────────────

    def _build_payload(self, token, title, body, data,
                        image_url=None, sound="default",
                        priority="high", click_action="FLUTTER_NOTIFICATION_CLICK"):
        notif = {"title": title, "body": body}
        if image_url:
            notif["image"] = image_url

        return {
            "to": token,
            "priority": priority,
            "notification": notif,
            "data": {**data, "nexpush": "true", "click_action": click_action},
            "android": {
                "priority": "HIGH",
                "notification": {
                    "channel_id": "nexpush_channel",
                    "sound": sound,
                    "notification_priority": "PRIORITY_MAX",
                    "visibility": "PUBLIC",
                    "default_sound": True,
                    "default_vibrate_timings": True,
                },
            },
            "apns": {
                "headers": {"apns-priority": "10"},
                "payload": {
                    "aps": {
                        "alert": {"title": title, "body": body},
                        "sound": sound,
                        "badge": 1,
                        "content-available": 1,
                    }
                },
            },
        }

    def _build_multicast(self, tokens, title, body, data, image_url=None):
        p = self._build_payload("", title, body, data, image_url)
        del p["to"]
        p["registration_ids"] = tokens
        return p

    def _dispatch(self, payload) -> NexPushResult:
        try:
            r = requests.post(
                FCM_URL, headers=self._headers,
                json=payload, timeout=self.config.timeout
            )
            body = r.json()
            if r.ok:
                return NexPushResult(
                    success=True,
                    message_id=body.get("message_id"),
                    success_count=body.get("success", 1),
                    failure_count=body.get("failure", 0),
                )
            return NexPushResult(success=False, error=body.get("error", "FCM error"))
        except Exception as e:
            return NexPushResult(success=False, error=str(e))

    async def _dispatch_async(self, payload) -> NexPushResult:
        try:
            async with aiohttp.ClientSession() as session:
                async with session.post(
                    FCM_URL, headers=self._headers,
                    json=payload, timeout=aiohttp.ClientTimeout(total=self.config.timeout)
                ) as r:
                    body = await r.json()
                    if r.ok:
                        return NexPushResult(
                            success=True,
                            success_count=body.get("success", 1),
                            failure_count=body.get("failure", 0),
                        )
                    return NexPushResult(success=False, error=body.get("error"))
        except Exception as e:
            return NexPushResult(success=False, error=str(e))
