<?php

declare(strict_types=1);

namespace Jotup\DI;

use Jotup\DI\Exceptions\ClassNotFoundException;
use Jotup\DI\Exceptions\Exception;
use Jotup\DI\Exceptions\InheritanceException;
use Jotup\DI\Exceptions\InstancingException;
use Jotup\DI\Exceptions\InvalidArgumentException;
use Jotup\Exceptions\CoreException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * @template T of object
 */
final class Container
{
    private static Container $instance;

    /** @var array<class-string<T>, BindData>  */
    private array $bindings = [];

    /** @var array<class-string<T>, BindData>  */
    private array $components = [];

    /** @var array<class-string<T>, T>  */
    private array $cacheConcretes = [];


    /** @var array<string, T>  */
    private array $cacheComponents = [];

    public static function debug(): void
    {
        self::getInstance()->debugContainer();
    }

    /**
     * @param class-string<T> $name
     * @param array $values
     * @return T
     * @throws Exception
     */
    public static function get(string $name, array $values = []): object
    {
        return self::getInstance()->getConcrete($name, $values);
    }


    /**
     * @param string $name
     * @param class-string<T> $as
     * @return T
     * @throws Exception
     */
    public static function getComponent(string $name, string $as): object
    {
        return self::getInstance()->getConcreteComponent($name, $as);
    }

    /**
     * @param class-string $contract
     * @param class-string $concrete
     * @param bool $reCreate
     * @param array $values
     * @return void
     * @throws Exception
     */
    public static function bind(string $contract, string $concrete, bool $reCreate = false, array $values = []): void
    {
        self::getInstance()->bindData($contract, new BindData($concrete, $reCreate, $values));
    }

    public static function bindComponent(string $name, array $config): void
    {
        self::getInstance()->bindComponentData($name, $config);
    }

    private function debugContainer(): void
    {
        var_dump($this->bindings);
    }

    /**
     * @param class-string $contract
     * @param BindData $data
     * @return void
     * @throws Exception
     */
    private function bindData(string $contract, BindData $data): void
    {
        if ($this->canBind($contract, $data)) {
            $this->bindings[$contract] = $data;
        }
    }

    private function bindComponentData(string $name, array $config): void
    {
        if (isset($this->cacheComponents[$name])) {
            unset($this->cacheComponents[$name]);
        }
        $this->components[$name] = $config;
    }

    /**
     * @param class-string $contract
     * @param BindData $data
     * @return bool
     * @throws ClassNotFoundException
     * @throws Exception
     */
    private function canBind(string $contract, BindData $data): bool
    {
        try {
            $concreteClass = new \ReflectionClass($data->concrete);
        } catch (\ReflectionException $e) {
            throw new ClassNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
        $this->checkInstance($concreteClass, $contract);
        return true;
    }


    /**
     * @param class-string<T> $contract
     * @param array $values
     * @return T
     * @throws Exception
     */
    private function getConcrete(string $contract, array $values = []): object
    {
        if (!isset($this->bindings[$contract])) {
            return $this->makeConcrete($contract, $values);
        }
        if (!$this->bindings[$contract]->reCreate && isset($this->cacheConcretes[$contract])) {
            return $this->cacheConcretes[$contract];
        }
        $concrete = $this->makeConcrete($this->bindings[$contract]->concrete, array_merge($this->bindings[$contract]->values, $values));
        if (!$this->bindings[$contract]->reCreate) {
            $this->cacheConcretes[$contract] = $concrete;
        }
        return $concrete;
    }

    /**
     * @param string $name
     * @param class-string<T> $as
     * @return T
     * @throws Exception
     */
    private function getConcreteComponent(string $name, string $as): object
    {
        if (isset($this->cacheComponents[$name])) {
            return $this->cacheComponents[$name];
        }
        $config = $this->components[$name] ?? [];
        if (!$config) {
            throw new InstancingException(sprintf('Config not found for component %s', $name));
        }
        if (!isset($config['class'])) {
            throw new InstancingException(sprintf('ClassName not found in config for component %s', $name));
        }
        $concreteClass = $config['class'];
        unset($config['class']);
        $concrete = $this->makeConcrete($concreteClass, values: $config, instanceOf: $as);
        $this->cacheComponents[$name] = $concrete;
        return $concrete;
    }

    /**
     * @param class-string<T> $concrete
     * @param array $values
     * @param ?class-string<T> $instanceOf
     * @return T
     * @throws Exception
     */
    private function makeConcrete(string $concrete, array $values = [], ?string $instanceOf = null): object
    {
        try {
            $concreteClass = new \ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new ClassNotFoundException(sprintf('Class %s not found: %s', $concrete, $e->getMessage()), $e->getCode(), $e);
        }
        if (!$concreteClass->isInstantiable()) {
            throw new InheritanceException(sprintf('%s is not instantiable', $concrete));
        }
        $this->checkInstance($concreteClass, $instanceOf);
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
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException(sprintf('Invalid argument in class %s: %s', $concrete, $e->getMessage()), $e->getCode(), $e);
        }
        try {
            return $concreteClass->newInstanceArgs($args);
        } catch (ReflectionException $e) {
            throw new InstancingException(sprintf('Can not create instance of class %s: %s', $concrete, $e->getMessage()), $e->getCode(), $e);
        }
    }

    /**
     * @param ReflectionClass $concreteClass
     * @param string|null $instanceOf
     * @return void
     * @throws Exception
     */
    private function checkInstance(ReflectionClass $concreteClass, ?string $instanceOf = null): void
    {
        if (!$instanceOf) {
            return;
        }
        if ($concreteClass->getName() === $instanceOf) {
            return;
        }
        try {
            $instanceClass = new ReflectionClass($instanceOf);
        } catch (ReflectionException $e) {
            throw new ClassNotFoundException(sprintf('Class %s not found: %s', $instanceOf, $e->getMessage()), $e->getCode(), $e);
        }
        if ($instanceClass->isInterface() && !$concreteClass->implementsInterface($instanceOf)) {
            throw new InheritanceException(sprintf('%s is not implements of %s', $concreteClass->getName(), $instanceOf));
        }
        if (!$concreteClass->isSubclassOf($instanceOf)) {
            throw new InheritanceException(sprintf('%s is not extends of %s', $concreteClass->getName(), $instanceOf));
        }
    }

    /**
     * @param ReflectionParameter[] $params
     * @param array $values
     * @return array
     * @throws Exception|ReflectionException
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
                $args[] = $this->getConcrete($param->getType()->getName());
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

    private static function getInstance(): Container
    {
        if (!isset(self::$instance)) {
            self::$instance = new Container();
        }
        return self::$instance;
    }

    private function __construct() {}

    private function __clone() {}

    /**
     * @throws CoreException
     */
    public function __serialize() {
        throw new CoreException('Container cannot be serialized');
    }

    /**
     * @throws CoreException
     */
    public function __unserialize(array $data): void
    {
        throw new CoreException('Container cannot be unserialized');
    }
}