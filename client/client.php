<?php
require_once __DIR__ . '/../functions.php';

$context = new ZMQContext();
$socket = new ZMQSocket($context, ZMQ::SOCKET_DEALER);
$socket->connect('tcp://localhost:5580');

$socket->send('Client message');

echo("send fatto");

$parts = function_get_recv_parts($socket);
if (count($parts) > 0) {
    echo(" | alert created");
    echo(" | response from broker " . $parts[0]);
}