<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(length=32, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(length=64, nullable=true)
     */
    private $firstname;

    /**
     * @ORM\Column(length=64, nullable=true)
     */
    private $lastname;

    /**
     * @ORM\Column(length=64, nullable=true)
     */
    private $password;

    public function getId()
    {
        return $this->id;
    }

    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getRoles()
    {
        return ['ROLE_USER'];
    }

    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getSalt()
    {
        return 'secrettdhgdhdhdrdrhdyhgrsdgrsdgdgdgddrg';
    }

    public function eraseCredentials()
    {
    }

    public function __toString()
    {
        return $this->firstname ? trim($this->firstname . ' ' . $this->lastname) : $this->username;
    }
}
