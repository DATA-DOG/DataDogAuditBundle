<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="projects")
 */
class Project
{
    /**
     * @ORM\GeneratedValue
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=3)
     */
    private $code;

    /**
     * @ORM\Column
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     */
    private $hoursSpent = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled = true;

    /**
     * @ORM\Column(type="integer")
     */
    private $deadline = 0;

    /**
     * @ORM\ManyToOne(targetEntity="Language")
     * @ORM\JoinColumn(nullable=false)
     */
    private $language;

    /**
     * @ORM\ManyToMany(targetEntity="Tag", cascade={"persist", "remove"})
     * @ORM\JoinTable(name="projects_tags")
     */
    private $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setHoursSpent($hoursSpent)
    {
        $this->hoursSpent = $hoursSpent;
        return $this;
    }

    public function getHoursSpent()
    {
        return $this->hoursSpent;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
        return $this;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setDeadline($deadline)
    {
        $this->deadline = $deadline;
        return $this;
    }

    public function getDeadline()
    {
        return $this->deadline;
    }

    public function isOverDeadline()
    {
        return $this->hoursSpent > $this->deadline;
    }

    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getEnabled()
    {
        return $this->enabled;
    }

    public function addTag(Tag $tag)
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(Tag $tag)
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
        }
        return $this;
    }

    public function getTags()
    {
        return $this->tags;
    }
}
