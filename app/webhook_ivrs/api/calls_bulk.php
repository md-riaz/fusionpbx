<?php
require_once dirname(__DIR__, 3) . '/resources/require.php';
require_once dirname(__DIR__) . '/resources/classes/webhook_ivr.php';
require_once dirname(__DIR__) . '/resources/classes/webhook_ivr_call_queue.php';

$api = new webhook_ivr($database);
$token = $api->extract_bearer_token();
$user = $api->auth_api_key($token);
if (empty($user) || !$api->is_api_allowed($user['user_uuid'], $user['domain_uuid'])) {
$api->json_response(['success' => false, 'error' => 'unauthorized'], 401);
}

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$ivr_menu_uuid = $payload['ivr_menu_uuid'] ?? '';
$items = $payload['items'] ?? [];

if (!is_uuid($ivr_menu_uuid) || !is_array($items) || empty($items)) {
$api->json_response(['success' => false, 'error' => 'invalid_request'], 422);
}

$settings = $api->get_settings($user['domain_uuid'], $ivr_menu_uuid);
if (empty($settings) || $settings['ivr_menu_enabled'] !== true || !$api->is_gateway_enabled($user['domain_uuid'], $settings['gateway_uuid'])) {
$api->json_response(['success' => false, 'error' => 'ivr_or_gateway_unavailable'], 422);
}

$queue = new webhook_ivr_call_queue($database);
$accepted = 0;
$rejected = 0;

foreach ($items as $item) {
$to = $api->clean_number($item['to'] ?? '');
$reference = $api->clean_reference($item['reference'] ?? '');
if (empty($to) || empty($reference)) {
$rejected++;
continue;
}
$queue->enqueue([
'domain_uuid' => $user['domain_uuid'],
'user_uuid' => $user['user_uuid'],
'ivr_menu_uuid' => $ivr_menu_uuid,
'gateway_uuid' => $settings['gateway_uuid'],
'to_number' => $to,
'reference' => $reference,
'max_attempts' => $settings['max_attempts'] ?: 3,
]);
$accepted++;
}

$api->json_response([
'success' => true,
'status' => 'queued',
'accepted' => $accepted,
'rejected' => $rejected,
], 202);
