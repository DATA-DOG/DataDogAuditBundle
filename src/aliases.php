<?php

declare(strict_types=1);

namespace DataDog\AuditBundle;

use DataDog\AuditBundle\DBAL\Middleware\ConnectionMiddleware;
use DataDog\AuditBundle\DBAL\Middleware\ConnectionMiddlewareForV3;
use DataDog\AuditBundle\DBAL\Middleware\ConnectionMiddlewareForV4;
use Doctrine\DBAL\VersionAwarePlatformDriver;

if (!class_exists(ConnectionMiddleware::class)) {
    if (!interface_exists(VersionAwarePlatformDriver::class)) {
        class_alias(ConnectionMiddlewareForV4::class, ConnectionMiddleware::class);
    } else {
        class_alias(ConnectionMiddlewareForV3::class, ConnectionMiddleware::class);
    }
}
