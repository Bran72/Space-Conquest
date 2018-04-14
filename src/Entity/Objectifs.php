<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ObjectifsRepository")
 */
class Objectifs
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    // add your own fields
    /**
     * @ORM\Column(type="text")
     */
    private $obj_nom;

    /**
     * @ORM\Column(type="text")
     */
    private $obj_img;

    /**
     * @ORM\Column(type="integer", length=1)
     */
    private $obj_points;

    /**
     * @ORM\Column(type="integer", length=10)
     */
    private $obj_ordre;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getNom()
    {
        return $this->obj_nom;
    }
    /**
     * @param mixed $obj_nom
     */
    public function setNom($obj_nom): void
    {
        $this->obj_nom = $obj_nom;
    }

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->obj_img;
    }

    /**
     * @param string $obj_img
     */
    public function setImage(string $obj_img): void
    {
        $this->obj_img = $obj_img;
    }
}