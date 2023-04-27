<?php
require_once __DIR__ . '/../functions.php';

/*
 * Pubsub envelope subscriber
 * @author Ian Barber <ian(dot)barber(at)gmail(dot)com>
 */

//  Prepare our context and subscriber
$context = new ZMQContext();
$subscriber = new ZMQSocket($context, ZMQ::SOCKET_SUB);
$subscriber->connect("tcp://localhost:5560");


//  Subscribe to zipcode, default is NYC, 10001
$filter = $_SERVER['argc'] > 1 ? $_SERVER['argv'][1] : "EURUSD";
$subscriber->setSockOpt(ZMQ::SOCKOPT_SUBSCRIBE, $filter);

while (true) {
    //  Read envelope with address
    $address = $subscriber->recv();
    //  Read message contents
    $contents = $subscriber->recv();
    printf ("[%s] %s%s", $address, $contents, PHP_EOL);
}
//  We never get here