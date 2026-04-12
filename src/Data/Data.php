<?php

declare(strict_types=1);

namespace Jotup\Data;

use BackedEnum;
use DateTime;
use DateTimeInterface;
use JsonSerializable;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Lightweight internal data object layer.
 *
 * Original inspiration: Spatie Laravel Data (`Spatie\LaravelData\Data`).
 */
abstract class Data implements JsonSerializable
{
    public static function from(array|object $payload): static
    {
        if (is_object($payload)) {
            $payload = get_object_vars($payload);
        }

        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new static();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $payload)) {
                $arguments[] = self::castValue($payload[$name], $parameter);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            $arguments[] = null;
        }

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * @return array<int, static>
     */
    public static function collect(iterable $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = static::from($item);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->transform();
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(): array
    {
        $data = [];

        foreach (get_object_vars($this) as $key => $value) {
            $data[$key] = $this->normalizeValue($value);
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->transform();
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->transform();
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if (is_array($value)) {
            return array_map($this->normalizeValue(...), $value);
        }

        return $value;
    }

    private static function castValue(mixed $value, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType) {
            return $value;
        }

        $typeName = $type->getName();

        if ($value === null) {
            return null;
        }

        if ($type->isBuiltin()) {
            return match ($typeName) {
                'int' => (int) $value,
                'float' => (float) $value,
                'bool' => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? (bool) $value,
                'string' => (string) $value,
                'array' => (array) $value,
                default => $value,
            };
        }

        if (is_a($typeName, BackedEnum::class, true) && !$value instanceof BackedEnum) {
            return $typeName::from($value);
        }

        if (is_a($typeName, self::class, true) && (is_array($value) || is_object($value))) {
            return $typeName::from($value);
        }

        if (is_a($typeName, DateTimeInterface::class, true) && is_string($value)) {
            return new $typeName($value);
        }

        if ($typeName === DateTimeInterface::class && is_string($value)) {
            return new DateTime($value);
        }

        return $value;
    }
}
