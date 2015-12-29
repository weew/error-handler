<?php

namespace Weew\ErrorHandler;

use Exception;
use Weew\ErrorHandler\Errors\IError;
use Weew\ErrorHandler\Exceptions\InvalidHandlerType;
use Weew\ErrorHandler\Handlers\ExceptionHandler;
use Weew\ErrorHandler\Handlers\NativeErrorHandler;
use Weew\ErrorHandler\Handlers\IExceptionHandler;
use Weew\ErrorHandler\Handlers\INativeErrorHandler;

class ErrorHandler implements IErrorHandler {
    /**
     * @var ErrorConverter
     */
    protected $errorConverter;

    /**
     * @var IExceptionHandler[]
     */
    protected $exceptionHandlers = [];

    /**
     * @var INativeErrorHandler[]
     */
    protected $recoverableErrorHandlers = [];

    /**
     * @var INativeErrorHandler[]
     */
    protected $fatalErrorHandlers = [];

    /**
     * @var bool
     */
    protected $isExceptionHandlingEnabled = false;

    /**
     * @var bool
     */
    protected $isRecoverableErrorHandlingEnabled = false;

    /**
     * @var bool
     */
    protected $isFatalErrorHandlingEnabled = false;

    /**
     * @var bool
     */
    protected $isConvertingErrorsToExceptions = false;

    /**
     * ErrorHandler constructor.
     *
     * @param bool $convertErrorsToExceptions
     */
    public function __construct($convertErrorsToExceptions = false) {
        $this->errorConverter = $this->createErrorConverter();
        $this->convertErrorsToExceptions($convertErrorsToExceptions);
    }

    /**
     * Enable exception, error and fatal error handling.
     */
    public function enable() {
        $this->enableExceptionHandling();
        $this->enableRecoverableErrorHandling();
        $this->enableFatalErrorHandling();
    }

    /**
     * Enable exception handling.
     */
    public function enableExceptionHandling() {
        if ($this->isExceptionHandlingEnabled()) {
            return;
        }

        set_exception_handler([$this, 'handleException']);
        $this->isExceptionHandlingEnabled = true;
    }

    /**
     * Enable regular error handling.
     */
    public function enableRecoverableErrorHandling() {
        if ($this->isRecoverableErrorHandlingEnabled()) {
            return;
        }

        set_error_handler(function ($number, $string, $file, $line) {
            return $this->errorConverter->createRecoverableErrorAndCallHandler(
                $this, $number, $string, $file, $line
            );
        });
        $this->isRecoverableErrorHandlingEnabled = true;
    }

    /**
     * Enable fatal/non-recoverable error handling.
     */
    public function enableFatalErrorHandling() {
        if ($this->isFatalErrorHandlingEnabled()) {
            return;
        }

        register_shutdown_function(function () {
            return $this->errorConverter
                ->extractFatalErrorAndCallHandler($this);
        });
        $this->isFatalErrorHandlingEnabled = true;
    }

    /**
     * @param callable|IExceptionHandler $handler
     *
     * @throws InvalidHandlerType
     */
    public function addExceptionHandler($handler) {
        if ( ! $handler instanceof IExceptionHandler && ! is_callable($handler)) {
            throw new InvalidHandlerType(
                s('Exception handler must be a callable or an instance of %s.',
                    IExceptionHandler::class)
            );
        }

        if (is_callable($handler)) {
            $handler = $this->createExceptionHandler($handler);
        }

        $this->exceptionHandlers[] = $handler;
    }

    /**
     * @param callable|INativeErrorHandler $handler
     *
     * @throws InvalidHandlerType
     */
    public function addRecoverableErrorHandler($handler) {
        if ( ! $handler instanceof INativeErrorHandler && ! is_callable($handler)) {
            throw new InvalidHandlerType(
                s('Recoverable error handler must be a callable or an instance of %s.',
                    INativeErrorHandler::class)
            );
        }

        if (is_callable($handler)) {
            $handler = $this->createRecoverableErrorHandler($handler);
        }

        $this->recoverableErrorHandlers[] = $handler;
    }

