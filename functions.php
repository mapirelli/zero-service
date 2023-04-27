
<?php 

function function_get_recv_parts($socket) {
    $parts = array();
    $parts[] = $socket->recv();
    while ($socket->getSockOpt(ZMQ::SOCKOPT_RCVMORE)) {
        $parts[] = $socket->recv();    
    }
    return $parts;
}

function function_get_parts_body($parts) {
    if (count($parts) == 1) {
        return $parts[0];
    }
    if (count($parts) > 1) {
        return $parts[1];
    }
}