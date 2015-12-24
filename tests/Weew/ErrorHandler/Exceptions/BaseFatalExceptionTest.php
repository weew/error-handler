<?php

namespace Tests\Weew\ErrorHandler\Exceptions;

use PHPUnit_Framework_TestCase;
use Weew\ErrorHandler\Exceptions\CoreErrorException;

class BaseFatalExceptionTest extends PHPUnit_Framework_TestCase {
    public function test_getters() {
        $ex = new CoreErrorException('foo', 'bar', 'yolo', 'swag');
        $this->assertEquals('foo', $ex->getErrorType());
        $this->assertEquals('bar', $ex->getErrorMessage());
        $this->assertEquals('yolo', $ex->getErrorFile());
        $this->assertEquals('swag', $ex->getErrorLine());
    }

    public function test_is_recoverable() {
        $ex = new CoreErrorException('foo', 'bar', 'yolo', 'swag');
        $this->assertFalse($ex->isRecoverable());
    }
}
