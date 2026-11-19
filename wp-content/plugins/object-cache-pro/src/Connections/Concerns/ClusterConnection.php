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

use Generator;

use RedisCachePro\Clients\RelayCluster;
use RedisCachePro\Clients\PhpRedisCluster;
use RedisCachePro\Connections\Transaction;
use RedisCachePro\Exceptions\ConnectionException;

trait ClusterConnection
{
    /**
     * Execute pipelines as atomic `MULTI` transactions.
     *
     * @return object
     */
    public function pipeline()
    {
        return $this->multi();
    }

    /**
     * Hijack `multi()` calls to allow command logging.
     *
     * @param  ?int  $type
     * @return object
     */
    public function multi(?int $type = null)
    {
        return Transaction::multi($this);
    }

    /**
     * Run a command against Redis Cluster.
     *
     * @param  string  $name
     * @param  array<mixed>  $parameters
     * @return mixed
     */
    public function command(string $name, array $parameters = [])
    {
        $distributesReads = \strpos($this->config->cluster_failover, 'distribute') === 0
            || ($this->config->cluster_distribution_strategy ?? 'none') !== 'none';

        $readsFromPrimary = $distributesReads && \preg_match(
            '/options}:(all|not)options|objectcache}:meta/',
            \implode(' ', (array) ($parameters[0] ?? []))
        );

        if ($readsFromPrimary) {
            try {
                return $this->withoutFailover(function () use ($name, $parameters) {
                    return parent::command($name, $parameters);
                });
            } catch (ConnectionException $exception) {
                if (! \in_array(\strtoupper($name), $this->readonly)) {
                    throw $exception;
                }
            }
        }

        return parent::command($name, $parameters);
    }

    /**
     * Yields all keys matching the given pattern.
     *
     * @param  string|null  $pattern
     * @param  int  $limit
     * @return \Generator<array<int, mixed>>
     */
    public function listKeys(?string $pattern = null, int $limit = 500): Generator
    {
        foreach ($this->client->_masters() as $primary) {
            $iterator = null;

            do {
                $keys = $this->withoutFailover(function () use (&$iterator, $primary, $pattern, $limit) {
                    return $this->client->scan($iterator, $primary, $pattern, $limit);
                });

                if (! empty($keys)) {
                    yield $keys;
                }
            } while ($iterator > 0);
        }
    }

    /**
     * Pings first primary node.
     *
     * To ping a specific node, pass name of key as a string, or a hostname and port as array.
     *
     * @param  string|array<mixed>  $parameter
     * @return bool
     */
    public function ping($parameter = null)
    {
        if (\is_null($parameter)) {
            $primaries = $this->client->_masters();
            $parameter = \reset($primaries);
        }

        $result = $this->withoutFailover(function () use ($parameter) {
            return $this->command('ping', [$parameter]);
        });

        if ($result === false && \is_array($parameter)) {
            throw new ConnectionException(\sprintf('Unknown node %s:%d', $parameter[0] ?? '', $parameter[1] ?? 0));
        }

        return $result;
    }

    /**
     * Fetches information from the first primary node.
     *
     * To fetch information from a specific node, pass the key or a host:port as array.
     *
     * @param  string|null  $section
     * @param  string|array<string>  $key
     * @return bool
     */
    public function info($section = null, $key = null)
    {
        $sections = [
            'server', 'clients', 'persistence',
            'threads', 'cpu', 'memory',
            'sentinel', 'cluster', 'replication',
            'modules', 'keyspace',
            'stats', 'errorstats', 'latencystats', 'commandstats',
            'all', 'default', 'everything',
        ];

        $validSection = \is_string($section)
            && \in_array(\strtolower($section), $sections);

        // support old `info($key_or_address)` signature
        if (\is_null($key) && (\is_array($section) || ! $validSection)) {
            $key = $section;
            $section = null;
        }

        if (\is_null($key)) {
            $primaries = $this->client->_masters();
            $key = \reset($primaries);
        }

        $result = $this->withoutFailover(function () use ($key, $section) {
            return $this->command('info', array_filter([$key, $section]));
        });

        if ($result === false && \is_array($key)) {
            throw new ConnectionException(\sprintf('Unknown node %s:%d', $key[0] ?? '', $key[1] ?? 0));
        }

        return $result;
    }

    /**
     * Call `EVAL` script one key at a time and on all primaries when needed.
     *
     * @param  string  $script
     * @param  array<mixed>  $args
     * @param  int  $keys
     * @return mixed
     */
    public function eval(string $script, array $args = [], int $keys = 0)
    {
        if ($keys === 0) {
            // Will go to random primary
            return $this->command('eval', [$script, $args, 0]);
        }

        if ($keys === 1) {
            if (strpos($args[0], '{') === false) {
                // Must be run on all primaries
                return $this->evalWithoutHashTag($script, $args, 1);
            }

            // Will be called on the primary matching the hash-tag of the key
            return $this->command('eval', [$script, $args, 1]);
        }

        $results = [];

        foreach (array_slice($args, 0, $keys) as $key) {
            // Call this method recursively for each key
            $results[$key] = $this->eval($script, array_merge([$key], array_slice($args, $keys)), 1);
        }

        return $results;
    }

    /**
     * Call `EVAL` script on all primary nodes.
     *
     * @param  string  $script
     * @param  array<mixed>  $args
     * @param  int  $keys
     * @return mixed
     */
    protected function evalWithoutHashTag(string $script, array $args = [], int $keys = 0)
    {
        $results = [];
        $primaries = $this->client->_masters();

        foreach ($primaries as $primary) {
            $key = $this->randomKey($primary);

            if ($key) {
                $results[] = $this->command('eval', [$script, array_merge([$key], $args, ['use-argv']), 1]);
            }
        }

        return $results;
    }

