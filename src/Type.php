<?php

namespace Bermuda\CheckType;

/**
 * The Type class provides utility methods for determining and enforcing variable types.
 */
final class Type
{
    public const TYPE_ARRAY    = 'array';
    public const TYPE_OBJECT   = 'object';
    public const TYPE_INT      = 'int';
    public const TYPE_BOOL     = 'bool';
    public const TYPE_STRING   = 'string';
    public const TYPE_RESOURCE = 'resource';
    public const TYPE_CALLABLE = 'callable';
    public const TYPE_FLOAT    = 'float';
    public const TYPE_NULL     = 'null';

    public const FLAG_CALLABLE_AS_OBJECT = 1;
    public const FLAG_OBJECT_AS_CLASS    = 2;

    /**
     * Returns the type of a variable.
     *
     * This method determines the type of the provided variable using a predefined set of types.
     * Depending on the flags provided:
     * - If FLAG_OBJECT_AS_CLASS is set and the variable is an object, its class name is returned.
     * - If FLAG_CALLABLE_AS_OBJECT is set and the variable is a callable object, it is returned as TYPE_OBJECT.
     *
     * @param mixed $var The variable whose type is to be determined.
     * @param int-mask-of<self::FLAG_*> $flags Bitmask of flags modifying the type determination:
     *        - FLAG_CALLABLE_AS_OBJECT: For callable objects, return TYPE_OBJECT if set.
     *        - FLAG_OBJECT_AS_CLASS: For objects, return the class name if set.
     *
     * @return string The variable type as one of the TYPE_* constants or a class-string when applicable.
     */
    public static function getType(mixed $var, int $flags = 0): string
    {
        return match (true) {
            is_array($var)    => self::TYPE_ARRAY,
            is_bool($var)     => self::TYPE_BOOL,
            is_int($var)      => self::TYPE_INT,
            is_string($var)   => self::TYPE_STRING,
            is_null($var)     => self::TYPE_NULL,
            is_resource($var) => self::TYPE_RESOURCE,
            is_callable($var) => ($flags & self::FLAG_CALLABLE_AS_OBJECT && is_object($var))
                ? ($flags & self::FLAG_OBJECT_AS_CLASS ? $var::class : self::TYPE_OBJECT)
                : self::TYPE_CALLABLE,
            is_float($var)    => self::TYPE_FLOAT,
            (($flags & self::FLAG_OBJECT_AS_CLASS) && is_object($var))
            => $var::class,
            default           => self::TYPE_OBJECT,
        };
    }

    /**
     * Checks whether the provided variable is a valid class name.
     *
     * Returns true if $var is a string representing an existing class name. If $concrete is provided,
     * the comparison is done case-insensitively.
     *
     * @param mixed $var The value to validate as a class name.
     * @param string|null $concrete Optional concrete class name to compare against.
     *
     * @return bool True if $var is a valid class name; false otherwise.
     */
    public static function isClass(mixed $var, ?string $concrete = null): bool
    {
        if (!is_string($var) || !class_exists($var)) {
            return false;
        }
        if ($concrete !== null) {
            return strcasecmp($var, $concrete) === 0;
        }
        return true;
    }

    /**
     * Checks whether the provided variable is a valid interface name.
     *
     * Returns true if $var is a string representing an existing interface name. If $concrete is provided,
     * the comparison is done case-insensitively.
     *
     * @param mixed $var The value to validate as an interface name.
     * @param string|null $concrete Optional concrete interface name to compare against.
     *
     * @return bool True if $var is a valid interface name; false otherwise.
     */
    public static function isInterface(mixed $var, ?string $concrete = null): bool
    {
        if (!is_string($var) || !interface_exists($var)) {
            return false;
        }
        if ($concrete !== null) {
            return strcasecmp($var, $concrete) === 0;
        }
        return true;
    }

