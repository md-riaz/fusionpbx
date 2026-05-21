<?php
require_once dirname(__DIR__, 2) . '/resources/require.php';
require_once 'resources/check_auth.php';

if (!permission_exists('webhook_ivr_delete')) {
echo 'access denied';
exit;
}

$ivr_menu_uuid = $_GET['id'] ?? '';
if (is_uuid($ivr_menu_uuid)) {
$domain_uuid = $_SESSION['domain_uuid'];
$sql = 'select dialplan_uuid from v_ivr_menus where domain_uuid = :domain_uuid and ivr_menu_uuid = :ivr_menu_uuid';
$dialplan_uuid = $database->select($sql, ['domain_uuid' => $domain_uuid, 'ivr_menu_uuid' => $ivr_menu_uuid], 'column');

$database->execute('delete from v_ivr_menu_option_webhooks where domain_uuid = :domain_uuid and ivr_menu_uuid = :ivr_menu_uuid', ['domain_uuid' => $domain_uuid, 'ivr_menu_uuid' => $ivr_menu_uuid]);
$database->execute('delete from v_ivr_menu_options where domain_uuid = :domain_uuid and ivr_menu_uuid = :ivr_menu_uuid', ['domain_uuid' => $domain_uuid, 'ivr_menu_uuid' => $ivr_menu_uuid]);
$database->execute('delete from v_webhook_ivr_settings where domain_uuid = :domain_uuid and ivr_menu_uuid = :ivr_menu_uuid', ['domain_uuid' => $domain_uuid, 'ivr_menu_uuid' => $ivr_menu_uuid]);
$database->execute('delete from v_ivr_menus where domain_uuid = :domain_uuid and ivr_menu_uuid = :ivr_menu_uuid', ['domain_uuid' => $domain_uuid, 'ivr_menu_uuid' => $ivr_menu_uuid]);
if (is_uuid($dialplan_uuid)) {
$database->execute('delete from v_dialplans where domain_uuid = :domain_uuid and dialplan_uuid = :dialplan_uuid', ['domain_uuid' => $domain_uuid, 'dialplan_uuid' => $dialplan_uuid]);
}
message::add('Delete Complete');
}

header('Location: webhook_ivrs.php');
exit;
