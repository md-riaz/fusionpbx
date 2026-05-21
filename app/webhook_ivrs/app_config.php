<?php

//application details
$apps[$x]['name'] = 'Webhook IVRs';
$apps[$x]['uuid'] = '8f0562b7-7f1a-4a29-a9ca-7e2b526f56e1';
$apps[$x]['category'] = 'Switch';
$apps[$x]['subcategory'] = '';
$apps[$x]['version'] = '1.0';
$apps[$x]['license'] = 'Mozilla Public License 1.1';
$apps[$x]['url'] = 'http://www.fusionpbx.com';
$apps[$x]['description']['en-us'] = 'Auto-call IVRs with DTMF webhook actions.';

//permission details
$y = 0;
$apps[$x]['permissions'][$y]['name'] = 'webhook_ivr_view';
$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
$apps[$x]['permissions'][$y]['groups'][] = 'admin';
$y++;
$apps[$x]['permissions'][$y]['name'] = 'webhook_ivr_add';
$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
$apps[$x]['permissions'][$y]['groups'][] = 'admin';
$y++;
$apps[$x]['permissions'][$y]['name'] = 'webhook_ivr_edit';
$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
$apps[$x]['permissions'][$y]['groups'][] = 'admin';
$y++;
$apps[$x]['permissions'][$y]['name'] = 'webhook_ivr_delete';
$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
$y++;
$apps[$x]['permissions'][$y]['name'] = 'webhook_ivr_api';
$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
$apps[$x]['permissions'][$y]['groups'][] = 'admin';
$y++;
$apps[$x]['permissions'][$y]['name'] = 'webhook_ivr_queue_view';
$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
$apps[$x]['permissions'][$y]['groups'][] = 'admin';

//schema details
$y = 0;
$apps[$x]['db'][$y]['table']['name'] = 'v_webhook_ivr_settings';
$apps[$x]['db'][$y]['table']['parent'] = '';
$z = 0;
$apps[$x]['db'][$y]['fields'][$z]['name'] = 'webhook_ivr_setting_uuid';
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
$z++;
$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_uuid';
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
$z++;
$apps[$x]['db'][$y]['fields'][$z]['name'] = 'ivr_menu_uuid';
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
$z++;
$apps[$x]['db'][$y]['fields'][$z]['name'] = 'gateway_uuid';
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
$z++;
foreach ([
'originate_timeout' => 'numeric',
'concurrent_limit' => 'numeric',
'calls_per_second' => 'numeric',
'retry_seconds' => 'numeric',
'max_attempts' => 'numeric',
] as $name => $type) {
$apps[$x]['db'][$y]['fields'][$z]['name'] = $name;
$apps[$x]['db'][$y]['fields'][$z]['type'] = $type;
$z++;
}
$apps[$x]['db'][$y]['fields'][$z]['name'] = 'enabled';
$apps[$x]['db'][$y]['fields'][$z]['type'] = 'boolean';
$z++;
foreach (['insert_date', 'update_date'] as $name) {
$apps[$x]['db'][$y]['fields'][$z]['name'] = $name;
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'timestamptz';
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'date';
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'date';
$z++;
}
foreach (['insert_user', 'update_user'] as $name) {
$apps[$x]['db'][$y]['fields'][$z]['name'] = $name;
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
$z++;
}

$y++;
$apps[$x]['db'][$y]['table']['name'] = 'v_ivr_menu_option_webhooks';
$apps[$x]['db'][$y]['table']['parent'] = '';
$z = 0;
foreach ([
['ivr_menu_option_webhook_uuid', 'uuid', 'primary'],
['domain_uuid', 'uuid', ''],
['ivr_menu_uuid', 'uuid', ''],
['ivr_menu_option_uuid', 'uuid', ''],
] as $field) {
$apps[$x]['db'][$y]['fields'][$z]['name'] = $field[0];
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
if ($field[2] === 'primary') {
$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
}
$z++;
}
foreach (['webhook_url', 'webhook_secret', 'response_recording', 'failed_recording', 'next_action_app', 'next_action_data'] as $name) {
$apps[$x]['db'][$y]['fields'][$z]['name'] = $name;
$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
$z++;
}
$apps[$x]['db'][$y]['fields'][$z]['name'] = 'webhook_enabled';
$apps[$x]['db'][$y]['fields'][$z]['type'] = 'boolean';
$z++;
foreach (['insert_date', 'update_date'] as $name) {
$apps[$x]['db'][$y]['fields'][$z]['name'] = $name;
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'timestamptz';
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'date';
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'date';
$z++;
}
foreach (['insert_user', 'update_user'] as $name) {
$apps[$x]['db'][$y]['fields'][$z]['name'] = $name;
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
$z++;
}

$y++;
$apps[$x]['db'][$y]['table']['name'] = 'v_webhook_ivr_calls';
$apps[$x]['db'][$y]['table']['parent'] = '';
$z = 0;
foreach ([
['webhook_ivr_call_uuid', 'uuid', 'primary'],
['domain_uuid', 'uuid', ''],
['user_uuid', 'uuid', ''],
['ivr_menu_uuid', 'uuid', ''],
['gateway_uuid', 'uuid', ''],
] as $field) {
$apps[$x]['db'][$y]['fields'][$z]['name'] = $field[0];
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
if ($field[2] === 'primary') {
$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
}
$z++;
}
foreach (['call_uuid', 'to_number', 'reference', 'call_status', 'locked_by', 'originate_response', 'hangup_cause', 'last_error'] as $name) {
$apps[$x]['db'][$y]['fields'][$z]['name'] = $name;
$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
$z++;
}
foreach (['attempts', 'max_attempts'] as $name) {
$apps[$x]['db'][$y]['fields'][$z]['name'] = $name;
$apps[$x]['db'][$y]['fields'][$z]['type'] = 'numeric';
$z++;
}
foreach (['scheduled_at', 'locked_at', 'insert_date', 'update_date'] as $name) {
$apps[$x]['db'][$y]['fields'][$z]['name'] = $name;
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'timestamptz';
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'date';
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'date';
$z++;
}

$y++;
$apps[$x]['db'][$y]['table']['name'] = 'v_webhook_ivr_events';
$apps[$x]['db'][$y]['table']['parent'] = '';
$z = 0;
foreach ([
['webhook_ivr_event_uuid', 'uuid', 'primary'],
['domain_uuid', 'uuid', ''],
['ivr_menu_uuid', 'uuid', ''],
['ivr_menu_option_uuid', 'uuid', ''],
['webhook_ivr_call_uuid', 'uuid', ''],
] as $field) {
$apps[$x]['db'][$y]['fields'][$z]['name'] = $field[0];
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
if ($field[2] === 'primary') {
$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
}
$z++;
}
foreach (['call_uuid', 'reference', 'digits', 'webhook_url', 'webhook_secret', 'payload_json', 'event_status', 'locked_by', 'http_status', 'response_body', 'last_error'] as $name) {
$apps[$x]['db'][$y]['fields'][$z]['name'] = $name;
$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
$z++;
}
foreach (['attempts', 'max_attempts'] as $name) {
$apps[$x]['db'][$y]['fields'][$z]['name'] = $name;
$apps[$x]['db'][$y]['fields'][$z]['type'] = 'numeric';
$z++;
}
foreach (['scheduled_at', 'locked_at', 'insert_date', 'update_date'] as $name) {
$apps[$x]['db'][$y]['fields'][$z]['name'] = $name;
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'timestamptz';
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'date';
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'date';
$z++;
}
