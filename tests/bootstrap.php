<?php
declare(strict_types=1);

use Symfony\Component\ErrorHandler\ErrorHandler;

require dirname(__DIR__) . '/vendor/autoload.php';

// This is a workaround for avoiding PHPUnit 11 warning: 'Test code or tested code did not remove its own exception handlers'
// Read more: https://github.com/symfony/symfony/issues/53812#issuecomment-1962740145
set_exception_handler([new ErrorHandler(), 'handleException']);
