<?php

namespace DataDog\AuditBundle\DBAL\Middleware;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsMiddleware;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

#[AsMiddleware(connections: ['default'])]
class AuditFlushMiddleware implements Middleware
{
    /**
     * @var array<object, string>
     */
    public ?array $flushHandler = null;

    public function wrap(Driver $driver): Driver
    {
        return new DriverMiddleware($driver, $this);
    }
}
