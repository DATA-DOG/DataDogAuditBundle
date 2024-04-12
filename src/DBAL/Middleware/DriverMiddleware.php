<?php

namespace DataDog\AuditBundle\DBAL\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

class DriverMiddleware extends AbstractDriverMiddleware
{
    public function __construct(
        Driver $driver,
        private readonly AuditFlushMiddleware $auditFlushMiddleware
    )
    {
        parent::__construct($driver);
    }

    public function connect(array $params): Connection
    {
        return new ConnectionMiddleware(parent::connect($params), $this->auditFlushMiddleware);
    }
}
