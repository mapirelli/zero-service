<?php
    require_once __DIR__ . '/../functions.php';

    $context = new ZMQContext();
    $socket = new ZMQSocket($context, ZMQ::SOCKET_DEALER);
    $socket->setSockOpt(ZMQ::SOCKOPT_IDENTITY, 'workerID');
    $socket->connect("tcp://localhost:5570");

    $counter = 1;
    while (true) {
        //The DEALER socket gives us the address envelope and message
        $body = function_get_parts_body(function_get_recv_parts($socket));
        $socket->send('received n.' . $counter);
        printf ("broker sent: [%s]%s", $body, PHP_EOL);
    }