<?php

namespace DataDog\AuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="audit_logs", indexes={
 *     @ORM\Index(name="idx_audit_logs_logged_at", columns={"logged_at"}),
 *     @ORM\Index(name="idx_audit_logs_tbl", columns={"tbl"})
 * })
 */
class AuditLog
{
    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(length=12)
     */
    private $action;

    /**
     * @ORM\Column(length=128)
     */
    private $tbl;

    /**
     * @ORM\OneToOne(targetEntity="Association")
     * @ORM\JoinColumn(nullable=false)
     */
    private $source;

    /**
     * @ORM\OneToOne(targetEntity="Association")
     */
    private $target;

    /**
     * @ORM\OneToOne(targetEntity="Association")
     */
    private $blame;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $diff;

    /**
     * @ORM\Column(type="datetime")
     */
    private $loggedAt;

    public function getId()
    {
        return $this->id;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getTbl()
    {
        return $this->tbl;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function getBlame()
    {
        return $this->blame;
    }

    public function getDiff()
    {
        return $this->diff;
    }

    public function getLoggedAt()
    {
        return $this->loggedAt;
    }
}
