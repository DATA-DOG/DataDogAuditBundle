<?php

declare(strict_types=1);

namespace DataDog\AuditBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AuditLogDeleteOldLogsCommand extends Command
{
    public const DEFAULT_RETENTION_PERIOD = 'P3M';

    public function __construct(
        protected Connection $connection
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setName('audit-logs:delete-old-logs')
            ->setDescription('Remove old records from the audit logs')
            ->addOption('retention-period', null, InputOption::VALUE_OPTIONAL, 'The retention interval, format: https://www.php.net/manual/en/dateinterval.construct.php', self::DEFAULT_RETENTION_PERIOD)
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = (new \DateTime())->sub(new \DateInterval($input->getOption('retention-period')));
        $formattedDate = $date->format('Y-m-d H:i:s');

        $output->writeln(sprintf('<info>Delete all records before %s</info>', $formattedDate));

        $result = $this->connection->executeQuery(
            'SELECT * FROM audit_logs WHERE logged_at < ? ORDER BY logged_at DESC LIMIT 1',
            [$formattedDate]
        )
            ->fetchAssociative();

        if ($result === false) {
            $output->writeln(sprintf('<info>No records to delete</info>'));

            return 0;
        }

        $auditLogStartRecordId = $result['id'];
        $auditAssociativeStartRecordId = max($result['source_id'], $result['target_id'], $result['blame_id']);

        $count = $this->deleteFromAuditLogs($auditLogStartRecordId);
        $output->writeln(sprintf('<info> %s records from audit_logs deleted!</info>', $count));

        $count = $this->deleteFromAuditAssociations($auditAssociativeStartRecordId);
        $output->writeln(sprintf('<info> %s records from audit_associations deleted!</info>', $count));

        return 0;
    }

    private function deleteFromAuditLogs(int $startRecordId): int
    {
        $allRecords = 0;
        $this->connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');

        $sql = 'DELETE LOW_PRIORITY FROM audit_logs WHERE id <= ? ORDER BY id LIMIT 10000';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $startRecordId, ParameterType::INTEGER);
        do {
            $startTime = microtime(true);
            $deletedRows = $stmt->executeStatement();
            $allRecords += $deletedRows;
            echo round((microtime(true) - $startTime), 3) . "s ";
            sleep(1);
        } while ($deletedRows > 0);

        $this->connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');

        return $allRecords;
    }

    private function deleteFromAuditAssociations(int $startRecordId): int
    {
        $allRecords = 0;
        $this->connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');

        $sql = 'DELETE LOW_PRIORITY FROM audit_associations WHERE id <= ? ORDER BY id LIMIT 10000';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $startRecordId, ParameterType::INTEGER);
        do {
            $startTime = microtime(true);
            $deletedRows = $stmt->executeStatement();
            $allRecords += $deletedRows;
            echo round((microtime(true) - $startTime), 3) . "s ";
            sleep(1);
        } while ($deletedRows > 0);

        $this->connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');

        return $allRecords;
    }
}
