<?php

namespace Tests\Weew\ErrorHandler\Handlers;

use PHPUnit_Framework_TestCase;
use Weew\ErrorHandler\Errors\IError;
use Weew\ErrorHandler\Errors\RecoverableError;
use Weew\ErrorHandler\Handlers\NativeErrorHandler;

class RecoverableErrorHandlerTest extends PHPUnit_Framework_TestCase {
    public function test_get_handler() {
        $callable = function(IError $error) {};
        $handler = new NativeErrorHandler($callable);
        $this->assertTrue($callable === $handler->getHandler());
    }

    public function test_handle() {
        $handler = new NativeErrorHandler(function(IError $error) {});
        $this->assertFalse(
            $handler->handle(new RecoverableError(null, null, null, null))
        );

        $handler = new NativeErrorHandler(function(IError $error) {
            return true;
        });
        $this->assertTrue(
            $handler->handle(new RecoverableError(null, null, null, null))
        );

        $handler = new NativeErrorHandler(function(IError $error) {
            return false;
        });
        $this->assertFalse(
            $handler->handle(new RecoverableError(null, null, null, null))
        );
    }
}
