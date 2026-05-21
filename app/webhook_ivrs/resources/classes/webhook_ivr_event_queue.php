<?php

class webhook_ivr_event_queue {
public static function retry_delay($attempts) {
$map = [1 => 0, 2 => 60, 3 => 300, 4 => 900, 5 => 3600];
return $map[$attempts] ?? 3600;
}
}
