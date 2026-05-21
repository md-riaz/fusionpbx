<?php

if (!defined('STDIN')) {
exit;
}

require_once dirname(__DIR__, 4) . '/resources/require.php';
require_once dirname(__DIR__) . '/classes/webhook_ivr_event_queue.php';

set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');

$worker_id = gethostname() . ':' . getmypid();

function is_private_ip($ip) {
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
return true;
}
return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
}

function webhook_url_allowed($url) {
$parts = parse_url($url);
if (empty($parts['scheme']) || empty($parts['host'])) {
return false;
}
if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
return false;
}
$host = $parts['host'];
if (in_array(strtolower($host), ['localhost'], true)) {
return false;
}
$ip = gethostbyname($host);
if (is_private_ip($ip)) {
return false;
}
return true;
}

while (true) {
if (!$database->is_connected()) {
$database->connect();
if (!$database->is_connected()) {
sleep(3);
continue;
}
}

$claim_sql = "with jobs as (\n"
. " select webhook_ivr_event_uuid\n"
. " from v_webhook_ivr_events\n"
. " where event_status = 'queued' and scheduled_at <= now()\n"
. " order by insert_date asc\n"
. " for update skip locked\n"
. " limit 25\n"
. ")\n"
. "update v_webhook_ivr_events e\n"
. "set event_status = 'processing', locked_at = now(), locked_by = :worker_id, attempts = attempts + 1, update_date = now()\n"
. "from jobs where e.webhook_ivr_event_uuid = jobs.webhook_ivr_event_uuid returning e.*";
$jobs = $database->select($claim_sql, ['worker_id' => $worker_id], 'all');

if (empty($jobs)) {
sleep(1);
continue;
}

foreach ($jobs as $event) {
$attempts = (int)$event['attempts'];
$max_attempts = (int)($event['max_attempts'] ?: 5);

if (empty($event['webhook_url']) || !webhook_url_allowed($event['webhook_url'])) {
$database->execute(
"update v_webhook_ivr_events set event_status = 'failed', last_error = :last_error, update_date = now() where webhook_ivr_event_uuid = :webhook_ivr_event_uuid",
['last_error' => 'webhook_url_blocked_or_invalid', 'webhook_ivr_event_uuid' => $event['webhook_ivr_event_uuid']]
);
continue;
}

$ch = curl_init($event['webhook_url']);
$headers = ['Content-Type: application/json'];
if (!empty($event['webhook_secret'])) {
$headers[] = 'X-IVR-Webhook-Secret: ' . $event['webhook_secret'];
}

curl_setopt_array($ch, [
CURLOPT_POST => true,
CURLOPT_POSTFIELDS => $event['payload_json'],
CURLOPT_HTTPHEADER => $headers,
CURLOPT_RETURNTRANSFER => true,
CURLOPT_TIMEOUT => 5,
CURLOPT_CONNECTTIMEOUT => 5,
]);
$response_body = curl_exec($ch);
$http_status = (string)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error === '' && (int)$http_status >= 200 && (int)$http_status < 300) {
$database->execute(
"update v_webhook_ivr_events set event_status = 'sent', http_status = :http_status, response_body = :response_body, update_date = now() where webhook_ivr_event_uuid = :webhook_ivr_event_uuid",
[
'http_status' => $http_status,
'response_body' => (string)$response_body,
'webhook_ivr_event_uuid' => $event['webhook_ivr_event_uuid'],
]
);
continue;
}

$last_error = !empty($error) ? $error : ('http_' . $http_status);
if ($attempts < $max_attempts) {
$delay = webhook_ivr_event_queue::retry_delay($attempts + 1);
$database->execute(
"update v_webhook_ivr_events set event_status = 'queued', scheduled_at = now() + (:delay || ' seconds')::interval, http_status = :http_status, response_body = :response_body, last_error = :last_error, update_date = now() where webhook_ivr_event_uuid = :webhook_ivr_event_uuid",
[
'delay' => $delay,
'http_status' => $http_status,
'response_body' => (string)$response_body,
'last_error' => $last_error,
'webhook_ivr_event_uuid' => $event['webhook_ivr_event_uuid'],
]
);
}
else {
$database->execute(
"update v_webhook_ivr_events set event_status = 'failed', http_status = :http_status, response_body = :response_body, last_error = :last_error, update_date = now() where webhook_ivr_event_uuid = :webhook_ivr_event_uuid",
[
'http_status' => $http_status,
'response_body' => (string)$response_body,
'last_error' => $last_error,
'webhook_ivr_event_uuid' => $event['webhook_ivr_event_uuid'],
]
);
}
}
}
