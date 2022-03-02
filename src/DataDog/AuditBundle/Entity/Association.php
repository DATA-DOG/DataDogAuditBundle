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
    private ?int $id;

    /**
     * @ORM\Column(length=128)
     */
    private string $typ;

    /**
     * @ORM\Column(length=128)
     */
    private ?string $tbl;

    /**
     * @ORM\Column(nullable=true)
     */
    private ?string $label;

    /**
     * @ORM\Column
     */
    private string $fk;

    /**
     * @ORM\Column
     */
    private string $class;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTyp(): string
    {
        return $this->typ;
    }

    public function getTypLabel(): string
    {
        $words = \explode('.', $this->getTyp());

        return \implode(' ', \array_map('ucfirst', \explode('_', \end($words))));
    }

    public function getTbl(): ?string
    {
        return $this->tbl;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getFk(): string
    {
        return $this->fk;
    }

    public function getClass(): string
    {
        return $this->class;
    }
}
