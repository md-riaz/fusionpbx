<?php
require_once dirname(__DIR__, 2) . '/resources/require.php';
require_once 'resources/check_auth.php';

if (!permission_exists('webhook_ivr_add') && !permission_exists('webhook_ivr_edit')) {
echo 'access denied';
exit;
}

$language = new text;
$text = $language->get();

$domain_uuid = $_SESSION['domain_uuid'];
$ivr_menu_uuid = (isset($_REQUEST['id']) && is_uuid($_REQUEST['id'])) ? $_REQUEST['id'] : '';
$action = $ivr_menu_uuid ? 'update' : 'add';

// defaults
$ivr = [
'ivr_menu_name' => '',
'ivr_menu_extension' => '',
'ivr_menu_greet_long' => '',
'ivr_menu_greet_short' => '',
'ivr_menu_invalid_sound' => '',
'ivr_menu_exit_sound' => '',
'ivr_menu_timeout' => '3000',
'ivr_menu_digit_len' => '1',
'ivr_menu_max_failures' => '3',
'ivr_menu_max_timeouts' => '3',
'ivr_menu_enabled' => 'true',
'ivr_menu_description' => ''
];
$settings = [
'gateway_uuid' => '',
'originate_timeout' => '30',
'concurrent_limit' => '5',
'calls_per_second' => '1',
'retry_seconds' => '300',
'max_attempts' => '3',
'enabled' => 'true'
];
$options = [];

if ($action === 'update' && empty($_POST)) {
$sql = 'select * from v_ivr_menus where domain_uuid = :domain_uuid and ivr_menu_uuid = :ivr_menu_uuid';
$row = $database->select($sql, ['domain_uuid' => $domain_uuid, 'ivr_menu_uuid' => $ivr_menu_uuid], 'row');
if (!empty($row)) {
$ivr = array_merge($ivr, $row);
}

$sql = 'select * from v_webhook_ivr_settings where domain_uuid = :domain_uuid and ivr_menu_uuid = :ivr_menu_uuid limit 1';
$row = $database->select($sql, ['domain_uuid' => $domain_uuid, 'ivr_menu_uuid' => $ivr_menu_uuid], 'row');
if (!empty($row)) {
$settings = array_merge($settings, $row);
}

$sql = "select o.*, w.ivr_menu_option_webhook_uuid, w.webhook_url, w.webhook_secret, w.response_recording, w.failed_recording, w.next_action_app, w.next_action_data, w.webhook_enabled\n"
. "from v_ivr_menu_options o\n"
. "left join v_ivr_menu_option_webhooks w on w.ivr_menu_option_uuid = o.ivr_menu_option_uuid and w.domain_uuid = o.domain_uuid\n"
. "where o.domain_uuid = :domain_uuid and o.ivr_menu_uuid = :ivr_menu_uuid\n"
. "order by o.ivr_menu_option_order asc";
$options = $database->select($sql, ['domain_uuid' => $domain_uuid, 'ivr_menu_uuid' => $ivr_menu_uuid], 'all');
}

