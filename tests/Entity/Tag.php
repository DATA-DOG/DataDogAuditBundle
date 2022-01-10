<?php

declare(strict_types=1);

namespace DataDog\AuditBundle\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table()
 */
#[ORM\Entity]
#[ORM\Table]
class Tag
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    #[ORM\Column]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private int $id;

    /**
     * @ORM\Column(type="string")
     */
    #[ORM\Column(nullable: true)]
    private string $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
