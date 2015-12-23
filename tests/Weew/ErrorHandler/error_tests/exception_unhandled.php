<?php

use Tests\Weew\ErrorHandler\Stubs\FooException;
use Weew\ErrorHandler\ErrorHandler;

require __DIR__ . '/../../../../vendor/autoload.php';

$errorHandler = new ErrorHandler();
$errorHandler->enableExceptionHandling();

throw new FooException();
