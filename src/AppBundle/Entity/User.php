<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 * @UniqueEntity(fields="username", message="Username already taken")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank()
     */
    private $username;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=4096)
     */
    private $plainPassword;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $password;

    /**
     * @ORM\OneToMany(targetEntity="Portfolio", mappedBy="user")
     */
    private $portfolios;

    // other properties and methods

    public function __construct()
    {
        $this->portfolios = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getSalt()
    {
        // The bcrypt algorithm doesn't require a separate salt.
        return null;
    }

    public function getRoles()
    {
        return array('ROLE_USER');
    }

    public function eraseCredentials()
    {
        return null;
    }

    /**
     * Add portfolio
     *
     * @param \AppBundle\Entity\Portfolio $portfolio
     *
     * @return User
     */
    public function addPortfolio(\AppBundle\Entity\Portfolio $portfolio)
    {
        $this->portfolios[] = $portfolio;

        return $this;
    }

    /**
     * Remove portfolio
     *
     * @param \AppBundle\Entity\Portfolio $portfolio
     */
    public function removePortfolio(\AppBundle\Entity\Portfolio $portfolio)
    {
        $this->portfolios->removeElement($portfolio);
    }

    /**
     * Get portfolios
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPortfolios()
    {
        return $this->portfolios;
    }
}
