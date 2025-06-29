# Bermuda Types

Runtime type checking and validation library for PHP 8.4+.

## Installation

```bash
composer require bermudaphp/types
```

## Features

- Runtime type checking and validation via `Types` class
- Support for built-in PHP types and custom classes
- Type assertions with detailed error messages
- Backward compatibility with legacy `enforce()` method
- Full PHP 8.4 compatibility with typed constants
- Static analysis friendly (PHPStan Level 9, Psalm Level 1)
- Zero dependencies
- Lightweight and performant

## Types Utility

The `Types` class provides comprehensive runtime type checking and validation.

### Basic Type Checking

```php
use Bermuda\Stdlib\Types;

// Check basic types
Types::is($value, Types::TYPE_STRING);  // true if string
Types::is($value, Types::TYPE_INT);     // true if integer
Types::is($value, 'array');             // true if array

// Check multiple types
Types::isAny($value, [Types::TYPE_STRING, Types::TYPE_INT]); // true if string OR int

// Check object instances
Types::is($user, User::class);         // true if $user instanceof User
Types::isInstanceOf($user, UserInterface::class); // true if implements interface
```

### Type Assertions

```php
use Bermuda\Stdlib\Types;

// Throws InvalidArgumentException if not matching
Types::assert($value, Types::TYPE_STRING);
Types::assert($value, [Types::TYPE_STRING, Types::TYPE_INT]);

// enforce() is an alias for assert() (backward compatibility)
Types::enforce($value, Types::TYPE_STRING);

// Assert not null with type check
$user = Types::assertNotNull($maybeUser, User::class);
// $user is guaranteed to be non-null User instance

// Custom error messages
Types::assert($value, Types::TYPE_INT, 'ID must be an integer');
```

### Advanced Type Detection

```php
use Bermuda\Stdlib\Types;

// Get the actual type
$type = Types::getType($value); // returns 'string', 'int', 'array', etc.

// Get class name for objects
$type = Types::getType($object, Types::OBJECT_AS_CLASS); // returns 'App\User'

// Handle callables as objects
$type = Types::getType($callable, Types::CALLABLE_AS_OBJECT);
```

### Class and Interface Validation

```php
use Bermuda\Stdlib\Types;

// Check if string is a valid class name
Types::isClass('App\User');           // true if class exists
Types::isClass($value, User::class);   // true if $value === 'App\User' (case-insensitive)

// Check if string is a valid interface name
Types::isInterface('App\UserInterface'); // true if interface exists

// Check subclass relationships
Types::isSubclassOf('App\Admin', 'App\User'); // true if Admin extends User
Types::isSubclassOfAny('App\Admin', ['App\User', 'App\Manager']);
```

### Real-world Examples

```php
use Bermuda\Stdlib\Types;

class UserService
{
    public function createUser(mixed $data): User
    {
        Types::assert($data, Types::TYPE_ARRAY, 'User data must be an array');
        
        $id = Types::assertNotNull($data['id'] ?? null, Types::TYPE_INT);
        $email = Types::assertNotNull($data['email'] ?? null, Types::TYPE_STRING);
        
        return new User($id, $email);
    }
    
    public function processPayment(mixed $handler): void
    {
        Types::assert($handler, [
            PaymentInterface::class,
            Types::TYPE_CALLABLE
        ], 'Payment handler must implement PaymentInterface or be callable');
        
        // Process payment...
    }
}
```

## API Reference

### Type Constants

- `Types::TYPE_ARRAY` - Array type
- `Types::TYPE_OBJECT` - Object type
- `Types::TYPE_INT` - Integer type
- `Types::TYPE_BOOL` - Boolean type
- `Types::TYPE_STRING` - String type
- `Types::TYPE_RESOURCE` - Resource type
- `Types::TYPE_CALLABLE` - Callable type
- `Types::TYPE_FLOAT` - Float type
- `Types::TYPE_NULL` - Null type

### Flag Constants

- `Types::CALLABLE_AS_OBJECT` - Treat callable objects as objects
- `Types::OBJECT_AS_CLASS` - Return class name instead of 'object'

### Methods

#### `getType(mixed $value, int $flags = 0): string`
Determines the type of a variable.

#### `is(mixed $value, string $expectedType): bool`
Checks if the value matches the specified type.

#### `isAny(mixed $value, array $expectedTypes): bool`
Checks if the value matches any of the provided types.

#### `assert(mixed $value, string|array $expectedTypes, ?string $message = null): void`
Asserts that the value matches one of the allowed types.

#### `enforce(mixed $value, string|array $expectedTypes, ?string $message = null): void`
Alias for assert() method (backward compatibility).

#### `assertNotNull(mixed $value, string|array $expectedTypes, ?string $message = null): mixed`
Asserts that the value is not null and matches one of the allowed types.

#### `isClass(mixed $value, ?string $expectedClass = null): bool`
Checks if the value is a valid class name.

#### `isInterface(mixed $value, ?string $expectedInterface = null): bool`
Checks if the value is a valid interface name.

#### `isSubclassOf(mixed $value, string $parentClass): bool`
Checks if the value is a subclass of the specified class.

#### `isInstanceOf(mixed $value, string $className): bool`
Checks if the value is an instance of the specified class or interface.

## License

MIT License. See LICENSE file for details.
