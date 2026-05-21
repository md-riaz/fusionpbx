<?php
require_once dirname(__DIR__, 2) . '/resources/require.php';
require_once 'resources/check_auth.php';

if (!permission_exists('webhook_ivr_view')) {
echo 'access denied';
exit;
}

$language = new text;
$text = $language->get();

$sql = "select i.ivr_menu_uuid, i.ivr_menu_name, i.ivr_menu_extension, i.ivr_menu_description,\n"
. "s.concurrent_limit, s.calls_per_second, s.enabled, g.gateway\n"
. "from v_ivr_menus i\n"
. "left join v_webhook_ivr_settings s on s.ivr_menu_uuid = i.ivr_menu_uuid and s.domain_uuid = i.domain_uuid\n"
. "left join v_gateways g on g.gateway_uuid = s.gateway_uuid and g.domain_uuid = i.domain_uuid\n"
. "where i.domain_uuid = :domain_uuid\n"
. "order by i.ivr_menu_name asc";
$rows = $database->select($sql, ['domain_uuid' => $_SESSION['domain_uuid']], 'all');

require_once 'resources/header.php';

echo "<div class='action_bar' id='action_bar'>";
echo "<a class='btn' href='webhook_ivr_edit.php'>Add</a> ";
echo "<a class='btn' href='webhook_ivr_calls.php'>Calls</a> ";
echo "<a class='btn' href='webhook_ivr_events.php'>Events</a>";
echo "</div>";

echo "<table class='tr_hover' width='100%'>";
echo "<tr><th>Name</th><th>Extension</th><th>Gateway</th><th>Concurrent Limit</th><th>Calls/sec</th><th>Enabled</th><th>Description</th><th></th></tr>";
if (!empty($rows)) {
foreach ($rows as $row) {
echo '<tr>';
echo '<td>'.escape($row['ivr_menu_name']).'</td>';
echo '<td>'.escape($row['ivr_menu_extension']).'</td>';
echo '<td>'.escape($row['gateway']).'</td>';
echo '<td>'.escape($row['concurrent_limit']).'</td>';
echo '<td>'.escape($row['calls_per_second']).'</td>';
echo '<td>'.escape(($row['enabled'] ?? false) ? 'true' : 'false').'</td>';
echo '<td>'.escape($row['ivr_menu_description']).'</td>';
echo '<td><a href="webhook_ivr_edit.php?id='.urlencode($row['ivr_menu_uuid']).'">Edit</a> | <a href="webhook_ivr_delete.php?id='.urlencode($row['ivr_menu_uuid']).'" onclick="return confirm(\'Delete?\')">Delete</a></td>';
echo '</tr>';
}
}
echo '</table>';

require_once 'resources/footer.php';
