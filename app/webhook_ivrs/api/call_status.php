<?php
require_once dirname(__DIR__, 3) . '/resources/require.php';
require_once dirname(__DIR__) . '/resources/classes/webhook_ivr.php';

$api = new webhook_ivr($database);
$token = $api->extract_bearer_token();
$user = $api->auth_api_key($token);
if (empty($user) || !$api->is_api_allowed($user['user_uuid'], $user['domain_uuid'])) {
$api->json_response(['success' => false, 'error' => 'unauthorized'], 401);
}

$webhook_ivr_call_uuid = $_GET['webhook_ivr_call_uuid'] ?? '';
$reference = $api->clean_reference($_GET['reference'] ?? '');

if (!is_uuid($webhook_ivr_call_uuid) && empty($reference)) {
$api->json_response(['success' => false, 'error' => 'invalid_request'], 422);
}

if (is_uuid($webhook_ivr_call_uuid)) {
$sql = 'select * from v_webhook_ivr_calls where domain_uuid = :domain_uuid and webhook_ivr_call_uuid = :webhook_ivr_call_uuid limit 1';
$params = ['domain_uuid' => $user['domain_uuid'], 'webhook_ivr_call_uuid' => $webhook_ivr_call_uuid];
}
else {
$sql = 'select * from v_webhook_ivr_calls where domain_uuid = :domain_uuid and reference = :reference order by insert_date desc limit 1';
$params = ['domain_uuid' => $user['domain_uuid'], 'reference' => $reference];
}

$row = $database->select($sql, $params, 'row');
if (empty($row)) {
$api->json_response(['success' => false, 'error' => 'not_found'], 404);
}

$api->json_response([
'success' => true,
'call' => [
'webhook_ivr_call_uuid' => $row['webhook_ivr_call_uuid'],
'reference' => $row['reference'],
'to' => $row['to_number'],
'status' => $row['call_status'],
'attempts' => $row['attempts'],
'max_attempts' => $row['max_attempts'],
'last_error' => $row['last_error'],
'hangup_cause' => $row['hangup_cause'],
'originate_response' => $row['originate_response'],
'updated_at' => $row['update_date'],
],
]);
