<?php

namespace DataDog\AuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="audit_logs")
 */
class AuditLog
{
    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */

    /**
     * @ORM\Column(length=12)
     */

    /**
     * @ORM\Column(length=128)
     */

    /**
     * @ORM\OneToOne(targetEntity="Association")
     * @ORM\JoinColumn(nullable=false)
     */

    /**
     * @ORM\OneToOne(targetEntity="Association")
     */

    /**
     * @ORM\OneToOne(targetEntity="Association")
     */

    /**
     * @ORM\Column(type="json", nullable=true)
     */

    /**
     * @ORM\Column(type="datetime")
     */

    public function getId()
    private ?int $id;

    private string $action;

    private string $tbl;

    private Association $source;

    private ?Association $target;

    private ?Association $blame;

    private ?array $diff;

    private \DateTimeInterface $loggedAt;

    public function getId(): ?int
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
