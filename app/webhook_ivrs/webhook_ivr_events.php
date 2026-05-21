<?php
require_once dirname(__DIR__, 2) . '/resources/require.php';
require_once 'resources/check_auth.php';
if (!permission_exists('webhook_ivr_queue_view')) {
echo 'access denied';
exit;
}

$sql = "select * from v_webhook_ivr_events where domain_uuid = :domain_uuid order by insert_date desc limit 500";
$rows = $database->select($sql, ['domain_uuid' => $_SESSION['domain_uuid']], 'all');

require_once 'resources/header.php';
echo "<table width='100%' class='tr_hover'><tr><th>Reference</th><th>Digits</th><th>Status</th><th>Attempts</th><th>HTTP</th><th>Error</th></tr>";
foreach (($rows ?: []) as $row) {
echo '<tr>';
echo '<td>'.escape($row['reference']).'</td>';
echo '<td>'.escape($row['digits']).'</td>';
echo '<td>'.escape($row['event_status']).'</td>';
echo '<td>'.escape($row['attempts']).'/'.escape($row['max_attempts']).'</td>';
echo '<td>'.escape($row['http_status']).'</td>';
echo '<td>'.escape($row['last_error']).'</td>';
echo '</tr>';
}
echo '</table>';
require_once 'resources/footer.php';
