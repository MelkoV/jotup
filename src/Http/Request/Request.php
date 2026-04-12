<?php

declare(strict_types=1);

namespace Jotup\Http\Request;

use BackedEnum;
use DateTime;
use Jotup\Database\Db;
use Jotup\ExecutionScope\ExecutionScopeProviderInterface;
use Jotup\Http\Exception\ValidationException;
use Jotup\Http\Request\Validation\EnumRule;
use Jotup\Http\Request\Validation\RuleInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

abstract class Request
{
    /** @var array<string, mixed> */
    private array $data;

    /** @var array<string, mixed> */
    private array $validated = [];

    public function __construct(
        protected readonly ServerRequestInterface $request,
        protected readonly ?Db $db = null,
        protected readonly ?ExecutionScopeProviderInterface $executionScopeProvider = null,
    ) {
        $this->data = $this->extractData();
        $this->prepareForValidation();
        $this->validated = $this->validateData();
    }

    /**
     * @return array<string, list<string|RuleInterface>>
     */
    abstract public function rules(): array;

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        return $this->validated;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function route(string $key, mixed $default = null): mixed
    {
        $params = $this->request->getAttribute('route_params', []);

        return is_array($params) ? ($params[$key] ?? $default) : $default;
    }

    public function userId(): ?string
    {
        $userId = $this->request->getAttribute('user_id');
        if (is_string($userId) && $userId !== '') {
            return $userId;
        }

        return $this->executionScopeProvider?->get()?->userId;
    }

    public function db(): ?Db
    {
        return $this->db;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function merge(array $data): void
    {
        $this->data = [...$this->data, ...$data];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractData(): array
    {
        $query = $this->request->getQueryParams();
        $body = $this->request->getParsedBody();
        $parsedBody = is_array($body) ? $body : [];
        $routeParams = $this->request->getAttribute('route_params', []);
        $routeParams = is_array($routeParams) ? $routeParams : [];

        return [...$query, ...$parsedBody, ...$routeParams];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(): array
    {
        $errors = [];
        $validated = [];
        $messages = $this->messages();

        foreach ($this->rules() as $field => $rules) {
            $value = $this->data[$field] ?? null;
            $nullable = in_array('nullable', $rules, true);
            $bail = in_array('bail', $rules, true);
            $required = in_array('required', $rules, true);

            if ($value === null || $value === '') {
                if ($required) {
                    $errors[$field][] = $messages[$field . '.required'] ?? sprintf('The %s field is required.', $field);
                } elseif ($nullable) {
                    $validated[$field] = null;
                }

                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'required' || $rule === 'nullable' || $rule === 'bail') {
                    continue;
                }

                $message = null;
                $ruleName = '';

                if (is_string($rule)) {
                    [$ruleName] = explode(':', $rule, 2);
                    $message = $this->validateStringRule($field, $value, $rule);
                } else {
                    $ruleName = $rule->name();
                    $message = $rule->validate($field, $value, $this->data, $this);

                    if ($message === null && $rule instanceof EnumRule && !$value instanceof BackedEnum) {
                        $enumClass = $rule->enumClass();
                        $value = $enumClass::from($value);
                    }
                }

                if ($message !== null) {
                    $errors[$field][] = $messages[$field . '.' . $ruleName] ?? $message;
                    if ($bail) {
                        break;
                    }
                }
            }

            if (!isset($errors[$field])) {
                $validated[$field] = $value;
            }
        }

        if ($errors !== []) {
            $firstError = null;
            foreach ($errors as $fieldErrors) {
                $firstError = $fieldErrors[0] ?? null;
                if (is_string($firstError) && $firstError !== '') {
                    break;
                }
            }

            throw new ValidationException($errors, $firstError ?? 'Validation failed');
        }

        return $validated;
    }

    private function validateStringRule(string $field, mixed &$value, string $rule): ?string
    {
        [$ruleName, $options] = array_pad(explode(':', $rule, 2), 2, null);

        return match ($ruleName) {
            'string' => is_string($value) ? null : sprintf('The %s field must be a string.', $field),
            'boolean' => $this->validateBoolean($field, $value),
            'integer' => $this->validateInteger($field, $value),
            'uuid' => Uuid::isValid((string) $value) ? null : sprintf('The %s field must be a valid UUID.', $field),
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : sprintf('The %s field must be a valid email address.', $field),
            'date' => $this->validateDate($field, $value),
            'min' => $this->validateMin($field, $value, (int) $options),
            'max' => $this->validateMax($field, $value, (int) $options),
            'decimal' => $this->validateDecimal($field, $value, $options ?? '0,0'),
            'confirmed' => ($this->data[$options ?? ''] ?? null) == $value ? null : sprintf('The %s field confirmation does not match.', $field),
            default => null,
        };
    }

    private function validateBoolean(string $field, mixed &$value): ?string
    {
        $normalized = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($normalized === null && !in_array($value, [0, 1, '0', '1'], true)) {
            return sprintf('The %s field must be true or false.', $field);
        }

        $value = $normalized ?? in_array($value, [1, '1'], true);

        return null;
    }

    private function validateInteger(string $field, mixed &$value): ?string
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return sprintf('The %s field must be an integer.', $field);
        }

        $value = (int) $value;

        return null;
    }

    private function validateDate(string $field, mixed &$value): ?string
    {
        try {
            $value = $value instanceof DateTime ? $value : new DateTime((string) $value);
            return null;
        } catch (\Throwable) {
            return sprintf('The %s field must be a valid date.', $field);
        }
    }

    private function validateMin(string $field, mixed $value, int $min): ?string
    {
        if (is_string($value) && mb_strlen($value) < $min) {
            return sprintf('The %s field must be at least %d characters.', $field, $min);
        }

        if ((is_int($value) || is_float($value)) && $value < $min) {
            return sprintf('The %s field must be at least %d.', $field, $min);
        }

        return null;
    }

    private function validateMax(string $field, mixed $value, int $max): ?string
    {
        if (is_string($value) && mb_strlen($value) > $max) {
            return sprintf('The %s field must not be greater than %d characters.', $field, $max);
        }

        if ((is_int($value) || is_float($value)) && $value > $max) {
            return sprintf('The %s field must not be greater than %d.', $field, $max);
        }

        return null;
    }

    private function validateDecimal(string $field, mixed &$value, string $range): ?string
    {
        [$min, $max] = array_map('intval', explode(',', $range));
        $string = (string) $value;

        if (!preg_match('/^-?\d+(?:\.(\d+))?$/', $string, $matches)) {
            return sprintf('The %s field must be a decimal number.', $field);
        }

        $decimals = isset($matches[1]) ? strlen($matches[1]) : 0;
        if ($decimals < $min || $decimals > $max) {
            return sprintf('The %s field must have between %d and %d decimal places.', $field, $min, $max);
        }

        $value = (float) $string;

        return null;
    }
}
