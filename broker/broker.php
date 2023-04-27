<?php
    require_once __DIR__ . '/../functions.php';
    //Frontend socket wait messages from webhook client
    $context = new ZMQContext();
    $frontend = new ZMQSocket($context, ZMQ::SOCKET_DEALER);
    $frontend->bind("tcp://*:5580"); //Connect to alert server

    //Backend socket talks to EA clients
    $backend = new ZMQSocket($context, ZMQ::SOCKET_DEALER);
    $backend->bind("tcp://*:5570");

    //Initialize poll set
    $poll = new ZMQPoll();
    $poll->add($frontend, ZMQ::POLL_IN);
    $poll->add($backend, ZMQ::POLL_IN);
    
    $read = $write = array();
    $counter = 1;
    //  Process messages from both sockets
    while (true) {

        $events = 0; /* Amount of events retrieved */
        $events = $poll->poll($read, $write);
        
        if ($events > 0) {
            
            foreach ($read as $socket) {

                if ($socket === $frontend) {

                    $body = function_get_parts_body(function_get_recv_parts($socket));
                    printf ("%d webhook sent: [%s]%s", $counter, $counter . "-" . $body, PHP_EOL);
                    $backend->send($counter . "-" . $body);
                    
                    $counter++;

                } elseif ($socket === $backend) {
                    $body = function_get_parts_body(function_get_recv_parts($socket));
                    printf ("%d worker sent: [%s]%s", $counter, $counter . "-" . $body,  PHP_EOL);
                    //$frontend->send($body);
                }
            }
        }
    }
    //  We never get here
