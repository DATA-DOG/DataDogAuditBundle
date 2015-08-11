<?php

namespace DataDog\AuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="audit_associations")
 */
class Association
{
    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(length=128)
     */
    private $typ;

    /**
     * @ORM\Column(length=128)
     */
    private $tbl;

    /**
     * @ORM\Column(nullable=true)
     */
    private $label;

    /**
     * @ORM\Column
     */
    private $fk;

    /**
     * @ORM\Column
     */
    private $class;

    public function getId()
    {
        return $this->id;
    }

    public function getTyp()
    {
        return $this->typ;
    }

    public function getTypLabel()
    {
        $words = explode('.', $this->getTyp());
        return implode(' ', array_map('ucfirst', explode('_', end($words))));
    }

    public function getTbl()
    {
        return $this->tbl;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function getFk()
    {
        return $this->fk;
    }

    public function getClass()
    {
        return $this->class;
    }
}
