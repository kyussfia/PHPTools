<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Table(name="exchange_rate")
 * @ORM\Entity(repositoryClass="Slametrix\Doctrine\ORM\EntityRepository")
 */
class ExchangeRate
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="valid_at", type="date", nullable=false)
     */
    protected $validAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;

    /**
     * @var float
     *
     * @ORM\Column(name="eur_in_huf", type="decimal", precision=10, scale=5, nullable=false)
     */
    private $eurInHuf;

    /**
     * @var float
     *
     * @ORM\Column(name="usd_in_huf", type="decimal", precision=10, scale=5, nullable=false)
     */
    private $usdInHuf;

    public function __construct(
        \DateTime $validAt,
        \DateTime $createdAt,
        float $eurInHuf,
        float $usdInHuf
    )
    {
        $this->validAt = $validAt;
        $this->createdAt = $createdAt;
        $this->eurInHuf = $eurInHuf;
        $this->usdInHuf = $usdInHuf;
    }

    /**
     * @return \DateTime
     */
    public function getValidAt(): \DateTime
    {
        return $this->validAt;
    }

    /**
     * @return float
     */
    public function getEurInHuf(): float
    {
        return $this->eurInHuf;
    }

    /**
     * @return float
     */
    public function getUsdInHuf(): float
    {
        return $this->usdInHuf;
    }
}