<?php
require_once dirname(__DIR__, 2) . '/resources/require.php';
require_once 'resources/check_auth.php';
if (!permission_exists('webhook_ivr_queue_view')) {
echo 'access denied';
exit;
}

$sql = "select c.*, i.ivr_menu_name from v_webhook_ivr_calls c left join v_ivr_menus i on i.ivr_menu_uuid = c.ivr_menu_uuid where c.domain_uuid = :domain_uuid order by c.insert_date desc limit 500";
$rows = $database->select($sql, ['domain_uuid' => $_SESSION['domain_uuid']], 'all');

require_once 'resources/header.php';
echo "<table width='100%' class='tr_hover'><tr><th>Reference</th><th>To</th><th>Status</th><th>Attempts</th><th>Scheduled</th><th>IVR</th><th>Error</th></tr>";
foreach (($rows ?: []) as $row) {
echo '<tr>';
echo '<td>'.escape($row['reference']).'</td>';
echo '<td>'.escape($row['to_number']).'</td>';
echo '<td>'.escape($row['call_status']).'</td>';
echo '<td>'.escape($row['attempts']).'/'.escape($row['max_attempts']).'</td>';
echo '<td>'.escape($row['scheduled_at']).'</td>';
echo '<td>'.escape($row['ivr_menu_name']).'</td>';
echo '<td>'.escape($row['last_error']).'</td>';
echo '</tr>';
}
echo '</table>';
require_once 'resources/footer.php';
