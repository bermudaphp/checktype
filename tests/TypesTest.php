<?php

declare(strict_types=1);

namespace Bermuda\Stdlib\Tests;

use Countable;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Bermuda\Stdlib\Types;
use InvalidArgumentException;
use stdClass;

class TypesTest extends TestCase
{
    public function testGetTypeBasicTypes(): void
    {
        $this->assertSame(Types::TYPE_STRING, Types::getType('hello'));
        $this->assertSame(Types::TYPE_INT, Types::getType(42));
        $this->assertSame(Types::TYPE_FLOAT, Types::getType(3.14));
        $this->assertSame(Types::TYPE_BOOL, Types::getType(true));
        $this->assertSame(Types::TYPE_ARRAY, Types::getType([]));
        $this->assertSame(Types::TYPE_NULL, Types::getType(null));
        $this->assertSame(Types::TYPE_OBJECT, Types::getType(new stdClass()));
    }

    public function testGetTypeWithObjectAsClassFlag(): void
    {
        $object = new DateTime();
        $this->assertSame(
            DateTime::class,
            Types::getType($object, Types::OBJECT_AS_CLASS)
        );
    }

    public function testGetTypeCallable(): void
    {
        $this->assertSame(Types::TYPE_CALLABLE, Types::getType('strlen'));
        $this->assertSame(Types::TYPE_CALLABLE, Types::getType(fn() => true));

        // Callable object without flag
        $callable = new class {
            public function __invoke() {}
        };
        $this->assertSame(Types::TYPE_CALLABLE, Types::getType($callable));

        // Callable object with flag
        $this->assertSame(
            Types::TYPE_OBJECT,
            Types::getType($callable, Types::CALLABLE_AS_OBJECT)
        );
    }

    public function testIs(): void
    {
        $this->assertTrue(Types::is('hello', Types::TYPE_STRING));
        $this->assertTrue(Types::is(42, Types::TYPE_INT));
        $this->assertTrue(Types::is(new DateTime(), DateTime::class));

        $this->assertFalse(Types::is('hello', Types::TYPE_INT));
        $this->assertFalse(Types::is(42, Types::TYPE_STRING));
    }

    public function testIsAny(): void
    {
        $this->assertTrue(Types::isAny(42, [Types::TYPE_STRING, Types::TYPE_INT]));
        $this->assertTrue(Types::isAny('hello', [Types::TYPE_STRING, Types::TYPE_INT]));
        $this->assertFalse(Types::isAny(3.14, [Types::TYPE_STRING, Types::TYPE_INT]));
    }

    public function testIsClass(): void
    {
        $this->assertTrue(Types::isClass(DateTime::class));
        $this->assertTrue(Types::isClass('DateTime', DateTime::class));
        $this->assertFalse(Types::isClass('NonExistentClass'));
        $this->assertFalse(Types::isClass(123));
    }

    public function testIsInterface(): void
    {
        $this->assertTrue(Types::isInterface(Countable::class));
        $this->assertFalse(Types::isInterface(DateTime::class));
        $this->assertFalse(Types::isInterface('NonExistentInterface'));
    }

    public function testIsSubclassOf(): void
    {
        $parent = new TestParent;
        $child = new TestChild;

        $this->assertTrue(Types::isSubclassOf(get_class($child), get_class($parent)));
        $this->assertFalse(Types::isSubclassOf(get_class($parent), get_class($child)));
        $this->assertFalse(Types::isSubclassOf(123, get_class($parent)));
    }

    public function testIsInstanceOf(): void
    {
        $date = new DateTime();
        $this->assertTrue(Types::isInstanceOf($date, DateTime::class));
        $this->assertTrue(Types::isInstanceOf($date, DateTimeInterface::class));
        $this->assertFalse(Types::isInstanceOf($date, stdClass::class));
    }

    public function testAssertSuccess(): void
    {
        // Should not throw
        Types::assert('hello', Types::TYPE_STRING);
        Types::assert(42, [Types::TYPE_INT, Types::TYPE_FLOAT]);

        $this->assertTrue(true); // Assertion passed
    }

    public function testEnforceAsAlias(): void
    {
        Types::enforce('hello', Types::TYPE_STRING);
        Types::enforce(42, [Types::TYPE_INT, Types::TYPE_FLOAT]);

        $this->assertTrue(true); // Assertion passed

        $this->expectException(InvalidArgumentException::class);
        Types::enforce('hello', Types::TYPE_INT);
    }

    public function testAssertFailure(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type mismatch');

        Types::assert('hello', Types::TYPE_INT);
    }

    public function testAssertCustomMessage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Custom error message');

        Types::assert('hello', Types::TYPE_INT, 'Custom error message');
    }

    public function testAssertNotNull(): void
    {
        $value = Types::assertNotNull('hello', Types::TYPE_STRING);
        $this->assertSame('hello', $value);

        $this->expectException(InvalidArgumentException::class);
        Types::assertNotNull(null, Types::TYPE_STRING);
    }

    public function testIsInstanceOfAny(): void
    {
        $this->assertTrue(Types::isInstanceOfAny(new TestInstnceofA, [A::class, B::class]));
        $this->assertFalse(Types::isInstanceOfAny(new stdClass, [A::class, B::class]));
    }

    public function testIsInstanceOfAll(): void
    {
        $this->assertTrue(Types::isInstanceOfAll(new TestInstanceofAll, [A::class, B::class]));
        $this->assertFalse(Types::isInstanceOfAll(new TestInstnceofA, [A::class, B::class]));
    }
}

class TestParent {}
class TestChild extends TestParent {}
interface A {}
interface B {}
class TestInstanceofAll implements A, B {}
class TestInstnceofA implements A {}
