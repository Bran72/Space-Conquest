<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PartieRepository")
 */
class Partie
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $main_j1;

    /**
     * @ORM\Column(type="text")
     */
    private $main_j2;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $id_j1;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $id_j2;

    /**
     * @ORM\Column(type="text")
     */
    private $partie_tour;

    /**
     * @ORM\Column(type="text")
     */
    private $partie_finie;

    /**
     * @ORM\Column(type="text")
     */
    private $partie_manche;

    /**
     * @ORM\Column(type="text")
     */
    private $partie_pioche;

    /**
     * @ORM\Column(type="text")
     */
    private $partie_objectifs;

    /**
     * @ORM\Column(type="text")
     */
    private $action_j1;

    /**
     * @ORM\Column(type="text")
     */
    private $action_j2;

    /**
     * @ORM\Column(type="text")
     */
    private $carte_jetee;

    /**
     * @ORM\Column(type="integer", length=2)
     */
    private $score_j1;

    /**
     * @ORM\Column(type="integer", length=2)
     */
    private $score_j2;

    /**
     * @ORM\Column(type="text")
     */
    private $terrain_j1;

    /**
     * @ORM\Column(type="text")
     */
    private $terrain_j2;

    /**
     * @ORM\Column(type="text")
     */
    private $finiJ1; //stock les points pour chaque carte objectifs du J1

    /**
     * @ORM\Column(type="text")
     */
    private $finiJ2; //stock les points pour chaque carte objectifs du J2

    /**
     * @ORM\Column(type="integer", length=2)
     */
    private $partieGagne; //stock les points pour chaque carte objectifs du J2

    /**
     * @ORM\Column(type="text")
     */
    private $jetonJ1; //stock les id des cartes qui serviront pour les jetons

    /**
     * @ORM\Column(type="text")
     */
    private $jetonJ2; //stock les id des cartes qui serviront pour les jetons

    /**
     * @return mixed
     */
    public function getCartejetee()
    {
        return $this->carte_jetee;
    }

    /**
     * @param mixed $carte_jetee
     */
    public function setCarteJetee($carte_jetee): void
    {
        $this->carte_jetee = $carte_jetee;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getJoueur1()
    {
        return $this->id_j1;
    }

    /**
     * @param User $user
     */
    public function setJoueur1($user): void
    {
        $this->id_j1 = $user;
    }

    /**
     * @return User
     */
    public function getJoueur2(): User
    {
        return $this->id_j2;
    }

    /**
     * @param User  $adversaire
     */
    public function setJoueur2($adversaire): void
    {
        $this->id_j2 = $adversaire;
    }

    /**
     * @return User
     */
    public function getScoreJ1()
    {
        return $this->score_j1;
    }

    /**
     * @param User $score_j1
     */
    public function setScoreJ1($score_j1): void
    {
        $this->score_j1 = $score_j1;
    }

    /**
     * @return User
     */
    public function getScoreJ2()
    {
        return $this->score_j2;
    }

    /**
     * @param User $score_j2
     */
    public function setScoreJ2($score_j2): void
    {
        $this->score_j2 = $score_j2;
    }

    /**
     * @return mixed
     */
    public function getJ1Main()
    {
        return json_decode($this->main_j1);
    }
    /**
     * @param mixed $main_j1
     */
    public function setJ1Main($main_j1): void
    {
        $this->main_j1 = $main_j1;
    }

    /**
     * @return mixed
     */
    public function getJ2Main()
    {
        return json_decode($this->main_j2);
    }
    /**
     * @param mixed $main_j2
     */
    public function setJ2Main($main_j2): void
    {
        $this->main_j2 = $main_j2;
    }

    /**
     * @return mixed
     */
    public function getJ1Actions()
    {
        return json_decode($this->action_j1);
    }
    /**
     * @param mixed $action_j1
     */
    public function setJ1Actions($action_j1): void
    {
        $this->action_j1 = $action_j1;
    }

    /**
     * @return mixed
     */
    public function getJ2Actions()
    {
        return json_decode($this->action_j2);
    }
    /**
     * @param mixed $action_j2
     */
    public function setJ2Actions($action_j2): void
    {
        $this->action_j2 = $action_j2;
    }

    /**
     * @return mixed
     */
    public function getPioche()
    {
        return json_decode($this->partie_pioche);
    }
    /**
     * @param mixed $partie_pioche
     */
    public function setPioche($partie_pioche): void
    {
        $this->partie_pioche = $partie_pioche;
    }

    /**
     * @return mixed
     */
    public function getObjectifs()
    {
        return (array)json_decode($this->partie_objectifs);
    }

    /**
     * @param mixed $partie_objectifs
     */
    public function setObjectifs($partie_objectifs): void
    {
        $this->partie_objectifs = $partie_objectifs;
    }

    /**
     * @return mixed
     */
    public function getTerrainJ1()
    {
        return json_decode($this->terrain_j1);
    }
    /**
     * @param mixed $terrain_j1
     */
    public function setTerrainJ1($terrain_j1): void
    {
        $this->terrain_j1 = $terrain_j1;
    }

    /**
     * @return mixed
     */
    public function getTerrainJ2()
    {
        return json_decode($this->terrain_j2);
    }
    /**
     * @param mixed $terrain_j2
     */
    public function setTerrainJ2($terrain_j2): void
    {
        $this->terrain_j2 = $terrain_j2;
    }

    /**
     * @return mixed
     */
    public function getPartieTour()
    {
        return $this->partie_tour;
    }

    /**
     * @param mixed $partie_tour
     */
    public function setPartieTour($partie_tour)
    {
        $this->partie_tour = $partie_tour;
    }

    /**
     * @return mixed
     */
    public function getPartieFinie()
    {
        return $this->partie_finie;
    }

    /**
     * @param mixed $partie_finie
     */
    public function setPartieFinie($partie_finie): void
    {
        $this->partie_finie = $partie_finie;
    }

    /**
     * @return mixed
     */
    public function getPartieManche()
    {
        return $this->partie_manche;
    }

    /**
     * @param mixed $partie_manche
     */
    public function setPartieManche($partie_manche): void
    {
        $this->partie_manche = $partie_manche;
    }

    /**
     * @return mixed
     */
    public function getFiniJ1()
    {
        return $this->finiJ1;
    }

    /**
     * @param mixed $finiJ1
     */
    public function setFiniJ1($finiJ1): void
    {
        $this->finiJ1 = $finiJ1;
    }

    /**
     * @return mixed
     */
    public function getFiniJ2()
    {
        return $this->finiJ2;
    }

    /**
     * @param mixed $finiJ2
     */
    public function setFiniJ2($finiJ2): void
    {
        $this->finiJ2 = $finiJ2;
    }

    /**
     * @return mixed
     */
    public function getPartieGagne()
    {
        return $this->partieGagne;
    }

    /**
     * @param mixed $partieGagne
     */
    public function setPartieGagne($partieGagne): void
    {
        $this->partieGagne = $partieGagne;
    }

    /**
     * @return mixed
     */
    public function getJetonJ1()
    {
        return $this->jetonJ1;
    }

    /**
     * @param mixed $jetonJ1
     */
    public function setJetonJ1($jetonJ1): void
    {
        $this->jetonJ1 = $jetonJ1;
    }

    /**
     * @return mixed
     */
    public function getJetonJ2()
    {
        return $this->jetonJ2;
    }

    /**
     * @param mixed $jetonJ2
     */
    public function setJetonJ2($jetonJ2): void
    {
        $this->jetonJ2 = $jetonJ2;
    }
}