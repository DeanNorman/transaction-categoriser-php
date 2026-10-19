<?php
/**
 * Front controller. nginx hands every request that is not a static file here.
 * It does three things: build a Request from the globals, ask the Kernel for a
 * Response, and send it. Everything else lives in src/.
 */
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Http\Kernel;
use App\Http\Request;

Kernel::fromEnv()->handle(Request::fromGlobals())->send();
