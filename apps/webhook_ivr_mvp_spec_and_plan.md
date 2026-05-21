# FusionPBX Webhook IVR Auto-Call App — Instructions and Plan

## Source instructions
This file captures the full user instruction for building a focused Webhook IVR MVP in FusionPBX that:
- reuses native IVR tables/runtime (`v_ivr_menus`, `v_ivr_menu_options`, `v_dialplans`)
- saves IVR options as `menu-exec-app` Lua actions
- authenticates API with `v_users.api_key`
- queues calls and webhook events in DB tables
- uses workers for call origination and webhook delivery
- uses selected outbound `gateway_uuid` from IVR settings
- clears IVR cache after save

## Target files (MVP)
- `app/webhook_ivrs/app_config.php`
- `app/webhook_ivrs/app_languages.php`
- `app/webhook_ivrs/webhook_ivrs.php`
- `app/webhook_ivrs/webhook_ivr_edit.php`
- `app/webhook_ivrs/webhook_ivr_delete.php`
- `app/webhook_ivrs/webhook_ivr_calls.php`
- `app/webhook_ivrs/webhook_ivr_events.php`
- `app/webhook_ivrs/api/calls.php`
- `app/webhook_ivrs/api/calls_bulk.php`
- `app/webhook_ivrs/api/call_status.php`
- `app/webhook_ivrs/resources/classes/webhook_ivr.php`
- `app/webhook_ivrs/resources/classes/webhook_ivr_call_queue.php`
- `app/webhook_ivrs/resources/classes/webhook_ivr_event_queue.php`
- `app/webhook_ivrs/resources/service/call_worker.php`
- `app/webhook_ivrs/resources/service/webhook_worker.php`
- `app/switch/resources/scripts/app/webhook_ivrs/ivr_action.lua`

## Implementation plan
1. Create app shell with permissions and schema tables for settings, option webhooks, call queue, and event queue.
2. Add list/edit/delete pages for managed IVRs and queue visibility pages for calls/events.
3. Save IVR data to native tables and force option action to `menu-exec-app` with Lua param.
4. Add REST endpoints to enqueue single and bulk calls, with API key auth and domain-safe validation.
5. Add call worker to claim queued rows, respect per-IVR limits, and originate via selected gateway.
6. Add Lua action to capture selected option, queue webhook event, and execute post-selection media/action.
7. Add webhook worker to deliver webhook payloads with retry/backoff and SSRF protections.
8. Validate syntax and run security scan.

## Notes
- No existing files are modified in this iteration.
- All new logic is isolated under `app/webhook_ivrs` and Lua script path above.
