<?php

    $context = new ZMQContext();
    // Socket to talk to dispatcher
    $receiver = new ZMQSocket($context, ZMQ::SOCKET_REP);
    $receiver->connect("tcp://localhost:5560");

    while (true) {
        $string = $receiver->recv();
        printf ("Received request: [%s]%s", $string, PHP_EOL);
        // Send reply back to client
        $receiver->send("OK");
    }