if (!empty($_POST) && empty($_POST['persistformvar'])) {
$token = new token;
if (!$token->validate($_SERVER['PHP_SELF'])) {
message::add('Invalid token', 'negative');
header('Location: webhook_ivrs.php');
exit;
}

foreach ($ivr as $k => $v) {
$ivr[$k] = $_POST[$k] ?? $v;
}
foreach ($settings as $k => $v) {
$settings[$k] = $_POST[$k] ?? $v;
}

$msg = '';
if (empty($ivr['ivr_menu_name'])) $msg .= "Name required<br>";
if (empty($ivr['ivr_menu_extension'])) $msg .= "Extension required<br>";
if (empty($settings['gateway_uuid']) || !is_uuid($settings['gateway_uuid'])) $msg .= "Gateway required<br>";

$digits = $_POST['option_digits'] ?? [];
if (empty($digits) || !is_array($digits)) {
$msg .= "At least one option is required<br>";
}

if (!empty($msg)) {
require_once 'resources/header.php';
echo "<div class='negative'>$msg</div>";
require_once 'resources/footer.php';
exit;
}

if ($action === 'add') {
$ivr_menu_uuid = uuid();
$dialplan_uuid = uuid();
} else {
$dialplan_uuid = $ivr['dialplan_uuid'] ?? '';
if (!is_uuid($dialplan_uuid)) {
$dialplan_uuid = uuid();
}
}

$ivr_menu_context = $_SESSION['context'];

$dialplan_xml = "<extension name=\"".xml::sanitize($ivr['ivr_menu_name'])."\" continue=\"false\" uuid=\"".xml::sanitize($dialplan_uuid)."\">\n";
$dialplan_xml .= "\t<condition field=\"destination_number\" expression=\"^".xml::sanitize($ivr['ivr_menu_extension'])."\\$\">\n";
$dialplan_xml .= "\t\t<action application=\"set\" data=\"ivr_menu_uuid=".xml::sanitize($ivr_menu_uuid)."\"/>\n";
$dialplan_xml .= "\t\t<action application=\"ivr\" data=\"".xml::sanitize($ivr_menu_uuid)."\"/>\n";
$dialplan_xml .= "\t</condition>\n";
$dialplan_xml .= "</extension>\n";

$array['ivr_menus'][0]['domain_uuid'] = $domain_uuid;
$array['ivr_menus'][0]['ivr_menu_uuid'] = $ivr_menu_uuid;
$array['ivr_menus'][0]['dialplan_uuid'] = $dialplan_uuid;
$array['ivr_menus'][0]['ivr_menu_name'] = $ivr['ivr_menu_name'];
$array['ivr_menus'][0]['ivr_menu_extension'] = $ivr['ivr_menu_extension'];
$array['ivr_menus'][0]['ivr_menu_greet_long'] = $ivr['ivr_menu_greet_long'];
$array['ivr_menus'][0]['ivr_menu_greet_short'] = $ivr['ivr_menu_greet_short'];
$array['ivr_menus'][0]['ivr_menu_invalid_sound'] = $ivr['ivr_menu_invalid_sound'];
$array['ivr_menus'][0]['ivr_menu_exit_sound'] = $ivr['ivr_menu_exit_sound'];
$array['ivr_menus'][0]['ivr_menu_timeout'] = $ivr['ivr_menu_timeout'];
$array['ivr_menus'][0]['ivr_menu_digit_len'] = $ivr['ivr_menu_digit_len'];
$array['ivr_menus'][0]['ivr_menu_max_failures'] = $ivr['ivr_menu_max_failures'];
$array['ivr_menus'][0]['ivr_menu_max_timeouts'] = $ivr['ivr_menu_max_timeouts'];
$array['ivr_menus'][0]['ivr_menu_context'] = $ivr_menu_context;
$array['ivr_menus'][0]['ivr_menu_enabled'] = $ivr['ivr_menu_enabled'];
$array['ivr_menus'][0]['ivr_menu_description'] = $ivr['ivr_menu_description'];

$array['dialplans'][0]['domain_uuid'] = $domain_uuid;
$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
$array['dialplans'][0]['dialplan_name'] = $ivr['ivr_menu_name'];
$array['dialplans'][0]['dialplan_number'] = $ivr['ivr_menu_extension'];
$array['dialplans'][0]['dialplan_context'] = $ivr_menu_context;
$array['dialplans'][0]['dialplan_continue'] = 'false';
$array['dialplans'][0]['dialplan_xml'] = $dialplan_xml;
$array['dialplans'][0]['dialplan_order'] = '101';
$array['dialplans'][0]['dialplan_enabled'] = $ivr['ivr_menu_enabled'];
$array['dialplans'][0]['dialplan_description'] = $ivr['ivr_menu_description'];
$array['dialplans'][0]['app_uuid'] = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';

if ($action === 'update') {
$database->execute('delete from v_ivr_menu_option_webhooks where domain_uuid = :domain_uuid and ivr_menu_uuid = :ivr_menu_uuid', ['domain_uuid' => $domain_uuid, 'ivr_menu_uuid' => $ivr_menu_uuid]);
$database->execute('delete from v_ivr_menu_options where domain_uuid = :domain_uuid and ivr_menu_uuid = :ivr_menu_uuid', ['domain_uuid' => $domain_uuid, 'ivr_menu_uuid' => $ivr_menu_uuid]);
}

$posted_option_uuid = $_POST['option_uuid'] ?? [];
$webhook_url = $_POST['webhook_url'] ?? [];
$webhook_secret = $_POST['webhook_secret'] ?? [];
$response_recording = $_POST['response_recording'] ?? [];
$failed_recording = $_POST['failed_recording'] ?? [];
$next_action_app = $_POST['next_action_app'] ?? [];
$next_action_data = $_POST['next_action_data'] ?? [];
$option_enabled = $_POST['option_enabled'] ?? [];
$option_description = $_POST['option_description'] ?? [];

$y = 0;
foreach ($digits as $i => $digit) {
$digit = trim($digit);
if ($digit === '') {
continue;
}
$option_uuid = (!empty($posted_option_uuid[$i]) && is_uuid($posted_option_uuid[$i])) ? $posted_option_uuid[$i] : uuid();
$array['ivr_menu_options'][$y]['domain_uuid'] = $domain_uuid;
$array['ivr_menu_options'][$y]['ivr_menu_uuid'] = $ivr_menu_uuid;
$array['ivr_menu_options'][$y]['ivr_menu_option_uuid'] = $option_uuid;
$array['ivr_menu_options'][$y]['ivr_menu_option_digits'] = $digit;
$array['ivr_menu_options'][$y]['ivr_menu_option_action'] = 'menu-exec-app';
$array['ivr_menu_options'][$y]['ivr_menu_option_param'] = 'lua app/webhook_ivrs/ivr_action.lua '.$option_uuid;
$array['ivr_menu_options'][$y]['ivr_menu_option_order'] = ($i + 1);
$array['ivr_menu_options'][$y]['ivr_menu_option_description'] = $option_description[$i] ?? '';
$array['ivr_menu_options'][$y]['ivr_menu_option_enabled'] = (!empty($option_enabled[$i]) && $option_enabled[$i] === 'true') ? 'true' : 'false';

$array['ivr_menu_option_webhooks'][$y]['ivr_menu_option_webhook_uuid'] = uuid();
$array['ivr_menu_option_webhooks'][$y]['domain_uuid'] = $domain_uuid;
$array['ivr_menu_option_webhooks'][$y]['ivr_menu_uuid'] = $ivr_menu_uuid;
$array['ivr_menu_option_webhooks'][$y]['ivr_menu_option_uuid'] = $option_uuid;
$array['ivr_menu_option_webhooks'][$y]['webhook_url'] = trim($webhook_url[$i] ?? '');
$array['ivr_menu_option_webhooks'][$y]['webhook_secret'] = trim($webhook_secret[$i] ?? '');
$array['ivr_menu_option_webhooks'][$y]['response_recording'] = trim($response_recording[$i] ?? '');
$array['ivr_menu_option_webhooks'][$y]['failed_recording'] = trim($failed_recording[$i] ?? '');
$array['ivr_menu_option_webhooks'][$y]['next_action_app'] = trim($next_action_app[$i] ?? 'hangup');
$array['ivr_menu_option_webhooks'][$y]['next_action_data'] = trim($next_action_data[$i] ?? '');
$array['ivr_menu_option_webhooks'][$y]['webhook_enabled'] = (!empty($option_enabled[$i]) && $option_enabled[$i] === 'true') ? 'true' : 'false';
$y++;
}

$sql = 'select webhook_ivr_setting_uuid from v_webhook_ivr_settings where domain_uuid = :domain_uuid and ivr_menu_uuid = :ivr_menu_uuid';
$setting_uuid = $database->select($sql, ['domain_uuid' => $domain_uuid, 'ivr_menu_uuid' => $ivr_menu_uuid], 'column');
$array['webhook_ivr_settings'][0]['webhook_ivr_setting_uuid'] = (is_uuid($setting_uuid) ? $setting_uuid : uuid());
$array['webhook_ivr_settings'][0]['domain_uuid'] = $domain_uuid;
$array['webhook_ivr_settings'][0]['ivr_menu_uuid'] = $ivr_menu_uuid;
$array['webhook_ivr_settings'][0]['gateway_uuid'] = $settings['gateway_uuid'];
$array['webhook_ivr_settings'][0]['originate_timeout'] = $settings['originate_timeout'];
$array['webhook_ivr_settings'][0]['concurrent_limit'] = $settings['concurrent_limit'];
$array['webhook_ivr_settings'][0]['calls_per_second'] = $settings['calls_per_second'];
$array['webhook_ivr_settings'][0]['retry_seconds'] = $settings['retry_seconds'];
$array['webhook_ivr_settings'][0]['max_attempts'] = $settings['max_attempts'];
$array['webhook_ivr_settings'][0]['enabled'] = $settings['enabled'];

$p = permissions::new();
$p->add(($action === 'add') ? 'dialplan_add' : 'dialplan_edit', 'temp');
$database->save($array);
$p->delete('dialplan_add', 'temp');
$p->delete('dialplan_edit', 'temp');

$cache = new cache;
$cache->delete('configuration:ivr.conf:' . $ivr_menu_uuid);
$cache->delete('dialplan:' . $ivr_menu_context);
$_SESSION['reload_xml'] = true;

message::add($action === 'add' ? ($text['message-add'] ?? 'Add Complete') : ($text['message-update'] ?? 'Update Complete'));
header('Location: webhook_ivr_edit.php?id=' . urlencode($ivr_menu_uuid));
exit;
}

