<?php

namespace Jotup\Container;

use Jotup\Container\Exceptions\BindException;
use Jotup\Container\Exceptions\ClassNotFoundException;
use Jotup\Container\Exceptions\Exception;
use Jotup\Container\Exceptions\InheritanceException;
use Jotup\Container\Exceptions\InstancingException;
use Jotup\Container\Exceptions\InvalidArgumentException;
use Jotup\Container\Exceptions\RecursiveLinkException;
use Jotup\Container\Exceptions\ServiceNotFoundException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;

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
        $service = $this->bindings[$id];

        $this->log('Send ' . $id . ' to making');
        $object = $this->make($id, $service->values);
        $this->log('Stop getting ' . $id . ', returning ' . $service->concrete);
        return $object;
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
        return $this->makeRecursive($id, $values);
    }

    /**
     * @param string $id
     * @param array $values
     * @param array $dependencies
     * @return object
     * @throws Exception
     */
    private function makeRecursive(string $id, array $values = [], array $dependencies = ['concrete' => [], 'stack' => []]): object
    {
        $this->log('Start making recursive ' . $id);
        if (isset($this->bindings[$id])) {
            if ($this->bindings[$id]->id !== $id) {
                $this->log($id . ' is link to ' . $this->bindings[$id]->id);
                return $this->make($this->bindings[$id]->id, $values);
            }
            if ($this->bindings[$id]->singleton) {
                $this->log($id . ' is singleton');
                if (isset($this->objects[$id])) {
                    $this->log($id . ' already created, ignoring values, returning');
                    return $this->objects[$id];
                }
            }
            $concrete = $this->bindings[$id]->concrete;
            $values = [...$this->bindings[$id]->values, ...$values];
        } else {
            $concrete = $values['class'] ?? $id;
        }
        $this->log('Concrete for ' . $id . ' is ' . $concrete);
        if (in_array($concrete, $dependencies['concrete'], true)) {
            throw new RecursiveLinkException(sprintf('Find recursive link for "%s"', $concrete));
        }
        $dependencies['stack'][] = $concrete;
        $dependencies['concrete'][] = $concrete;
        $object = $this->makeConcrete($concrete, $values, $dependencies);
        if (isset($this->bindings[$id]) && $this->bindings[$id]->singleton) {
            $this->log($id . ' pushed to bindings');
            $this->objects[$id] = $object;
        }
        return $object;
    }

    /**
     * @param string $concrete
     * @param array $values
     * @param array $dependencies
     * @return object
     * @throws Exception
     */
    private function makeConcrete(string $concrete, array $values = [], array $dependencies = []): object
    {
        $this->log('Start making concrete ' . $concrete);
        try {
            $concreteClass = new \ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new ClassNotFoundException(sprintf('Class %s not found: %s', $concrete, $e->getMessage()), $e->getCode(), $e);
        }
        if (!$concreteClass->isInstantiable()) {
            throw new InheritanceException(sprintf('%s is not instantiable', $concrete));
        }
        $constructor = $concreteClass->getConstructor();
        if ($constructor === null) {
            return new $concrete();
        }
        $params = $constructor->getParameters();
        if (!$params) {
            return new $concrete();
        }
        try {
            $args = $this->makeMethodArguments($params, $values, $dependencies);
        } catch (Exception $e) {
            throw new InvalidArgumentException(sprintf('Invalid argument in class %s: %s', $concrete, $e->getMessage()), $e->getCode(), $e);
        }
        try {
            return $concreteClass->newInstanceArgs($args);
        } catch (ReflectionException $e) {
            throw new InstancingException(sprintf('Can not create instance of class %s: %s', $concrete, $e->getMessage()), $e->getCode(), $e);
        }
    }

    /**
     * @param \ReflectionParameter[] $params
     * @param array $values
     * @param array $dependencies
     * @return array
     * @throws Exception
     */
    private function makeMethodArguments(array $params, array $values = [], array $dependencies = []): array
    {
        $args = [];
        foreach ($params as $param) {
            $this->log('Setting parameter ' . $param->getName(), $values);
            try {
                if ($param->hasType() && !$param->getType()->isBuiltin()) {
                    $paramValues = (isset($values[$param->getName()]) && is_array($values[$param->getName()])) ? $values[$param->getName()] : [];
                    $args[] = $this->resolveParameter($param->getName(), $param->getType()->getName(), $paramValues, $dependencies);
                    continue;
                }
                if (isset($values[$param->getName()])) {
                    $args[] = $values[$param->getName()];
                    continue;
                }
                if ($param->isOptional()) {
                    $args[] = $param->getDefaultValue();
                    continue;
                }
            } catch (ReflectionException $e) {
                throw new InstancingException($e->getMessage(), $e->getCode(), $e);
            }
            throw new InvalidArgumentException(sprintf(
                'Unable to resolve required parameter $%s.',
                $param->getName()
            ));
        }
        return $args;
    }

    /**
     * @param string $name
     * @param string $type
     * @param array $values
     * @param array $dependencies
     * @return object
     * @throws Exception
     */
    private function resolveParameter(string $name, string $type, array $values = [], array $dependencies = []): object
    {
        $this->log('Start resolving parameter ' . $name . ' with type ' . $type, $values);
        if (isset($values['class'])) {
            $this->log('Find class ' . $values['class'] . ' for ' . $name);
            $type = $values['class'];
        }
        /*if (isset($this->bindings[$name])) {
            $this->log('Find binding for ' . $name);
            $root = $this->getRootBindData($name);
            if ($this->checkInstance($root->concrete, $type)) {
                $this->log('Find concrete ' . $root->concrete . ' for ' . $name);
            }
            return $this->makeRecursive($root->concrete, $values, $dependencies);
        }*/
        return $this->makeRecursive($type, $values, $dependencies);
    }

    private function getRootBindData(string $id): BindData
    {
        $data = $this->bindings[$id];
        if ($id === $data->id) {
            return $data;
        }
        return $this->getRootBindData($data->id);
    }

    private function checkInstance(string $concrete, string $instanceOf): bool
    {
        try {
            $concreteClass = new \ReflectionClass($concrete);
            $instanceClass = new \ReflectionClass($instanceOf);
        } catch (ReflectionException $e) {
            return false;
        }
        if ($concreteClass->getName() === $instanceOf) {
            return true;
        }
        if ($instanceClass->isInterface() && !$concreteClass->implementsInterface($instanceOf)) {
            return false;
        }
        if (!$concreteClass->isSubclassOf($instanceOf)) {
            return false;
        }
        return true;
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

    private function bindObject($id, object $concrete, array $values = []): object
    {
        $className = get_class($concrete);
        $this->log($id . ' is object of class ' . $className);
        $this->log('Ignored `singleton` (set as true) for ' . $id);
        $this->log('Binding ' . $id . ' as singleton');
        $bindData = new BindData($id, $concrete, true, $values);
        $this->bindings[$id] = $bindData;
        $this->objects[$id] = $concrete;
        return $bindData;
    }

    private function log(string $message, array $context = []): void
    {
        if ($this->debug && $this->logger) {
            $this->logger->debug($message, $context);
        }
    }


}