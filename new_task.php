<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

// Message durability:
// When RabbitMQ quits or crashes it will forget the queues and
// messages unless you tell it not to. Two things are required to make sure that messages aren't lost.


// Although this command is correct by itself, 
// it won't work in our present setup. That's because
// we've already defined a queue called hello which is not durable.
// RabbitMQ doesn't allow you to redefine an existing queue with different parameters
// and will return an error to any program that tries to do that. But there is a quick workaround
// - let's declare a queue with different name, for example task_queue.


$channel->queue_declare('task_queue', false, true, false, false);

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = "Hello... World!";
}

$msg = new AMQPMessage(
    $data,
    ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
);

$channel->basic_publish($msg, '', 'task_queue');

// You might have noticed that the dispatching still doesn't work exactly as we want.
// For example in a situation with two workers, when all odd messages are heavy and even messages
// are light, one worker will be constantly busy and the other one will do hardly any work. Well,
// RabbitMQ doesn't know anything about that and will still dispatch messages evenly.

$channel->basic_qos(null, 1, null);

echo ' [x] Sent ', $data, "\n";

$channel->close();
$connection->close();