$sql = "select gateway_uuid, gateway, description from v_gateways where domain_uuid = :domain_uuid and enabled = true order by gateway asc";
$gateways = $database->select($sql, ['domain_uuid' => $domain_uuid], 'all');

require_once 'resources/header.php';

echo "<form method='post'>";
$token = new token;
echo $token->create($_SERVER['PHP_SELF']);

echo "<table width='100%'>";
echo "<tr><td>Name</td><td><input class='formfld' type='text' name='ivr_menu_name' value='".escape($ivr['ivr_menu_name'])."'></td></tr>";
echo "<tr><td>Extension</td><td><input class='formfld' type='text' name='ivr_menu_extension' value='".escape($ivr['ivr_menu_extension'])."'></td></tr>";
echo "<tr><td>Greeting Long</td><td><input class='formfld' type='text' name='ivr_menu_greet_long' value='".escape($ivr['ivr_menu_greet_long'])."'></td></tr>";
echo "<tr><td>Greeting Short</td><td><input class='formfld' type='text' name='ivr_menu_greet_short' value='".escape($ivr['ivr_menu_greet_short'])."'></td></tr>";
echo "<tr><td>Invalid Sound</td><td><input class='formfld' type='text' name='ivr_menu_invalid_sound' value='".escape($ivr['ivr_menu_invalid_sound'])."'></td></tr>";
echo "<tr><td>Exit Sound</td><td><input class='formfld' type='text' name='ivr_menu_exit_sound' value='".escape($ivr['ivr_menu_exit_sound'])."'></td></tr>";
echo "<tr><td>Timeout</td><td><input class='formfld' type='text' name='ivr_menu_timeout' value='".escape($ivr['ivr_menu_timeout'])."'></td></tr>";
echo "<tr><td>Digit Length</td><td><input class='formfld' type='text' name='ivr_menu_digit_len' value='".escape($ivr['ivr_menu_digit_len'])."'></td></tr>";
echo "<tr><td>Max Failures</td><td><input class='formfld' type='text' name='ivr_menu_max_failures' value='".escape($ivr['ivr_menu_max_failures'])."'></td></tr>";
echo "<tr><td>Max Timeouts</td><td><input class='formfld' type='text' name='ivr_menu_max_timeouts' value='".escape($ivr['ivr_menu_max_timeouts'])."'></td></tr>";
echo "<tr><td>Enabled</td><td><select class='formfld' name='ivr_menu_enabled'><option value='true'".($ivr['ivr_menu_enabled'] == 'true' ? ' selected' : '').">true</option><option value='false'".($ivr['ivr_menu_enabled'] == 'false' ? ' selected' : '').">false</option></select></td></tr>";
echo "<tr><td>Description</td><td><input class='formfld' type='text' name='ivr_menu_description' value='".escape($ivr['ivr_menu_description'])."'></td></tr>";

