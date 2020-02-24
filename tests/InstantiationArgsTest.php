<?php 

use Aivec\WordPress\Routing\Router;

class InstantiationArgsTest extends Codeception\Test\Unit
{
    public function testRoutesNamespaceArgArrayNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('routes_namespace must be a string');
        new Router([1], 'key', 'name');
    }

    public function testRoutesNamespaceArgNumberNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('routes_namespace must be a string');
        new Router(1, 'key', 'name');
    }

    public function testRoutesNamespaceArgBoolNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('routes_namespace must be a string');
        new Router(true, 'key', 'name');
    }

    public function testRoutesNamespaceArgEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('routes_namespace must not be empty');
        new Router('', 'key', 'name');
    }
    public function testNonceKeyArgArrayNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nonce_key must be a string');
        new Router('/test', [1], 'name');
    }

    public function testNonceKeyArgNumberNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nonce_key must be a string');
        new Router('/test', 1, 'name');
    }

    public function testNonceKeyArgBoolNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nonce_key must be a string');
        new Router('/test', true, 'name');
    }

    public function testNonceKeyArgEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nonce_key must not be empty');
        new Router('/test', '', 'name');
    }

    public function testNonceNameArgArrayNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nonce_name must be a string');
        new Router('/test', 'key', [1]);
    }

    public function testNonceNameArgNumberNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nonce_name must be a string');
        new Router('/test', 'key', 1);
    }

    public function testNonceNameArgBoolNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nonce_name must be a string');
        new Router('/test', 'key', true);
    }

    public function testNonceNameArgEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nonce_name must not be empty');
        new Router('/test', 'key', '');
    }
}
