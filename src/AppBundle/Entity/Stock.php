<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Stock
 *
 * @ORM\Table(name="stock",uniqueConstraints={@ORM\UniqueConstraint(name="portfolio_id_symbol",columns={"portfolio_id","symbol"})})
 * @UniqueEntity(fields={"portfolioId","symbol"}, message="Stock symbol already exists in this portfolio")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\StockRepository")
 */
class Stock
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="portfolio_id", type="integer")
     */
    private $portfolioId;

    /**
     * @var string
     *
     * @ORM\Column(name="symbol", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     */
    private $symbol;

    /**
     * @ORM\ManyToOne(targetEntity="Portfolio", inversedBy="stocks")
     * @ORM\JoinColumn(name="portfolio_id", referencedColumnName="id")
     */
    private $portfolio;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set portfolioId
     *
     * @param integer $portfolioId
     *
     * @return Stock
     */
    public function setPortfolioId($portfolioId)
    {
        $this->portfolioId = $portfolioId;

        return $this;
    }

    /**
     * Get portfolioId
     *
     * @return int
     */
    public function getPortfolioId()
    {
        return $this->portfolioId;
    }

    /**
     * Set symbol
     *
     * @param string $symbol
     *
     * @return Stock
     */
    public function setSymbol($symbol)
    {
        $this->symbol = $symbol;

        return $this;
    }

    /**
     * Get symbol
     *
     * @return string
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * Set portfolio
     *
     * @param \AppBundle\Entity\Portfolio $portfolio
     *
     * @return Stock
     */
    public function setPortfolio(\AppBundle\Entity\Portfolio $portfolio = null)
    {
        $this->portfolio = $portfolio;

        return $this;
    }

    /**
     * Get portfolio
     *
     * @return \AppBundle\Entity\Portfolio
     */
    public function getPortfolio()
    {
        return $this->portfolio;
    }
}
