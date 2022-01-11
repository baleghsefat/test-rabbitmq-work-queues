<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$callback = function ($msg) {
    echo ' [x] Received ', $msg->body, "\n";
    sleep(substr_count($msg->body, '.'));
    echo " [x] Done\n";
    $msg->ack();
};

// If a consumer dies (its channel is closed, connection is closed, or TCP connection is lost) 
// without sending an ack, RabbitMQ will understand that a message wasn't processed fully and will
// re-queue it. If there are other consumers online at the same time, it will then quickly redeliver it 
// to another consumer. That way you can be sure that no message is lost, even if the workers occasionally die.


$channel->basic_consume('task_queue', '', false, false, false, false, $callback);

while ($channel->is_open()) {
    $channel->wait();
}
