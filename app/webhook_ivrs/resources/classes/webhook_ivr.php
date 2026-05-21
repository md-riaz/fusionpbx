<?php

class webhook_ivr {

private $database;

public function __construct($database = null) {
$this->database = $database ?: database::new();
}

public function auth_api_key($api_key) {
if (empty($api_key)) {
return null;
}
$sql = "select user_uuid, domain_uuid, username from v_users where api_key = :api_key and user_enabled = true limit 1";
return $this->database->select($sql, ['api_key' => $api_key], 'row');
}

public function is_api_allowed($user_uuid, $domain_uuid) {
$sql = "select count(*) from v_user_groups where user_uuid = :user_uuid and domain_uuid = :domain_uuid and group_name in ('superadmin', 'admin')";
$count = $this->database->select($sql, ['user_uuid' => $user_uuid, 'domain_uuid' => $domain_uuid], 'column');
return ((int)$count) > 0;
}

public function get_settings($domain_uuid, $ivr_menu_uuid) {
$sql = "select s.*, i.ivr_menu_name, i.ivr_menu_extension, i.ivr_menu_context, i.ivr_menu_enabled, d.domain_name\n"
. "from v_webhook_ivr_settings s\n"
. "join v_ivr_menus i on i.ivr_menu_uuid = s.ivr_menu_uuid\n"
. "join v_domains d on d.domain_uuid = s.domain_uuid\n"
. "where s.domain_uuid = :domain_uuid and s.ivr_menu_uuid = :ivr_menu_uuid and s.enabled = true limit 1";
return $this->database->select($sql, ['domain_uuid' => $domain_uuid, 'ivr_menu_uuid' => $ivr_menu_uuid], 'row');
}

public function is_gateway_enabled($domain_uuid, $gateway_uuid) {
$sql = "select count(*) from v_gateways where domain_uuid = :domain_uuid and gateway_uuid = :gateway_uuid and enabled = true";
$count = $this->database->select($sql, ['domain_uuid' => $domain_uuid, 'gateway_uuid' => $gateway_uuid], 'column');
return ((int)$count) > 0;
}

public function extract_bearer_token() {
$header = '';
if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
$header = trim($_SERVER['HTTP_AUTHORIZATION']);
}
if (empty($header) && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
$header = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
}
if (stripos($header, 'Bearer ') === 0) {
return trim(substr($header, 7));
}
return '';
}

public function clean_number($value) {
return preg_replace('/[^0-9\+]/', '', (string)$value);
}

public function clean_reference($value) {
return trim(substr((string)$value, 0, 255));
}

public function json_response($payload, $status = 200) {
http_response_code($status);
header('Content-Type: application/json');
echo json_encode($payload, JSON_UNESCAPED_SLASHES);
exit;
}
}