    /**
     * Checks if the variable's type matches the specified type.
     *
     * If the variable is an object and its type is TYPE_OBJECT, it returns true if the specified
     * type equals TYPE_OBJECT or if the variable is an instance of the provided type.
     *
     * @param mixed $var The variable to check.
     * @param string $type The expected type (one of the TYPE_* constants or a class-string).
     *
     * @return bool True if the variable matches the specified type; false otherwise.
     */
    public static function match(mixed $var, string $type): bool
    {
        if ($type !== self::TYPE_CALLABLE) $flag = self::FLAG_CALLABLE_AS_OBJECT;
        if (($actual = self::getType($var, $flag ?? 0)) === self::TYPE_OBJECT) {
            return self::TYPE_OBJECT === $type ? true : $var instanceof $type;
        }

        return $actual === $type;
    }

    /**
     * Checks if the variable's type matches any of the provided types.
     *
     * @param mixed $var The variable to check.
     * @param string[] $types An array of allowed types (TYPE_* constants or class-strings).
     *
     * @return bool True if the variable's type matches at least one type; false otherwise.
     */
    public static function matchAny(mixed $var, array $types): bool
    {
        return array_any($types, static fn(string $type) => Type::match($var, $type));
    }

    /**
     * Checks whether the provided variable is a subclass of the specified class.
     *
     * Expects $var to be a string representing a class name.
     *
     * @param mixed $var The variable to check.
     * @param string $class The parent class to check against.
     *
     * @return bool True if $var is a subclass of $class; false otherwise.
     */
    public static function subclassOf(mixed $var, string $class): bool
    {
        return is_string($var) && is_subclass_of($var, $class);
    }

    /**
     * Determines whether the given variable is an instance of the specified interface.
     *
     * @param mixed $var The variable to check.
     * @param string $interface The interface to check for.
     *
     * @return bool True if $var implements the given interface; false otherwise.
     */
    public function instanceOf(mixed $var, string $interface): bool
    {
        return $var instanceof $interface;
    }

    /**
     * Determines whether the given variable is an instance of any of the specified interfaces.
     *
     * @param mixed $var The variable to check.
     * @param array $interfaces An array of interface names.
     *
     * @return bool True if $var implements at least one of the specified interfaces; false otherwise.
     */
    public function instanceOfAny(mixed $var, array $interfaces): bool
    {
        return array_any($var, static fn(mixed $v) => Type::instanceOf($v, $interfaces));
    }

    /**
     * Checks whether the provided variable is a subclass of any of the given classes.
     *
     * Expects $var to be a string representing a class name.
     *
     * @param mixed $var The variable to check.
     * @param array $classes An array of parent classes.
     *
     * @return bool True if $var is a subclass of at least one provided class; false otherwise.
     */
    public static function subclassOfAny(mixed $var, array $classes): bool
    {
        return array_any($classes, static fn(string $class) => Type::subclassOf($var, $class));
    }

    /**
     * Enforces that the variable matches one of the allowed types.
     *
     * If the variable does not match any of the provided types, an InvalidArgumentException is thrown.
     * The error message includes the calling context, the allowed types, and the actual type.
     *
     * @param mixed $var The variable to enforce.
     * @param string|string[] $types An string or array of allowed types. {@see Type::match()}
     * @param string|null $msg Optional custom error message.
     *
     * @throws \InvalidArgumentException if the variable's type is not one of the specified types.
     *
     * @return void
     */
    public static function enforce(mixed $var, string|array $types, ?string $msg = null): void
    {
        if (is_string($types)) $types = [$types];
        if (!self::matchAny($var, $types)) {
            if (!$msg) {
                $actual = self::getType($var);
                $allowed = implode(', ', $types);

                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
                $callerInfo = 'unknown function';
                if (isset($trace[1])) {
                    if (isset($trace[1]['class'])) {
                        $callerInfo = $trace[1]['class'] . '::' . $trace[1]['function'];
                    } elseif (isset($trace[1]['function'])) {
                        $callerInfo = $trace[1]['function'];
                    }
                }

                $msg = "Invalid argument passed to {$callerInfo}: Expected one of [{$allowed}], got {$actual}.";
            }

            throw new \InvalidArgumentException($msg);
        }
    }
}
