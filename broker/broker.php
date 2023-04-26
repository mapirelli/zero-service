<?php
/*
 *  Reading from multiple sockets
 *  This version uses zmq_poll()
 * @author Ian Barber <ian(dot)barber(at)gmail(dot)com>
 */

 $context = new ZMQContext();

 //  Connect to alert server
 $frontend = new ZMQSocket($context, ZMQ::SOCKET_ROUTER);
 $frontend->bind("tcp://*:5556");
 //$frontend->connect("tcp://*:5556");
 
 //  Connect to EA server
 //$subscriber = new ZMQSocket($context, ZMQ::SOCKET_SUB);
 //$subscriber->connect("tcp://localhost:5555");
 //$subscriber->setSockOpt(ZMQ::SOCKOPT_SUBSCRIBE, "EURUSD");
 
 //  Initialize poll set
 $poll = new ZMQPoll();
 $poll->add($frontend, ZMQ::POLL_IN);
 //$poll->add($subscriber, ZMQ::POLL_IN);
 
 $readable = $writeable = array();
 
 //  Process messages from both sockets
 while (true) {
     $events = $poll->poll($readable, $writeable);
     if ($events > 0) {
         foreach ($readable as $socket) {
             if ($socket === $frontend) {

                $parts = array();
                while (true) {
                    $parts[] = $socket->recv();
                    if (!$socket->getSockOpt(ZMQ::SOCKOPT_RCVMORE)) {
                        break;
                    }
                }
                $message = $parts[count($parts)-1];
                printf ("[%s] %s", $message, PHP_EOL);
                                
             //} elseif ($socket === $subscriber) {
                 // $mesage = $socket->recv();
                 // Process weather update
             }
         }
     }
 }
 
 //  We never get here
