<?php

declare(strict_types=1);

namespace MultisiteAutoEnabler;

use Psr\Container\ContainerInterface;
use RuntimeException;

class Container implements ContainerInterface
{
    /** @var array<string, callable> */
    private array $definitions = [];

    /** @var array<string, mixed> */
    private array $resolved = [];

    public function bind(string $id, callable $factory): void
    {
        $this->definitions[$id] = $factory;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $id)
    {
        if (array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }

        if (!$this->has($id)) {
            throw new class("Service not found: {$id}") extends RuntimeException implements \Psr\Container\NotFoundExceptionInterface {};
        }

        $this->resolved[$id] = ($this->definitions[$id])($this);

        return $this->resolved[$id];
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $id): bool
    {
        return isset($this->definitions[$id]);
    }
}
