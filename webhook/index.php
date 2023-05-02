<?php
//require_once __DIR__ . '/../functions.php';
/**
 * Webhook
 */

$socketAddress = "tcp://*:5560";
$authToken = "auth_token";
$remoteAddress = $_SERVER['REMOTE_ADDR'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

define ('ALERT_SYMBOL','alert_symbol');
define ('AUTH_TOKEN','auth_token');

if ($requestMethod === 'POST' ) {
    $payload = json_decode(file_get_contents('php://input'), true);
    $client = new Publisher($socketAddress, $authToken);
    $client->publish($payload);
}

if ($requestMethod === 'GET') {
    echo("Up and running");
    $testReq = new TestRequest();
    if ($testReq->isValid()) {
        $alertTest = new AlertTester($authToken);
        $payload = $alertTest->getPayload($testReq->getSymbol(), $testReq->getAction(), $testReq->getVolume());
        $client = new Publisher($socketAddress, $authToken);
        if ($client->publish($payload))
            echo(" & published");
    }
}

class Publisher {
    
    private $socketAddress;

    private $authToken;

    static $messageLifeTime = 30000; //(-1 forever, 0 right now, > 0 expiration time) in milliseconds   

    public function __construct(string $socketAddress, string $authToken)
    {
        $this->socketAddress = $socketAddress;
        $this->authToken = $authToken;
    }

    protected function isValidPayload(array $payload): bool {
        
        $valid = (
            isset($payload['alert_time']) &&
            isset($payload['alert_symbol']) &&
            isset($payload['order_action']) &&
            isset($payload['order_price']) &&
            isset($payload['order_volume']) &&
            isset($payload['auth_token'])
        );

        error_log('Processing payload...');
        error_log(print_r($payload, true));

        if ($valid == FALSE) {
            error_log('Invalid payload');
            return FALSE;
        }
        error_log('Valid payload');
        return TRUE;
    }

    protected function isAuthorized(array $payload): bool {
        if ($this->authToken == $payload[AUTH_TOKEN])
            return TRUE;

        error_log('Not authorized token: ' . $payload[AUTH_TOKEN]);
        return FALSE;
    }

    public function publish(array $payload): bool {

        if ($this->isValidPayload($payload) && $this->isAuthorized($payload)) {
            $context = new ZMQContext();
            $socket = new ZMQSocket($context, ZMQ::SOCKET_PUB);
            //$socket->setSockOpt(ZMQ::SOCKOPT_LINGER, self::$messageLifeTime);
            $socket->bind($this->socketAddress);
            //send message
            usleep (500000);
            $symbol = $payload[ALERT_SYMBOL];
            $socket->send($symbol, ZMQ::MODE_SNDMORE);
            $socket->send(json_encode($payload));
            usleep (10000);
            return TRUE;
        }
        return FALSE;
    }

    /* abandoned code
    public function sendAlert(string $payload) {
        $context = new ZMQContext();
        $socket = new ZMQSocket($context, ZMQ::SOCKET_DEALER);
        $socket->setSockOpt(ZMQ::SOCKOPT_IDENTITY, $this->identity);
        $socket->setSockOpt(ZMQ::SOCKOPT_LINGER, self::$messageLifeTime);
        $socket->connect($this->socketAddress);
        $socket->send($payload);
        usleep(1000); //1000 microsecondi = 1 millisecondo //we let time to send the message
    }
    */
}

class AlertTester {

    private $authToken;

    private $exchange = 'TESTER';

    public function __construct(string $authToken)
    {
        $this->authToken = $authToken;
    }

    public function getPayload(string $symbol, string $action, float $volume): array {
        $price = 0;
        $timeframe = '5M';
        $time = date('Y-m-dTH:i:sZ');
        return [
            'auth_token' => $this->authToken,
            'alert_name' => 'Test payload',
            'alert_time' => $time,
            'alert_exchange' => $this->exchange,
            'alert_symbol' => $symbol,
            'alert_timeframe' => $timeframe,
            'order_action' => $action,
            'order_price' => $price,
            "order_volume" => $volume,
            'order_tp' => 3,
            'order_sl' => 1,
            'order_magic' => 1000
        ];
    }
}

class TestRequest {
    
    private $action;

    private $symbol;

    private $volume;

    public function isValid() {
        if ($_GET && isset($_GET['action']) && isset($_GET['symbol'])) {
            if (in_array($_GET['action'], ['buy','sell'])) {
                $this->action = $_GET['action'];
                $this->symbol = $_GET['symbol'];
                $this->volume = isset($_GET['volume']) ? $_GET['volume'] : 0.1;
                return TRUE;
            }
        }
        return FALSE;
    }

    public function getSymbol()
    {
        return $this->symbol;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getVolume()
    {
        return $this->volume;
    }
}