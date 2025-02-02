<?php

/*
 * Pubsub envelope publisher
 * @author Ian Barber <ian(dot)barber(at)gmail(dot)com>
 */

//  Prepare our context and publisher
$context = new ZMQContext();
$publisher = new ZMQSocket($context, ZMQ::SOCKET_PUB);
$publisher->bind("tcp://*:5560");

while (true) {
    //  Write two messages, each with an envelope and content
    $publisher->send("GBPUSD", ZMQ::MODE_SNDMORE);
    $publisher->send("We don't want to see this");
    $publisher->send("EURUSD", ZMQ::MODE_SNDMORE);
    $publisher->send("We would like to see this");
    sleep (5);
}
