<?php

declare(strict_types=1);

namespace Jotup\Container;

use Jotup\Container\Exceptions\ClassNotFoundException;
use Jotup\Container\Exceptions\Exception;
use Jotup\Container\Exceptions\InheritanceException;
use Jotup\Container\Exceptions\InstancingException;
use Jotup\Container\Exceptions\InvalidArgumentException;
use Jotup\Container\Exceptions\RecursiveLinkException;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Throwable;

final class Builder
{
    /** @var array<string, true> */
    private array $aliasStack = [];

    /** @var array<string, true> */
    private array $concreteStack = [];

    public function __construct(
        private readonly Container $container
    ) {
    }

    /**
     * @throws Exception
     */
    public function build(string $id, array $values = []): object
    {
        $this->container->logDebug('Start building ' . $id);

        return $this->buildById($id, $values);
    }

    /**
     * @throws Exception
     */
    public function makeMethod(object $object, string $method, array $values = []): mixed
    {
        $this->container->logDebug('Start building method ' . $object::class . '::' . $method, $values);

        try {
            $reflectionMethod = new ReflectionMethod($object, $method);
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException(
                sprintf('Method %s::%s not found: %s', $object::class, $method, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }

        if (!$reflectionMethod->isPublic()) {
            throw new InvalidArgumentException(sprintf('Method %s::%s is not public.', $object::class, $method));
        }

        try {
            $arguments = $this->makeMethodArguments($reflectionMethod->getParameters(), $values);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                sprintf('Invalid argument in method %s::%s: %s', $object::class, $method, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }

        return $reflectionMethod->invokeArgs($object, $arguments);
    }

    /**
     * @throws Exception
     */
    private function buildById(string $id, array $values = []): object
    {
        [$bindingId, $binding, $concrete, $resolvedValues] = $this->resolveBinding($id, $values);

        if ($binding !== null && $binding->singleton && $this->container->hasStoredObject($bindingId)) {
            $this->container->logDebug($bindingId . ' already created, returning cached object');
            return $this->container->getStoredObject($bindingId);
        }

        if (isset($this->concreteStack[$concrete])) {
            throw new RecursiveLinkException(sprintf('Find recursive link for "%s"', $concrete));
        }

        $this->concreteStack[$concrete] = true;

        try {
            $object = $this->makeConcrete($concrete, $resolvedValues);
        } finally {
            unset($this->concreteStack[$concrete]);
        }

        if ($binding !== null && $binding->singleton) {
            $this->container->storeObject($bindingId, $object);
        }

        return $object;
    }

    /**
     * @return array{string, BindData|null, string, array}
     * @throws Exception
     */
    private function resolveBinding(string $id, array $values = []): array
    {
        $this->container->logDebug('Resolving binding ' . $id, $values);

        if (isset($this->aliasStack[$id])) {
            throw new RecursiveLinkException(sprintf('Find recursive link for "%s"', $id));
        }

        $binding = $this->container->getBinding($id);
        if ($binding === null) {
            $concrete = isset($values['class']) && is_string($values['class']) ? $values['class'] : $id;
            return [$id, null, $concrete, $values];
        }

        if ($binding->id !== $id) {
            $this->aliasStack[$id] = true;
            try {
                return $this->resolveBinding($binding->id, $values);
            } finally {
                unset($this->aliasStack[$id]);
            }
        }

        $resolvedValues = [...$binding->values, ...$values];
        $concrete = $binding->concrete;

        if (isset($resolvedValues['class']) && is_string($resolvedValues['class'])) {
            $concrete = $resolvedValues['class'];
        }

        return [
            $binding->id,
            $binding,
            $concrete,
            $resolvedValues,
        ];
    }

    /**
     * @throws Exception
     */
    private function makeConcrete(string $concrete, array $values = []): object
    {
        $this->container->logDebug('Start making concrete ' . $concrete, $values);

        try {
            $concreteClass = new \ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new ClassNotFoundException(
                sprintf('Class %s not found: %s', $concrete, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }

        if (!$concreteClass->isInstantiable()) {
            throw new InheritanceException(sprintf('%s is not instantiable', $concrete));
        }

        $constructor = $concreteClass->getConstructor();
        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            try {
                return new $concrete();
            } catch (Throwable $e) {
                throw new InstancingException(
                    sprintf('Can not create instance of class %s: %s', $concrete, $e->getMessage()),
                    (int) $e->getCode(),
                    $e
                );
            }
        }

        try {
            $args = $this->makeMethodArguments($constructor->getParameters(), $values);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                sprintf('Invalid argument in class %s: %s', $concrete, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }

        try {
            return $concreteClass->newInstanceArgs($args);
        } catch (Throwable $e) {
            if ($e instanceof \Jotup\Http\Exception\ValidationException) {
                throw $e;
            }

            throw new InstancingException(
                sprintf('Can not create instance of class %s: %s', $concrete, $e->getMessage()),
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param ReflectionParameter[] $params
     * @throws Exception
     */
    private function makeMethodArguments(array $params, array $values = []): array
    {
        $args = [];
        $positionalValues = $this->extractPositionalValues($values);

        foreach ($params as $param) {
            $this->container->logDebug('Setting parameter ' . $param->getName(), $values);

            $paramName = $param->getName();
            $hasValue = array_key_exists($paramName, $values);
            $paramValue = $hasValue ? $values[$paramName] : null;
            $type = $param->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                if ($hasValue) {
                    if ($paramValue === null && $type->allowsNull()) {
                        $args[] = null;
                        continue;
                    }

                    if (is_object($paramValue)) {
                        if (
                            $paramValue instanceof \Psr\Http\Message\ServerRequestInterface
                            && is_a($type->getName(), \Jotup\Http\Request\Request::class, true)
                        ) {
                            $hasValue = false;
                        }

                        if (!$hasValue) {
                            // Fall through to class autowiring below for validated request objects.
                        } elseif (!is_a($paramValue, $type->getName())) {
                            throw new InvalidArgumentException(sprintf(
                                'Parameter $%s must be instance of %s.',
                                $paramName,
                                $type->getName()
                            ));
                        } else {
                            $args[] = $paramValue;
                            continue;
                        }
                    }

                    if ($hasValue && !is_array($paramValue)) {
                        throw new InvalidArgumentException(sprintf(
                            'Parameter $%s must be instance of %s or an array of nested constructor values.',
                            $paramName,
                            $type->getName()
                        ));
                    }

                    if (
                        $hasValue
                        && is_object($paramValue)
                        && !is_a($paramValue, $type->getName())
                    ) {
                        throw new InvalidArgumentException(sprintf(
                            'Parameter $%s must be instance of %s.',
                            $paramName,
                            $type->getName()
                        ));
                    }

                }

                if (!$hasValue && $this->container->has($paramName)) {
                    $service = $this->container->get($paramName);

                    if (!is_a($service, $type->getName())) {
                        throw new InvalidArgumentException(sprintf(
                            'Service "%s" must be instance of %s.',
                            $paramName,
                            $type->getName()
                        ));
                    }

                    $args[] = $service;
                    continue;
                }

                if (!$hasValue && $this->hasMatchingPositionalObject($positionalValues, $type->getName())) {
                    $args[] = array_shift($positionalValues);
                    continue;
                }

                if (
                    !$hasValue
                    && $param->isDefaultValueAvailable()
                    && $type->allowsNull()
                    && !$this->container->has($type->getName())
                ) {
                    $args[] = $param->getDefaultValue();
                    continue;
                }

                $paramValues = $hasValue ? $paramValue : [];
                $args[] = $this->buildById($type->getName(), $paramValues);
                continue;
            }

            if (!$hasValue && $positionalValues !== []) {
                $args[] = array_shift($positionalValues);
                continue;
            }

            if (($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) && $hasValue) {
                $args[] = $paramValue;
                continue;
            }

            if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
                if ($param->isOptional()) {
                    $args[] = $param->getDefaultValue();
                    continue;
                }

                throw new InvalidArgumentException(sprintf(
                    'Unable to autowire parameter $%s with union/intersection type.',
                    $paramName
                ));
            }

            if ($hasValue) {
                $args[] = $paramValue;
                continue;
            }

            if ($param->isOptional()) {
                $args[] = $param->getDefaultValue();
                continue;
            }

            throw new InvalidArgumentException(sprintf(
                'Unable to resolve required parameter $%s.',
                $paramName
            ));
        }

        return $args;
    }

    private function hasMatchingPositionalObject(array $positionalValues, string $type): bool
    {
        if ($positionalValues === []) {
            return false;
        }

        $value = $positionalValues[array_key_first($positionalValues)];

        return is_object($value) && is_a($value, $type);
    }

    /**
     * @return list<mixed>
     */
    private function extractPositionalValues(array $values): array
    {
        $positionalValues = [];

        foreach ($values as $key => $value) {
            if (is_int($key)) {
                $positionalValues[] = $value;
            }
        }

        return $positionalValues;
    }
}