    /**
     * @param callable|INativeErrorHandler $handler
     *
     * @throws InvalidHandlerType
     */
    public function addFatalErrorHandler($handler) {
        if ( ! $handler instanceof INativeErrorHandler && ! is_callable($handler)) {
            throw new InvalidHandlerType(
                s('Fatal error handler must be a callable or an instance of %s.',
                    INativeErrorHandler::class)
            );
        }

        if (is_callable($handler)) {
            $handler = $this->createFatalErrorHandler($handler);
        }

        $this->fatalErrorHandlers[] = $handler;
    }

    /**
     * @return bool
     */
    public function isExceptionHandlingEnabled() {
        return $this->isExceptionHandlingEnabled;
    }

    /**
     * @return bool
     */
    public function isRecoverableErrorHandlingEnabled() {
        return $this->isRecoverableErrorHandlingEnabled;
    }

    /**
     * @return bool
     */
    public function isFatalErrorHandlingEnabled() {
        return $this->isFatalErrorHandlingEnabled;
    }

    /**
     * @return bool
     */
    public function isConvertingErrorsToExceptions() {
        return $this->isConvertingErrorsToExceptions;
    }

    /**
     * @param Exception $ex
     *
     * @throws Exception
     */
    public function handleException(Exception $ex) {
        foreach ($this->getExceptionHandlers() as $handler) {
            if ( ! $handler->supports($ex)) {
                continue;
            }

            $handled = $handler->handle($ex);

            if ($handled === true) {
                return;
            }
        }

        throw $ex;
    }

    /**
     * @param IError $error
     *
     * @return bool|void
     */
    public function handleRecoverableError(IError $error) {
        if ($this->isConvertingErrorsToExceptions()) {
            return $this->errorConverter
                ->convertErrorToExceptionAndCallHandler($this, $error);
        }

        foreach ($this->getRecoverableErrorHandlers() as $handler) {
            $handled = $handler->handle($error);

            if ($handled === true) {
                return;
            }
        }

        return false;
    }

    /**
     * @param IError $error
     *
     * @return bool|void
     */
    public function handleFatalError(IError $error) {
        ob_get_clean();

        if ($this->isConvertingErrorsToExceptions()) {
            return $this->errorConverter
                ->convertErrorToExceptionAndCallHandler($this, $error);
        }

        foreach ($this->getFatalErrorHandlers() as $handler) {
            $handled = $handler->handle($error);

            if ($handled === true) {
                return;
            }
        }

        return false;
    }

    /**
     * @param bool $convertErrorsToExceptions
     */
    public function convertErrorsToExceptions($convertErrorsToExceptions) {
        $this->isConvertingErrorsToExceptions = $convertErrorsToExceptions;
    }

    /**
     * @return IExceptionHandler[]
     */
    public function getExceptionHandlers() {
        return $this->exceptionHandlers;
    }

    /**
     * @return INativeErrorHandler[]
     */
    public function getRecoverableErrorHandlers() {
        return $this->recoverableErrorHandlers;
    }

    /**
     * @return INativeErrorHandler[]
     */
    public function getFatalErrorHandlers() {
        return $this->fatalErrorHandlers;
    }

    /**
     * @param callable $handler
     *
     * @return IExceptionHandler
     */
    protected function createExceptionHandler(callable $handler) {
        return new ExceptionHandler($handler);
    }

    /**
     * @param callable $handler
     *
     * @return INativeErrorHandler
     */
    protected function createRecoverableErrorHandler(callable $handler) {
        return new NativeErrorHandler($handler);
    }

    /**
     * @param callable $handler
     *
     * @return INativeErrorHandler
     */
    protected function createFatalErrorHandler(callable $handler) {
        return new NativeErrorHandler($handler);
    }

    /**
     * @return ErrorConverter
     */
    protected function createErrorConverter() {
        return new ErrorConverter();
    }
}
