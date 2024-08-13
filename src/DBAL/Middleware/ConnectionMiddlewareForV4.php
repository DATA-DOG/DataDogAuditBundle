<?php

namespace DataDog\AuditBundle\DBAL\Middleware;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;

class ConnectionMiddlewareForV4 extends AbstractConnectionMiddleware
{
    public function __construct(
        Connection $wrappedConnection,
        private readonly AuditFlushMiddleware $auditFlushMiddleware
    )
    {
        parent::__construct($wrappedConnection);
    }

    /**
     * Override of the commit method
     *
     * @throws Exception
     */
    public function commit(): void
    {
        // Call the flusher callback if it's available.
        if ($this->auditFlushMiddleware->flushHandler !== null) {
            ($this->auditFlushMiddleware->flushHandler)();
            $this->auditFlushMiddleware->flushHandler = null;
        }

        // Call the parent's commit method
        parent::commit();
    }
}
