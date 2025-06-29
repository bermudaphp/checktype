<?php

declare(strict_types=1);

namespace Bermuda\Stdlib;

use InvalidArgumentException;

/**
 * Type inspection and assertion utility for runtime type checking.
 */
final class Types
{
    public const string TYPE_ARRAY    = 'array';
    public const string TYPE_OBJECT   = 'object';
    public const string TYPE_INT      = 'int';
    public const string TYPE_BOOL     = 'bool';
    public const string TYPE_STRING   = 'string';
    public const string TYPE_RESOURCE = 'resource';
    public const string TYPE_CALLABLE = 'callable';
    public const string TYPE_FLOAT    = 'float';
    public const string TYPE_NULL     = 'null';

    public const int CALLABLE_AS_OBJECT = 0b01;
    public const int OBJECT_AS_CLASS    = 0b10;

    /**
     * Determines the type of variable.
     *
     * @param mixed $value The value whose type is to be determined
     * @param int<self::TYPE_*> $flags Bitmask of flags modifying the type determination
     * @return string The variable type or class name
     */
    public static function getType(mixed $value, int $flags = 0): string
    {
        return match (true) {
            is_string($value)   => is_callable($value) ? self::handleCallableType($value, $flags) : self::TYPE_STRING,
            is_array($value)    => self::TYPE_ARRAY,
            is_bool($value)     => self::TYPE_BOOL,
            is_int($value)      => self::TYPE_INT,
            is_null($value)     => self::TYPE_NULL,
            is_resource($value) => self::TYPE_RESOURCE,
            is_callable($value) => self::handleCallableType($value, $flags),
            is_float($value)    => self::TYPE_FLOAT,
            is_object($value)   => self::handleObjectType($value, $flags),
            default             => self::TYPE_OBJECT,
        };
    }

    /**
     * Handles callable type determination based on flags.
     */
    private static function handleCallableType(mixed $value, int $flags): string
    {
        if (($flags & self::CALLABLE_AS_OBJECT) && is_object($value)) {
            return ($flags & self::OBJECT_AS_CLASS) ? $value::class : self::TYPE_OBJECT;
        }
        return self::TYPE_CALLABLE;
    }

    /**
     * Handles object type determination based on flags.
     */
    private static function handleObjectType(object $value, int $flags): string
    {
        return ($flags & self::OBJECT_AS_CLASS) ? $value::class : self::TYPE_OBJECT;
    }

    /**
     * Checks if the value is a valid class name.
     *
     * @param mixed $value The value to validate
     * @param class-string|null $expectedClass Optional specific class to compare against
     * @return bool True if valid class name, false otherwise
     */
    public static function isClass(mixed $value, ?string $expectedClass = null): bool
    {
        if (!is_string($value) || !class_exists($value)) {
            return false;
        }

        return $expectedClass === null || strcasecmp($value, $expectedClass) === 0;
    }

    /**
     * Checks if the value is a valid interface name.
     *
     * @param mixed $value The value to validate
     * @param class-string|null $expectedInterface Optional specific interface to compare against
     * @return bool True if valid interface name, false otherwise
     */
    public static function isInterface(mixed $value, ?string $expectedInterface = null): bool
    {
        if (!is_string($value) || !interface_exists($value)) {
            return false;
        }

        return $expectedInterface === null || strcasecmp($value, $expectedInterface) === 0;
    }

    /**
     * Checks if the value matches the specified type.
     *
     * @param mixed $value The value to check
     * @param string $expectedType The expected type or class name
     * @return bool True if matches, false otherwise
     */
    public static function is(mixed $value, string $expectedType): bool
    {
        $flags = $expectedType !== self::TYPE_CALLABLE ? self::CALLABLE_AS_OBJECT : 0;
        $actualType = self::getType($value, $flags);

        if ($actualType === self::TYPE_OBJECT) {
            return $expectedType === self::TYPE_OBJECT || $value instanceof $expectedType;
        }

        return $actualType === $expectedType;
    }

    /**
     * Checks if the value matches any of the provided types.
     *
     * @param mixed $value The value to check
     * @param string[] $expectedTypes Array of allowed types or class names
     * @return bool True if matches any type, false otherwise
     */
    public static function isAny(mixed $value, array $expectedTypes): bool
    {
        return array_any($expectedTypes, static fn($type) => self::is($value, $type));
    }

