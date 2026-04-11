<?php

namespace Jotup\Container;

use Jotup\Container\Exceptions\BindException;
use Jotup\Container\Exceptions\Exception;
use Jotup\Container\Exceptions\ServiceNotFoundException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Container implements ContainerInterface
{

    private ?LoggerInterface $logger = null;

    /** @var array<string|object> */
    private array $objects = [];

    /** @var array<string, BindData> */
    private array $bindings = [];

    public function __construct(
        private $debug = false
    ) {
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param string $id
     * @param string|object|null $concrete
     * @param bool $singleton
     * @param array $values
     * @return BindData
     * @throws BindException
     */
    public function bind(string $id, string|object|null $concrete = null, bool $singleton = false, array $values = []): BindData
    {
        $this->log('Start binding ' . $id);
        if (isset($this->bindings[$id])) {
            $this->log($id . ' already exists in bindings. Rewrite...');
        }
        if (isset($this->objects[$id])) {
            $this->log($id . ' already exists in objects. Delete...');
            unset($this->objects[$id]);
        }
        if (is_null($concrete)) {
            if (!isset($values['class'])) {
                throw new BindException('Binding concrete class was not provided');
            }
            $concrete = $values['class'];
        }
        if (is_object($concrete)) {
            if ($concrete instanceof BindData) {
                return $this->bindData($id, $concrete);
            }
            return $this->bindObject($id, $concrete, $values);
        }
        $bindData = new BindData($id, $concrete, $singleton, $values);
        $this->log('Binding ' . $id . ' with concrete ' . $concrete);
        $this->bindings[$id] = $bindData;
        return $bindData;
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]);
    }

    public function get(string $id): object
    {
        if (!isset($this->bindings[$id])) {
            throw new ServiceNotFoundException($id);
        }
        $this->log('Start getting ' . $id);

        return $this->make($id);
    }

    /**
     * Может принимать как ид из биндинга, так и название абстракции или конкретного класса.
     *
     * @param string $id
     * @param array $values
     * @return object
     * @throws Exception
     */
    public function make(string $id, array $values = []): object
    {
        $this->log('Start making ' . $id);
        return new Builder($this)->build($id, $values);
    }

    /**
     * @throws Exception
     */
    public function makeMethod(object $object, string $method, array $values = []): mixed
    {
        $this->log('Start making method ' . $object::class . '::' . $method, $values);

        return new Builder($this)->makeMethod($object, $method, $values);
    }

    /**
     * @throws BindException
     */
    private function bindData(string $id, BindData $concrete): BindData
    {
        $this->log($id . ' linked by BindData to ' . $concrete->id);
        $this->log('Ignored `singleton` and `values` for ' . $id);
        if (!isset($this->bindings[$concrete->id])) {
            throw new BindException(sprintf('%s linked to %s, but %s not exists in bindings', $id, $concrete->id, $concrete->id));
        }
        $this->log('Binding ' . $id . ' as link to ' . $concrete->id);
        $this->bindings[$id] = $concrete;
        return $concrete;
    }

    private function bindObject($id, object $concrete, array $values = []): BindData
    {
        $className = get_class($concrete);
        $this->log($id . ' is object of class ' . $className);
        $this->log('Ignored `singleton` (set as true) for ' . $id);
        $this->log('Binding ' . $id . ' as singleton');
        $bindData = new BindData($id, $className, true, $values);
        $this->bindings[$id] = $bindData;
        $this->objects[$id] = $concrete;
        return $bindData;
    }

    public function logDebug(string $message, array $context = []): void
    {
        if ($this->debug && $this->logger) {
            $this->logger->debug($message, $context);
        }
    }

    private function log(string $message, array $context = []): void
    {
        $this->logDebug($message, $context);
    }

    public function getBinding(string $id): ?BindData
    {
        return $this->bindings[$id] ?? null;
    }

    public function hasStoredObject(string $id): bool
    {
        return isset($this->objects[$id]);
    }

    public function getStoredObject(string $id): object
    {
        return $this->objects[$id];
    }

    public function storeObject(string $id, object $object): void
    {
        $this->objects[$id] = $object;
    }

}
