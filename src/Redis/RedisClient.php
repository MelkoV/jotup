<?php

declare(strict_types=1);

namespace Jotup\Redis;

final class RedisClient
{
    /** @var resource|null */
    private $connection = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly ?string $password = null,
        private readonly int $database = 0,
        private readonly float $timeout = 2.0,
        private readonly float $readTimeout = 5.0,
    ) {
    }

    public function lPush(string $key, string $value): int
    {
        $response = $this->command(['LPUSH', $key, $value]);

        return (int) $response;
    }

    public function brPop(string $key, int $timeout = 0): ?string
    {
        $response = $this->command(['BRPOP', $key, (string) max(0, $timeout)]);

        if ($response === null) {
            return null;
        }

        if (!is_array($response) || count($response) !== 2) {
            throw new \RuntimeException('Unexpected BRPOP response.');
        }

        return $response[1] === null ? null : (string) $response[1];
    }

    public function __destruct()
    {
        if (is_resource($this->connection)) {
            fclose($this->connection);
        }
    }

    private function command(array $parts): mixed
    {
        $connection = $this->connect();
        $payload = $this->encodeCommand($parts);

        $written = @fwrite($connection, $payload);
        if ($written === false || $written !== strlen($payload)) {
            $this->disconnect();
            throw new \RuntimeException('Failed to write to Redis.');
        }

        return $this->readResponse($connection);
    }

    /**
     * @return resource
     */
    private function connect()
    {
        if (is_resource($this->connection)) {
            return $this->connection;
        }

        $connection = @stream_socket_client(
            sprintf('tcp://%s:%d', $this->host, $this->port),
            $errorCode,
            $errorMessage,
            $this->timeout,
        );

        if (!is_resource($connection)) {
            throw new \RuntimeException(sprintf('Unable to connect to Redis: %s (%d).', $errorMessage, $errorCode));
        }

        stream_set_timeout($connection, (int) $this->readTimeout, (int) (($this->readTimeout - floor($this->readTimeout)) * 1_000_000));

        $this->connection = $connection;

        if ($this->password !== null && $this->password !== '') {
            $this->assertSimpleOk($this->command(['AUTH', $this->password]));
        }

        if ($this->database > 0) {
            $this->assertSimpleOk($this->command(['SELECT', (string) $this->database]));
        }

        return $connection;
    }

    private function disconnect(): void
    {
        if (is_resource($this->connection)) {
            fclose($this->connection);
        }

        $this->connection = null;
    }

    private function encodeCommand(array $parts): string
    {
        $buffer = '*' . count($parts) . "\r\n";

        foreach ($parts as $part) {
            $value = (string) $part;
            $buffer .= '$' . strlen($value) . "\r\n" . $value . "\r\n";
        }

        return $buffer;
    }

    private function readResponse($connection): mixed
    {
        $prefix = fgetc($connection);
        if ($prefix === false) {
            $this->disconnect();
            throw new \RuntimeException('Failed to read response from Redis.');
        }

        return match ($prefix) {
            '+' => $this->readLine($connection),
            '-' => throw new \RuntimeException($this->readLine($connection)),
            ':' => (int) $this->readLine($connection),
            '$' => $this->readBulkString($connection),
            '*' => $this->readArray($connection),
            default => throw new \RuntimeException(sprintf('Unsupported Redis response prefix "%s".', $prefix)),
        };
    }

    private function readBulkString($connection): ?string
    {
        $length = (int) $this->readLine($connection);
        if ($length < 0) {
            return null;
        }

        $value = $this->readBytes($connection, $length);
        $this->readBytes($connection, 2);

        return $value;
    }

    private function readArray($connection): ?array
    {
        $length = (int) $this->readLine($connection);
        if ($length < 0) {
            return null;
        }

        $values = [];
        for ($i = 0; $i < $length; $i++) {
            $values[] = $this->readResponse($connection);
        }

        return $values;
    }

    private function readLine($connection): string
    {
        $line = fgets($connection);
        if ($line === false) {
            $this->disconnect();
            throw new \RuntimeException('Failed to read line from Redis.');
        }

        return rtrim($line, "\r\n");
    }

    private function readBytes($connection, int $length): string
    {
        $data = '';

        while (strlen($data) < $length) {
            $chunk = fread($connection, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                $this->disconnect();
                throw new \RuntimeException('Failed to read bytes from Redis.');
            }

            $data .= $chunk;
        }

        return $data;
    }

    private function assertSimpleOk(mixed $response): void
    {
        if ($response !== 'OK') {
            throw new \RuntimeException('Unexpected Redis response.');
        }
    }
}