    /**
     * Checks if the value is a subclass of the specified class.
     *
     * @param mixed $value The value to check
     * @param class-string $parentClass The parent class
     * @return bool True if subclassed, false otherwise
     */
    public static function isSubclassOf(mixed $value, string $parentClass): bool
    {
        return is_string($value) && is_subclass_of($value, $parentClass);
    }

    /**
     * Checks if the value is a subclass of any of the specified classes.
     *
     * @param mixed $value The value to check
     * @param class-string[] $parentClasses Array of parent classes
     * @return bool True if subclass of any, false otherwise
     */
    public static function isSubclassOfAny(mixed $value, array $parentClasses): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return array_any($parentClasses, fn($class) => is_subclass_of($value, $class));
    }

    /**
     * Checks if the value is an instance of the specified class or interface.
     *
     * @param mixed $value The value to check
     * @param class-string $className The class or interface name
     * @return bool True if instance of, false otherwise
     */
    public static function isInstanceOf(mixed $value, string $className): bool
    {
        return $value instanceof $className;
    }

    /**
     * Checks if the value is an instance of any of the specified classes or interfaces.
     *
     * @param mixed $value The value to check
     * @param array<class-string> $classNames Array of class or interface names
     * @return bool True if instance of any, false otherwise
     */
    public static function isInstanceOfAny(mixed $value, array $classNames): bool
    {
        return array_any($classNames, static fn($className) => $value instanceof $className);
    }

    /**
     * Checks if the value is an instance of all the specified classes or interfaces.
     *
     * @param mixed $value The value to check
     * @param array<class-string> $classNames Array of class or interface names
     * @return bool True if instance of any, false otherwise
     */
    public static function isInstanceOfAll(mixed $value, array $classNames): bool
    {
        return array_all($classNames, static fn($className) => $value instanceof $className);
    }

    /**
     * Asserts that the value matches one of the allowed types.
     *
     * @param mixed $value The value to validate
     * @param string|string[] $expectedTypes Expected type(s)
     * @param string|null $message Optional custom error message
     * @throws InvalidArgumentException If type doesn't match
     */
    public static function assert(mixed $value, string|array $expectedTypes, ?string $message = null): void
    {
        $types = (array) $expectedTypes;

        if (!self::isAny($value, $types)) {
            throw new InvalidArgumentException(
                $message ?? self::buildErrorMessage($value, $types)
            );
        }
    }

    /**
     * Alias for assert() method for backward compatibility.
     *
     * @param mixed $value The value to validate
     * @param string|string[] $expectedTypes Expected type(s)
     * @param string|null $message Optional custom error message
     * @throws InvalidArgumentException If type doesn't match
     * @see self::assert()
     */
    public static function enforce(mixed $value, string|array $expectedTypes, ?string $message = null): void
    {
        self::assert($value, $expectedTypes, $message);
    }

    /**
     * Asserts that the value is not null and matches one of the allowed types.
     *
     * @template T
     * @param T $value The value to validate
     * @param string|string[] $expectedTypes Expected type(s)
     * @param string|null $message Optional custom error message
     * @return T The non-null value
     * @throws InvalidArgumentException If null or type doesn't match
     *
     * @psalm-assert !null $value
     */
    public static function assertNotNull(mixed $value, string|array $expectedTypes, ?string $message = null): mixed
    {
        if ($value === null) {
            throw new InvalidArgumentException($message ?? 'Value must not be null');
        }

        self::assert($value, $expectedTypes, $message);
        return $value;
    }

    /**
     * Builds error message for type mismatch.
     */
    private static function buildErrorMessage(mixed $value, array $expectedTypes): string
    {
        $actualType = self::getType($value, self::OBJECT_AS_CLASS);
        $expected = implode('|', $expectedTypes);
        $caller = self::getCallerInfo();

        return sprintf(
            'Type mismatch in %s: expected [%s], got [%s]',
            $caller,
            $expected,
            $actualType
        );
    }

    /**
     * Gets caller information for error messages.
     */
    private static function getCallerInfo(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        if (isset($trace[2])) {
            $caller = $trace[2];
            if (isset($caller['class'])) {
                return $caller['class'] . '::' . $caller['function'];
            }
            return $caller['function'] ?? 'unknown';
        }

        return 'unknown';
    }
}
