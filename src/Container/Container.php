<?php

namespace Jotup\Container;

use Jotup\Container\Exceptions\ClassNotFoundException;
use Jotup\Container\Exceptions\Exception;
use Jotup\Container\Exceptions\InheritanceException;
use Jotup\Container\Exceptions\InstancingException;
use Jotup\Container\Exceptions\InvalidArgumentException;
use Jotup\Container\Exceptions\ServiceNotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionException;

class Container implements ContainerInterface
{
    /** @var array<string, object> */
    private array $createdObjects = [];

    /** @var array<string, BindData> */
    private array $bindings = [];

    public function has(string $id): bool
    {
        return isset($this->createdObjects[$id]) || isset($this->bindings[$id]);
    }

    public function get(string $id): object
    {
        if (!$this->has($id)) {
            throw new ServiceNotFoundException();
        }
        if (isset($this->createdObjects[$id])) {
            return $this->createdObjects[$id];
        }
        $obj = $this->make($this->bindings[$id]->concrete, $this->bindings[$id]->values);
        if (!$this->bindings[$id]->reCreate) {
            $this->createdObjects[$id] = $obj;
        }
        return $obj;
    }

    /**
     * @param string $id
     * @param array $values
     * @return object
     * @throws Exception
     */
    public function make(string $id, array $values = []): object
    {
        if (isset($this->bindings[$id])) {
            $concrete = $this->bindings[$id]->concrete;
        } else {
            $concrete = $values['class'] ?? $id;
        }
        return $this->makeConcrete($concrete, $values);
    }

    /**
     * @param string $concrete
     * @param array $values
     * @return object
     * @throws Exception
     */
    private function makeConcrete(string $concrete, array $values = []): object
    {
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
            $args = $this->makeMethodArguments($params, $values);
        } catch (Exception $e) {
            throw new InvalidArgumentException(sprintf('Invalid argument in class %s: %s', $concrete, $e->getMessage()), $e->getCode(), $e);
        }
        try {
//            return $concreteClass->newLazyGhost(function (object $object) use ($args) {
//                $object->__construct($args);
//            });
            return $concreteClass->newInstanceArgs($args);
        } catch (ReflectionException $e) {
            throw new InstancingException(sprintf('Can not create instance of class %s: %s', $concrete, $e->getMessage()), $e->getCode(), $e);
        }
    }

    /**
     * @param array $params
     * @param array $values
     * @return array
     * @throws Exception
     */
    private function makeMethodArguments(array $params, array $values = []): array
    {
        $args = [];
        foreach ($params as $param) {
            if (isset($values[$param->getName()])) {
                $args[] = $values[$param->getName()];
                continue;
            }
            if ($param->hasType() && !$param->getType()->isBuiltin()) {
                $args[] = $this->getOrMake($param->getType()->getName());
                continue;
            }
            if ($param->isOptional()) {
                $args[] = $param->getDefaultValue();
                continue;
            }
            $args[] = null;
        }
        return $args;
    }

    /**
     * @param string $id
     * @return object
     * @throws Exception
     */
    private function getOrMake(string $id): object
    {
        if (isset($this->createdObjects[$id])) {
            return $this->createdObjects[$id];
        }
        return $this->make($id);
    }

    /**
     * @param string $id
     * @param class-string $concrete
     * @param bool $reCreate
     * @param array $values
     * @return void
     */
    public function bind(string $id, string $concrete, bool $reCreate = false, array $values = []): void
    {
        $this->bindData($id, new BindData($concrete, $reCreate, $values));
    }

    public function bindData(string $id, BindData $data): void
    {
        if (isset($this->createdObjects[$id])) {
            unset($this->createdObjects[$id]);
        }
        $this->bindings[$id] = $data;
    }

    public function bindObject(string $id, object $object): void
    {
        if (isset($this->bindings[$id])) {
            unset($this->bindings[$id]);
        }
        $this->createdObjects[$id] = $object;
    }
}