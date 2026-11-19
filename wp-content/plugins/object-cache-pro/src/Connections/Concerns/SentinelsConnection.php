<?php
/**
 * Copyright © 2019-2026 Rhubarb Tech Inc. All Rights Reserved.
 *
 * The Object Cache Pro Software and its related materials are property and confidential
 * information of Rhubarb Tech Inc. Any reproduction, use, distribution, or exploitation
 * of the Object Cache Pro Software and its related materials, in whole or in part,
 * is strictly forbidden unless prior permission is obtained from Rhubarb Tech Inc.
 *
 * In addition, any reproduction, use, distribution, or exploitation of the Object Cache Pro
 * Software and its related materials, in whole or in part, is subject to the End-User License
 * Agreement accessible in the included `LICENSE` file, or at: https://objectcache.pro/eula
 */

declare(strict_types=1);

namespace RedisCachePro\Connections\Concerns;

use Throwable;

use RedisCachePro\Clients\ClientInterface;
use RedisCachePro\Exceptions\ConnectionException;

use function RedisCachePro\log;

trait SentinelsConnection
{
    /**
     * Returns the current Sentinel's URL.
     *
     * @return string
     */
    public function sentinelUrl()
    {
        return $this->sentinel;
    }

    /**
     * Set the pool for read commands.
     *
     * @return void
     */
    protected function setPool()
    {
        $this->pool = $this->replicas;
    }

    /**
     * Connect to the first available Sentinel.
     *
     * @return void
     */
    protected function connectToSentinels()
    {
        foreach ($this->sentinels as $url => $state) {
            if ($state === false) {
                continue;
            }

            try {
                $this->sentinel = $url;

                if ($state instanceof ClientInterface) {
                    $this->discoverPrimary();
                    $this->discoverReplicas();
                } else {
                    $this->establishConnections($url);
                }

                $this->setPool();

                return;
            } catch (Throwable $error) {
                $this->sentinels[$url] = false;

                log('warning', $error->getMessage());
            }
        }

        $lastMessage = isset($error) ? "[{$error->getMessage()}]" : '';

        throw new ConnectionException("Unable to connect to any valid sentinels {$lastMessage}", 0, $error ?? null);
    }

    /**
     * Run a command against Redis Sentinel.
     *
     * @param  string  $name
     * @param  array<mixed>  $parameters
     * @return mixed
     */
    public function command(string $name, array $parameters = [])
    {
        $this->lastCommand = null;

        $command = \strtoupper($name);
        $isReading = \in_array($command, $this->readonly);

        if ($isReading) {
            $isReading = ! \preg_match(
                '/options:(all|not)options|objectcache:meta/',
                \implode(' ', (array) ($parameters[0] ?? []))
            );
        }

        $node = $isReading && ! empty($this->pool)
            ? $this->pool[\array_rand($this->pool)]
            : $this->primary;

        try {
            $result = $node->command($name, $parameters);

            if ($command === 'MGET' && $result === false) {
                throw new ConnectionException('Failed to execute MGET command');
            }

            $this->lastCommand = $node->lastCommand;

            return $result;
        } catch (Throwable $error) {
            if ($node === $this->primary) {
                $this->connectToSentinels();
            }
            $node = $this->primary;
        }

        $result = $node->command($name, $parameters);

        $this->lastCommand = $node->lastCommand;

        return $result;
    }
}
