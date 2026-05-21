<?php

if (!defined('STDIN')) {
exit;
}

require_once dirname(__DIR__, 4) . '/resources/require.php';

set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');

$worker_id = gethostname() . ':' . getmypid();
$sleep_seconds = 1;

while (true) {
if (!$database->is_connected()) {
$database->connect();
if (!$database->is_connected()) {
sleep(3);
continue;
}
}

$sql = "select s.domain_uuid, s.ivr_menu_uuid, s.gateway_uuid, s.originate_timeout, s.concurrent_limit, s.calls_per_second, s.retry_seconds, s.max_attempts,\n"
. "i.ivr_menu_extension, i.ivr_menu_context, d.domain_name\n"
. "from v_webhook_ivr_settings s\n"
. "join v_ivr_menus i on i.ivr_menu_uuid = s.ivr_menu_uuid and i.domain_uuid = s.domain_uuid\n"
. "join v_gateways g on g.gateway_uuid = s.gateway_uuid and g.domain_uuid = s.domain_uuid and g.enabled = true\n"
. "join v_domains d on d.domain_uuid = s.domain_uuid\n"
. "where s.enabled = true and i.ivr_menu_enabled = true";
$settings_rows = $database->select($sql, [], 'all');

$esl = event_socket::create();

foreach (($settings_rows ?: []) as $cfg) {
$concurrent_limit = max(1, (int)($cfg['concurrent_limit'] ?: 5));
$calls_per_second = (float)($cfg['calls_per_second'] ?: 1);
$retry_seconds = max(1, (int)($cfg['retry_seconds'] ?: 300));

$active_sql = "select count(*) from v_webhook_ivr_calls\n"
. "where domain_uuid = :domain_uuid and ivr_menu_uuid = :ivr_menu_uuid and call_status in ('processing','originated','answered')";
$active = (int)$database->select($active_sql, ['domain_uuid' => $cfg['domain_uuid'], 'ivr_menu_uuid' => $cfg['ivr_menu_uuid']], 'column');
$capacity = $concurrent_limit - $active;
if ($capacity <= 0) {
continue;
}

$claim_limit = min($capacity, max(1, (int)ceil($calls_per_second)));
$claim_sql = "with jobs as (\n"
. " select webhook_ivr_call_uuid\n"
. " from v_webhook_ivr_calls\n"
. " where domain_uuid = :domain_uuid\n"
. " and ivr_menu_uuid = :ivr_menu_uuid\n"
. " and call_status = 'queued'\n"
. " and scheduled_at <= now()\n"
. " order by insert_date asc\n"
. " for update skip locked\n"
. " limit ".((int)$claim_limit)."\n"
. ")\n"
. "update v_webhook_ivr_calls c\n"
. "set call_status = 'processing', locked_at = now(), locked_by = :worker_id, attempts = attempts + 1, update_date = now()\n"
. "from jobs where c.webhook_ivr_call_uuid = jobs.webhook_ivr_call_uuid returning c.*";
$jobs = $database->select($claim_sql, [
'domain_uuid' => $cfg['domain_uuid'],
'ivr_menu_uuid' => $cfg['ivr_menu_uuid'],
'worker_id' => $worker_id,
], 'all');

if (empty($jobs) || !$esl->is_connected()) {
continue;
}

$spacing = ($calls_per_second > 0) ? (int)(1000000 / $calls_per_second) : 1000000;

foreach ($jobs as $job) {
$call_uuid = trim(event_socket::api('create_uuid'));
$safe_reference = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', (string)$job['reference']);
$switch_cmd = "bgapi originate {"
. "origination_uuid=".$call_uuid
. ",ignore_early_media=true"
. ",domain_uuid=".$cfg['domain_uuid']
. ",domain_name=".$cfg['domain_name']
. ",ivr_menu_uuid=".$cfg['ivr_menu_uuid']
. ",webhook_ivr_call_uuid=".$job['webhook_ivr_call_uuid']
. ",webhook_ivr_reference=".$safe_reference
. "}"
. "sofia/gateway/".$cfg['gateway_uuid']."/".$job['to_number']
. " &transfer('".$cfg['ivr_menu_extension']." XML ".$cfg['ivr_menu_context']."')";

$response = trim(event_socket::api($switch_cmd));
if (stripos($response, '+OK') === 0) {
$database->execute(
"update v_webhook_ivr_calls set call_status = 'originated', call_uuid = :call_uuid, originate_response = :originate_response, update_date = now() where webhook_ivr_call_uuid = :webhook_ivr_call_uuid and domain_uuid = :domain_uuid",
[
'call_uuid' => $call_uuid,
'originate_response' => $response,
'webhook_ivr_call_uuid' => $job['webhook_ivr_call_uuid'],
'domain_uuid' => $cfg['domain_uuid'],
]
);
}
else {
$attempts = (int)$job['attempts'];
$max_attempts = (int)($job['max_attempts'] ?: $cfg['max_attempts'] ?: 3);
if ($attempts < $max_attempts) {
$database->execute(
"update v_webhook_ivr_calls set call_status = 'queued', scheduled_at = now() + (:retry_seconds || ' seconds')::interval, last_error = :last_error, update_date = now() where webhook_ivr_call_uuid = :webhook_ivr_call_uuid and domain_uuid = :domain_uuid",
[
'retry_seconds' => $retry_seconds,
'last_error' => $response,
'webhook_ivr_call_uuid' => $job['webhook_ivr_call_uuid'],
'domain_uuid' => $cfg['domain_uuid'],
]
);
}
else {
$database->execute(
"update v_webhook_ivr_calls set call_status = 'failed', last_error = :last_error, update_date = now() where webhook_ivr_call_uuid = :webhook_ivr_call_uuid and domain_uuid = :domain_uuid",
[
'last_error' => $response,
'webhook_ivr_call_uuid' => $job['webhook_ivr_call_uuid'],
'domain_uuid' => $cfg['domain_uuid'],
]
);
}
}
usleep($spacing);
}
}

sleep($sleep_seconds);
}
