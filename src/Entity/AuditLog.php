<?php

namespace DataDog\AuditBundle\Entity;

class AuditLog
{
    private ?string $id;

    private string $action;

    private string $tbl;

    private Association $source;

    private ?Association $target;

    private ?Association $blame;

    private ?array $diff;

    private \DateTimeInterface $loggedAt;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getTbl(): string
    {
        return $this->tbl;
    }

    public function getSource(): Association
    {
        return $this->source;
    }

    public function getTarget(): ?Association
    {
        return $this->target;
    }

    public function getBlame(): ?Association
    {
        return $this->blame;
    }

    public function getDiff(): ?array
    {
        return $this->diff;
    }

    public function getLoggedAt(): \DateTimeInterface
    {
        return $this->loggedAt;
    }
}
