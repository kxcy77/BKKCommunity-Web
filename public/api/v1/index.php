<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
require dirname(__DIR__, 3) . '/app/api.php';

try {
    api_dispatch();
} catch (Throwable $exception) {
    error_log('BKK API failure: ' . $exception->getMessage());
    api_error(500, 'The server could not complete that request.', 'server_error');
}
