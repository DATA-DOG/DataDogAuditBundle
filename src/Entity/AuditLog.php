<?php

namespace DataDog\AuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_logs')]
#[ORM\Index(columns: ['logged_at'])]
class AuditLog
{
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?string $id;

    #[ORM\Column(length: 12)]
    private string $action;

    #[ORM\Column(length: 128)]
    private string $tbl;

    #[ORM\OneToOne(targetEntity: Association::class)]
    #[ORM\JoinColumn(unique: true, nullable: false)]
    private Association $source;

    #[ORM\OneToOne(targetEntity: Association::class)]
    #[ORM\JoinColumn(unique: true)]
    private ?Association $target;

    #[ORM\OneToOne(targetEntity: Association::class)]
    #[ORM\JoinColumn(unique: true)]
    private ?Association $blame;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $diff;

    #[ORM\Column(name: 'logged_at', type: 'datetime')]
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
