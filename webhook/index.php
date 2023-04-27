<?php
require_once __DIR__ . '/../functions.php';
/**
 * Webhook
 */
$socketAddress = "tcp://*:5560";
$remoteAddress = $_SERVER['REMOTE_ADDR'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$channel = "XAUUSD.r";

if ($requestMethod === 'POST') {
    $alertPayload = file_get_contents('php://input');
    $client = new Client($socketAddress);
    if ($client->isValidPayload($alertPayload)) {
        $client->publishAlert($channel, $alertPayload);
    }
}

if ($requestMethod === 'GET') {
    echo("Hello world");
    if ($_GET && isset($_GET['alert'])) {
        $alert = $_GET['alert'];
        if ($alert == 'buy' || $alert == 'sell') {
            $alertTester = new AlertTester();
            $alertPayload  = $alertTester->getPayload($alert);
            $client = new Client($socketAddress);
            if ($client->isValidPayload($alertPayload)) {
                $client->publishAlert($channel, $alertPayload);
            }
        }
    }
}

class Client {
    
    private $socketAddress;

    private $identity;

    static $messageLifeTime = 30000; //(-1 forever, 0 right now, > 0 expiration time) in milliseconds   

    public function __construct(string $socketAddress, string $identity = 'webhook')
    {
        $this->socketAddress = $socketAddress;
        $this->identity = $identity;
    }

    public function isValidPayload(string $payload) {
        return TRUE;
    }

    public function publishAlert(string $channel, string $payload) {

        $context = new ZMQContext();
        $socket = new ZMQSocket($context, ZMQ::SOCKET_PUB);
        //$socket->setSockOpt(ZMQ::SOCKOPT_IDENTITY, $this->identity); not valid for SOCKET_PUB
        //$socket->setSockOpt(ZMQ::SOCKOPT_LINGER, self::$messageLifeTime);
        $socket->bind($this->socketAddress);
        //send message
        usleep (500000);
        $socket->send($channel, ZMQ::MODE_SNDMORE);
        $socket->send($payload);
        usleep (1000);

    }

    public function sendAlert(string $sender, string $payload) {
        $context = new ZMQContext();
        $socket = new ZMQSocket($context, ZMQ::SOCKET_DEALER);
        $socket->setSockOpt(ZMQ::SOCKOPT_IDENTITY, $this->identity);
        $socket->setSockOpt(ZMQ::SOCKOPT_LINGER, self::$messageLifeTime);
        $socket->connect($this->socketAddress);
        $socket->send($payload);
        usleep(1000); //1000 microsecondi = 1 millisecondo //we let time to send the message
    }
}

class AlertTester {

    protected $ticker;

    protected $timeframe;

    static $exchange = 'TEST';

    public function __construct(string $ticker = 'XAUUSD', string $timeframe = '1')
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
