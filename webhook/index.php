<?php
/**
 * Webhook
 */
$remoteAddress = $_SERVER['REMOTE_ADDR'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === 'POST') {
    $alertPayload = file_get_contents('php://input');
    $client = new Client();
    if ($client->isValidPayload($alertPayload)) {
        $client->sendAlert($remoteAddress, $alertPayload);
    }
}

if ($requestMethod === 'GET') {
    if ($_GET && isset($_GET['alert'])) {
        $alert = $_GET['alert'];
        if ($alert == 'buy' || $alert == 'sell') {
            echo("Alert created");
            $alertTester = new AlertTester();
            $alertPayload  = $alertTester->getPayload($alert);
            $client = new Client();
            if ($client->isValidPayload($alertPayload)) {
                $client->sendAlert($remoteAddress, $alertPayload);
            }
        }
    }
}

class Client {
    
    public function isValidPayload(string $payload) {
        return TRUE;
    }

    public function sendAlert(string $sender, string $payload) {
        $waitTimeBeforeRemoveDeliveredMessages = 0; //5 seconds (-1 never, 0 right now) in milliseconds
        $context = new ZMQContext();
        $socket = new ZMQSocket($context, ZMQ::SOCKET_DEALER);
        $socket->setSockOpt(ZMQ::SOCKOPT_IDENTITY, $sender);
        $socket->setSockOpt(ZMQ::SOCKOPT_LINGER, $waitTimeBeforeRemoveDeliveredMessages);
        $socket->connect("tcp://localhost:5556");
        $socket->send($payload);
        usleep(5000); //5 millisecondi //let the time to send the message
    }
}

class AlertTester {
    
    protected $ticker;
    protected $timeframe;
    static $exchange = 'TEST';

    public function __construct(string $ticker = 'XAUUSD', int $timeframe = 1)
    {
        $this->ticker = $ticker;
        $this->timeframe = $timeframe;
    }

    public function getPayload(string $action = 'buy', int $price = 0, int $size = 1) {
        $timenow = date('Y-m-dTH:i:sZ');
        $action == 'buy';
        $position = 'long';
        $previus_position = 'sell';
        if ($action == 'sell') {
            $position = 'short';
            $previus_position = 'buy';
        }
        $payload = [
            'timenow' => $timenow,
            'exchange' => self::$exchange,
            'ticker' => $this->ticker,
            'timeframe' => $this->timeframe,
            'strategy_order_action' => $action,
            'strategy_market_position' => $position,
            'strategy_position_size' => $size,
            'strategy_order_price' => $price,
            'strategy_prev_market_position' => $previus_position,
            'strategy_prev_market_position_size' => $size,
        ];
        return json_encode($payload);
    }
}
