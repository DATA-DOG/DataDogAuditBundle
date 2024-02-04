<?php

namespace DataDog\AuditBundle\Entity;

class Association
{
    private ?string $id;

    private string $typ;

    private ?string $tbl;

    private ?string $label;

    private int $fk;

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