echo "<tr><td colspan='2'><br><b>Auto-call Settings</b></td></tr>";
echo "<tr><td>Gateway</td><td><select class='formfld' name='gateway_uuid'><option value=''></option>";
foreach (($gateways ?: []) as $gateway) {
$selected = ($settings['gateway_uuid'] == $gateway['gateway_uuid']) ? ' selected' : '';
echo "<option value='".escape($gateway['gateway_uuid'])."'{$selected}>".escape($gateway['gateway'])."</option>";
}
echo "</select></td></tr>";
foreach (['originate_timeout','concurrent_limit','calls_per_second','retry_seconds','max_attempts'] as $k) {
echo "<tr><td>".escape($k)."</td><td><input class='formfld' type='text' name='".escape($k)."' value='".escape($settings[$k])."'></td></tr>";
}
echo "<tr><td>Enabled</td><td><select class='formfld' name='enabled'><option value='true'".($settings['enabled'] == 'true' ? ' selected' : '').">true</option><option value='false'".($settings['enabled'] == 'false' ? ' selected' : '').">false</option></select></td></tr>";

echo "<tr><td colspan='2'><br><b>IVR Options</b></td></tr>";
echo "<tr><td colspan='2'>";
echo "<table width='100%' class='tr_hover'>";
echo "<tr><th>Digit</th><th>Webhook URL</th><th>Webhook Secret</th><th>Response Recording</th><th>Failed Recording</th><th>Next Action</th><th>Next Action Data</th><th>Enabled</th><th>Description</th></tr>";

