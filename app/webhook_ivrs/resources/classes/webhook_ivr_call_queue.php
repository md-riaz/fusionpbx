<?php

class webhook_ivr_call_queue {
private $database;
public function __construct($database = null) {
$this->database = $database ?: database::new();
}

public function enqueue($row) {
$uuid = uuid();
$array['webhook_ivr_calls'][0]['webhook_ivr_call_uuid'] = $uuid;
$array['webhook_ivr_calls'][0]['domain_uuid'] = $row['domain_uuid'];
$array['webhook_ivr_calls'][0]['user_uuid'] = $row['user_uuid'];
$array['webhook_ivr_calls'][0]['ivr_menu_uuid'] = $row['ivr_menu_uuid'];
$array['webhook_ivr_calls'][0]['gateway_uuid'] = $row['gateway_uuid'];
$array['webhook_ivr_calls'][0]['to_number'] = $row['to_number'];
$array['webhook_ivr_calls'][0]['reference'] = $row['reference'];
$array['webhook_ivr_calls'][0]['call_status'] = 'queued';
$array['webhook_ivr_calls'][0]['attempts'] = 0;
$array['webhook_ivr_calls'][0]['max_attempts'] = $row['max_attempts'] ?? 3;
$array['webhook_ivr_calls'][0]['scheduled_at'] = date('c');
$this->database->save($array);
return $uuid;
}
}