    /**
     * Returns Redis cluster nodes.
     *
     * @return array<int, array<string, string|false>>
     */
    public function nodes()
    {
        $nodes = $this->rawCommand(
            $this->client->_masters()[0],
            'CLUSTER',
            'NODES'
        );

        $nodes = array_filter(explode("\n", $nodes));

        return array_map(function ($node) {
            $fields = explode(' ', $node);

            return [
                'address' => strstr($fields[1], '@', true),
                'flags' => str_replace(['myself,', 'myself'], '', $fields[2]),
            ];
        }, $nodes);
    }

    /**
     * Flush all nodes on the Redis cluster.
     *
     * @param  bool|null  $async
     * @return true
     */
    public function flushdb($async = null)
    {
        $useAsync = $async ?? $this->config->async_flush;

        $flush = function () use ($useAsync) {
            foreach ($this->client->_masters() as $primary) {
                $useAsync
                    ? $this->rawCommand($primary, 'flushdb', 'async')
                    : $this->command('flushdb', [$primary]);
            }
        };

        // Pin failover to `none` during the flush, otherwise PhpRedis may route
        // `FLUSHDB` calls to replicas (phpredis/phpredis#2890, assumed fixed after 6.3.0)
        $pinFailover = $this->client instanceof PhpRedisCluster
            && \version_compare((string) \phpversion('redis'), '6.3.0', '<=');

        $pinFailover ? $this->withoutFailover($flush) : $flush();

        return true;
    }

    /**
     * Handles `CONFIG` calls for cluster connections.
     *
     * @param  string  $command
     * @param  string  ...$args
     * @return mixed
     */
    public function config($command, ...$args)
    {
        $command = strtolower($command);

        if ($command == 'get') {
            return $this->withoutFailover(function () use ($command, $args) {
                $primaries = $this->client->_masters();
                array_unshift($args, reset($primaries), $command);

                $config = $this->command('config', $args);

                if (is_array($config) && array_keys($config) === range(0, count($config) - 1)) {
                    return array_column(array_chunk($config, 2), 1, 0);
                }

                return $config;
            });
        }

        if ($command == 'resetstat') {
            return $this->withoutFailover(function () {
                return array_reduce($this->client->_masters(), function ($carry, $primary) {
                    return $this->command('config', [$primary, 'resetstat']) && $carry;
                }, true);
            });
        }

        throw new ConnectionException("config|{$command} not implemented for cluster connections.");
    }

    /**
     * Handles `SLOWLOG` calls for cluster connections.
     *
     * @param  string  $command
     * @param  string  ...$args
     * @return mixed
     */
    public function slowlog($command, ...$args)
    {
        $command = strtolower($command);

        if ($command == 'get') {
            return $this->withoutFailover(function () use ($command, $args) {
                $log = [];

                foreach ($this->client->_masters() as $primary) {
                    $result = $this->client->rawCommand($primary, 'slowlog', $command, ...$args);

                    if ($result === false) {
                        return false;
                    }

                    foreach ($result as $item) {
                        $key = implode('|', array_slice($item[3], 0, 2));
                        if (! array_key_exists($key, $log) || $log[$key][2] < $item[2]) {
                            $log[$key] = $item;
                        }
                    }
                }

                usort($log, static function ($a, $b) {
                    return $b[1] <=> $a[1];
                });

                return isset($args[0]) && (int) $args[0] >= 0 ? array_slice($log, 0, (int) $args[0]) : $log;
            });
        }

        if ($command == 'reset') {
            return $this->withoutFailover(function () {
                return array_reduce($this->client->_masters(), function ($carry, $primary) {
                    return $this->client->rawCommand($primary, 'slowlog', 'reset') && $carry;
                }, true);
            });
        }

        throw new ConnectionException("slowlog|{$command} not implemented for cluster connections.");
    }

    /**
     * Execute callback with the cluster's routing options pinned to `none`.
     *
     * This prevents node-directed commands from being
     * routed to replicas (phpredis/phpredis#2890).
     *
     * Relay v0.22.0 supersedes `OPT_SLAVE_FAILOVER` with the `OPT_DISTRIBUTE`
     * and `OPT_FAILOVER` pair. Reading the legacy option returns `false` and
     * writing it resets both of the new options, so the new options must be
     * pinned directly whenever Relay supports them.
     *
     * @param  callable  $callback
     * @return mixed
     */
    public function withoutFailover(callable $callback)
    {
        $options = $this->client instanceof RelayCluster && \defined('Relay\Cluster::OPT_DISTRIBUTE')
            ? [
                $this->client::OPT_DISTRIBUTE => $this->client::DISTRIBUTE_NONE,
                $this->client::OPT_FAILOVER => $this->client::FAILOVER_NONE,
            ]
            : [
                $this->client::OPT_SLAVE_FAILOVER => $this->client::FAILOVER_NONE,
            ];

        $routing = [];

        try {
            foreach ($options as $option => $none) {
                $current = $this->client->getOption($option);

                if ($current !== $none) {
                    $routing[$option] = $current;
                    $this->client->setOption($option, $none);
                }
            }

            return $callback($this);
        } finally {
            foreach ($routing as $option => $value) {
                $this->client->setOption($option, $value);
            }
        }
    }
}