$row_count = max(3, is_array($options) ? count($options) : 0);
for ($i = 0; $i < $row_count; $i++) {
$row = $options[$i] ?? [];
echo "<tr>";
echo "<td><input class='formfld' type='text' name='option_digits[]' value='".escape($row['ivr_menu_option_digits'] ?? '')."'></td>";
echo "<td><input class='formfld' type='text' name='webhook_url[]' value='".escape($row['webhook_url'] ?? '')."'></td>";
echo "<td><input class='formfld' type='text' name='webhook_secret[]' value='".escape($row['webhook_secret'] ?? '')."'></td>";
echo "<td><input class='formfld' type='text' name='response_recording[]' value='".escape($row['response_recording'] ?? '')."'></td>";
echo "<td><input class='formfld' type='text' name='failed_recording[]' value='".escape($row['failed_recording'] ?? '')."'></td>";
echo "<td><select class='formfld' name='next_action_app[]'>";
$na = $row['next_action_app'] ?? 'hangup';
foreach (['hangup','transfer','playback'] as $app) {
echo "<option value='".escape($app)."'".($na == $app ? ' selected' : '').">".escape($app)."</option>";
}
echo "</select></td>";
echo "<td><input class='formfld' type='text' name='next_action_data[]' value='".escape($row['next_action_data'] ?? '')."'></td>";
$enabled_val = $row['ivr_menu_option_enabled'] ?? 'true';
echo "<td><select class='formfld' name='option_enabled[]'><option value='true'".($enabled_val == 'true' ? ' selected' : '').">true</option><option value='false'".($enabled_val == 'false' ? ' selected' : '').">false</option></select></td>";
echo "<td><input class='formfld' type='text' name='option_description[]' value='".escape($row['ivr_menu_option_description'] ?? '')."'></td>";
echo "<input type='hidden' name='option_uuid[]' value='".escape($row['ivr_menu_option_uuid'] ?? '')."'>";
echo "</tr>";
}

echo "</table></td></tr>";

echo "<tr><td colspan='2' align='right'><input class='btn' type='submit' value='Save'></td></tr>";
echo "</table></form>";

require_once 'resources/footer.php';
