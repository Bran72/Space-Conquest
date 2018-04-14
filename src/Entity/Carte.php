<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CarteRepository")
 */
class Carte
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $carte_nom;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $carte_points;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $carte_img;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Objectifs")
     */
    private $objectif;

    /**
     * @return mixed
     */
    public function getObjectif()
    {
        return $this->objectif;
    }
    /**
     * @param mixed $objectif
     */
    public function setObjectif($objectif): void
    {
        $this->objectif = $objectif;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getNom(): string
    {
        return $this->carte_nom;
    }

    /**
     * @param string $carte_nom
     */
    public function setNom(string $carte_nom): void
    {
        $this->carte_nom = $carte_nom;
    }

    /**
     * @return int
     */
    public function getValeur(): int
    {
        return $this->carte_points;
    }

    /**
     * @param int $carte_points
     */
    public function setValeur(int $carte_points): void
    {
        $this->carte_points = $carte_points;
    }

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->carte_img;
    }

    /**
     * @param string $carte_img
     */
    public function setImage(string $carte_img): void
    {
        $this->carte_img = $carte_img;
    }
}