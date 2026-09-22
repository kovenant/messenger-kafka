<?php

declare(strict_types=1);

use Symfony\Component\ErrorHandler\DebugClassLoader;

require dirname(__DIR__) . '/vendor/autoload.php';

// Check upcoming interface requirements as classes are loaded, including get($fetchSize).
DebugClassLoader::enable();
