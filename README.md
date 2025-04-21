# CheckType

A lightweight PHP library for determining and enforcing variable types.

Легковесная PHP-библиотека для определения и обеспечения типов переменных.

---

## Features / Возможности

- **Type Detection / Определение Типа**  
  Provides the `getType()` method to determine the type of any given variable, including returning class names when desired.

- **Type Enforcement / Обеспечение Типов**  
  Enforces allowed types using the `enforce()` method, throwing detailed error messages with caller information when expectations are not met.

- **Class & Interface Checks / Проверка Классов и Интерфейсов**  
  Validates class and interface names with `isClass()` and `isInterface()` methods, ensuring accurate type checks.

- **Flexible Configuration / Гибкая Настройка**  
  Uses bitmask flags such as `FLAG_OBJECT_AS_CLASS` and `FLAG_CALLABLE_AS_OBJECT` to customize behavior.

---

## Installation / Установка

This library requires **PHP 8.0** or higher.

Эта библиотека требует **PHP 8.0** или выше.

Install via Composer:

Установите через Composer:

```bash
composer require bermudaphp/checktype
```

## Usage / Использование
```php
use Bermuda\CheckType\Type;

$value = 42;

// Determine the type of the variable
echo Type::getType($value); // Outputs: int

// Enforce allowed types
Type::enforce(static fn() => 42, [Type::TYPE_CALLABLE, \Closure::class]);
```
