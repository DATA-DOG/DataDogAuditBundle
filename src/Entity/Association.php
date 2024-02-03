<?php

namespace DataDog\AuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_associations')]
#[ORM\Index(columns: ['fk'])]
class Association
{
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?string $id;

    #[ORM\Column(length: 128)]
    private string $typ;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $tbl;

    #[ORM\Column(nullable: true)]
    private ?string $label;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $fk;

    #[ORM\Column]
    private string $class;

    public function getId(): ?string
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

    public function getFk(): int
    {
        return $this->fk;
    }

    public function getClass(): string
    {
        return $this->class;
    }
}
