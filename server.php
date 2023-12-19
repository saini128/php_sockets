<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use React\Socket\Server as ReactServer;
use React\Socket\SecureServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require 'vendor/autoload.php';

class VoiceCall implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Create ServerContext with SSL certificate and private key paths
$context = new \React\Socket\ServerContext([
    'local_cert' => '/etc/ssl/certs/ssl-cert-snakeoil.pem',
    'local_pk'   => '/etc/ssl/private/ssl-cert-snakeoil.key',
]);

$server = new IoServer(
    new HttpServer(
        new WsServer(
            new VoiceCall()
        )
    ),
    new SecureServer(new ReactServer('0.0.0.0:3001'), $context)
);

echo "Server started at wss://singhropar.tech:3001\n";

$server->run();
