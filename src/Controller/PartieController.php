<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Partie;
use App\Entity\Carte;
use App\Entity\Objectifs;
use App\Form\ImageType;
use App\Form\InscriptionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class PartieController
 * @package App\Controller
 * @Route("/partie")
 */
class PartieController extends Controller
{
    /**
     * @Route("/new", name="nouvelle_partie")
     */
    public function newPartie(Request $request){
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            //Passer $user_etat à 1 lorsqu'il se connecte
            $connect = $this->getUser(); //On récupère l'ID de l'utilisateur connecté

            //On récupère le score de l'utilisateur
            $score = $this->getDoctrine()->getRepository("App:Partie")->findBy(['id_j1' => $connect]);
            $score2 = $this->getDoctrine()->getRepository("App:Partie")->findBy(['id_j2' => $connect]);

            $scoreTotal = array();
            foreach($score as $valeur){
                $scoreTotal[] =  $valeur->getScoreJ1();
            }
            foreach($score2 as $valeur){
                $scoreTotal[] =  $valeur->getScoreJ2();
            }
            $scoreJoueur = array_sum($scoreTotal);

            $nbPartie = count($scoreTotal);

            $parties = $this->getDoctrine()->getRepository("App:Partie")->findAll();

            //On ouvre la connexion à la base de donnée
            $entityManager = $this->getDoctrine()->getManager();
            $etat = $entityManager->getRepository(User::class)->find($connect);

            if($connect->getUserEtat() != 1) {
                $etat->setUserEtat(1);
            }

            $entityManager->flush();

            $users = $this->getDoctrine()->getRepository(User::class)->findAll();

            //Création du formulaire pour ajouter une image en photo de profil
            $form = $this->createForm(ImageType::class, $connect);
            $form->handleRequest($request);

            //Traitement du formulaire pour l'ajout d'une photo de profil
            if ($form->isSubmitted() && $form->isValid()) {

                $file = $connect->getImage(); //On récupère l'image passée dans le formulaire

                $fileName = 'user'.$connect->getId().'.'.$file->guessExtension(); //On renomme l'image avec l'id du user connecté (pas le pseudo pour éviter les caractères spéciaux)

                $file->move(
                    $this->getParameter('user_images'),
                    $fileName
                );

                //On ajoute le nom de l'image dans le champ image de l'utilisateur
                $connect->setImage($fileName);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->flush();

                return $this->render('partie/nouvelle.html.twig', ['users' => $users, 'connect'=>$connect, 'score'=>$scoreJoueur, 'nbPartie'=>$nbPartie, 'parties'=>$parties, 'form' => $form->createView()]);
            }

            return $this->render('partie/nouvelle.html.twig', ['users' => $users, 'connect'=>$connect, 'score'=>$scoreJoueur, 'nbPartie'=>$nbPartie, 'parties'=>$parties, 'form' => $form->createView()]);
        } else{
            return $this->redirectToRoute('index');
        }
    }

    /**
     * @Route("/creer", name="creer_partie")
     */
    public function creerPartie(Request $request)
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) { //Si connecté, on continue le script
            $idAdversaire = $request->request->get('adversaire');
            $user = $this->getUser(); //ID de l'utilisateur connecté
            //$user = $this->getDoctrine()->getRepository(User::class)->find(1);
            $adversaire = $this->getDoctrine()->getRepository(User::class)->find($idAdversaire);

            //Récupérer les cartes objets depuis la BDD
            $cartes = $this->getDoctrine()->getRepository(Carte::class)->findAll();
            $tCartes = array();

            //Récupérer les cartes objectifs depuis la BDD
            $objectifs = $this->getDoctrine()->getRepository(Objectifs::class)->findAll();
            $tObjectifs = array();

            //Création des actions de départ
            $pacte = array('etat'=>0, 'carte'=>0);
            $abandon = array('etat'=>0, 'carte'=>array());
            $monopole = array('etat'=>0, 'carte'=>array());
            $negociation = array('etat'=>0, 'carte'=>array('paire1'=>array(), 'paire2'=>array()));// A revoir

            $tAction = array("pacte"=>$pacte, "abandon"=>$abandon, "monopole"=>$monopole, "negociation"=>$negociation);

            $terrainJ1 = array('pacte'=>0, 'monopole'=>0, 'negociation'=>array());

            $terrainJ2 = array('pacte'=>0, 'monopole'=>0, 'negociation'=>array());

            $fini = array('obj1'=> array('etatA1'=>0, 'etatA3recu'=>0, 'etatA3garde1'=>0, 'etatA3garde2'=>0, 'etatA4recu1'=>0, 'etatA4recu2'=>0, 'etatA4garde1'=>0, 'etatA4garde2'=>0, 'points'=>0),
                                    'obj2'=> array('etatA1'=>0, 'etatA3recu'=>0, 'etatA3garde1'=>0, 'etatA3garde2'=>0, 'etatA4recu1'=>0, 'etatA4recu2'=>0, 'etatA4garde1'=>0, 'etatA4garde2'=>0, 'points'=>0),
                                    'obj3'=> array('etatA1'=>0, 'etatA3recu'=>0, 'etatA3garde1'=>0, 'etatA3garde2'=>0, 'etatA4recu1'=>0, 'etatA4recu2'=>0, 'etatA4garde1'=>0, 'etatA4garde2'=>0, 'points'=>0),
                                    'obj4' => array('etatA1'=>0, 'etatA3recu'=>0, 'etatA3garde1'=>0, 'etatA3garde2'=>0, 'etatA4recu1'=>0, 'etatA4recu2'=>0, 'etatA4garde1'=>0, 'etatA4garde2'=>0, 'points'=>0),
                                    'obj5' => array('etatA1'=>0, 'etatA3recu'=>0, 'etatA3garde1'=>0, 'etatA3garde2'=>0, 'etatA4recu1'=>0, 'etatA4recu2'=>0, 'etatA4garde1'=>0, 'etatA4garde2'=>0, 'points'=>0),
                                    'obj6' => array('etatA1'=>0, 'etatA3recu'=>0, 'etatA3garde1'=>0, 'etatA3garde2'=>0, 'etatA4recu1'=>0, 'etatA4recu2'=>0, 'etatA4garde1'=>0, 'etatA4garde2'=>0, 'points'=>0),
                                    'obj7' => array('etatA1'=>0, 'etatA3recu'=>0, 'etatA3garde1'=>0, 'etatA3garde2'=>0, 'etatA4recu1'=>0, 'etatA4recu2'=>0, 'etatA4garde1'=>0, 'etatA4garde2'=>0, 'points'=>0));

            $jetonJ1 = array('pacte'=>0, 'monopoleRecu'=>0, 'monopoleGarde1'=>0, 'monopoleGarde2'=>0, 'negociationRecu1'=>0, 'negociationRecu2'=>0, 'negociationGarde1'=>0, 'negociationGarde2'=>0);
            $jetonJ2 = array('pacte'=>0, 'monopoleRecu'=>0, 'monopoleGarde1'=>0, 'monopoleGarde2'=>0, 'negociationRecu1'=>0, 'negociationRecu2'=>0, 'negociationGarde1'=>0, 'negociationGarde2'=>0);

            //Mélanger les cartes
            foreach ($cartes as $carte) {
                $tCartes[] = $carte->getId();
            }

            shuffle($tCartes); //Mélange le tableau contenant les id

            //Retrait de la première carte
            $cartejetee = array_pop($tCartes);

            //Distribution des cartes aux joueurs
            $tMainJ1 = array();
            for ($i = 0; $i < 6; $i++) {
                $tMainJ1[] = array_pop($tCartes);
            }

            $tMainJ2 = array();
            for ($i = 0; $i < 6; $i++) {
                $tMainJ2[] = array_pop($tCartes);
            }

            //Créer la pioche
            $tPioche = $tCartes; //Sauvegarde des dernières cartes dans la pioche

            //Créer un objet de type Partie
            $partie = new Partie();

            $partie->setJoueur1($user); //On ne récupère pas l'ID du joueur 1
            $partie->setJoueur2($adversaire); //On ne récupère pas l'ID du joueur 2
            $partie->setCarteJetee($cartejetee);
            $partie->setJ1Main(json_encode($tMainJ1));
            $partie->setJ2Main(json_encode($tMainJ2));
            $partie->setPioche(json_encode($tPioche));
            $idtour = rand(1,20) % 2 ? $user->getId() : $adversaire->getId(); //notation terenaire
            $partie->setPartieTour($idtour);
            $partie->setPartieFinie(1); //Partie finie correspond au nombre de tour. Si == 9, et manche == 1, alors la partie est considéré comme finie
            $partie->setPartieManche(1);
            $partie->setPartieGagne(0);
            //$partie->setObjectifs(json_encode($tObjectifs));
            $partie->setObjectifs('[1, 2, 3, 4, 5, 6, 7]'); //Cartes objectifs passées manuellement car MANYTOMANY ne fonctionne pas (dans Carte.php)
            $partie->setJ1Actions(json_encode($tAction)); //A faire avec le MANYTOMANY
            $partie->setJ2Actions(json_encode($tAction)); //A faire avec le MANYTOMANY
            $partie->setScoreJ1(0);
            $partie->setScoreJ2(0);
            $partie->setTerrainJ1(json_encode($terrainJ1)); //A quoi sert setTerrain ?
            $partie->setTerrainJ2(json_encode($terrainJ2)); //Idem
            $partie->setFiniJ1(json_encode($fini));
            $partie->setFiniJ2(json_encode($fini));
            $partie->setJetonJ1(json_encode($jetonJ1));
            $partie->setJetonJ2(json_encode($jetonJ2));

            //Récupérer le manager de doctrine (connexion BDD)
            $em = $this->getDoctrine()->getManager();

            //Sauvegarde mon objet Partie dans la BDD
            $em->persist($partie);
            $em->flush();

            return $this->redirectToRoute('afficher_partie', ['id' => $partie->getId(), 'adversaire'=>$adversaire]);
        } else{
            return $this->redirectToRoute('index'); //Si non connecté, on redirige vers la page d'accueil
        }
    }

    /**
     * @Route("/join", name="join_partie")
     */
    public function joinPartie(Request $request){
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) { //Si connecté, on continue le script
            $user = $this->getUser(); //On récupère l'ID de l'utilisateur

            //On récupère les parties où partie_tour coïncide avec l'ID de l'utilisateur $user
            $parties = $this->getDoctrine()->getRepository("App:Partie")->findBy(['partie_tour' => $user]);

            $test = array();
            foreach ($parties as $result){
                $test[] = $result->getId();
            }

            //On récupère le partieGagne
            $partieGagne = $this->getDoctrine()->getRepository("App:Partie")->findBy(['partieGagne' => 1]);
            $partieWin = array();
            foreach ($partieGagne as $try){
                $partieWin[] = $try->getId();
            }

            $partieFinie = 0;
            $partieManche = 0;

            return $this->render('partie/join.html.twig', ['user'=>$user, 'tourPartie'=>$partieFinie, 'manchePartie'=>$partieManche, 'parties'=>$parties, 'test'=>$test]);
        } else{
            return $this->redirectToRoute('index'); //Si non connecté, on redirige vers la page d'accueil
        }
    }

    /**
     * @Route("/rejoindre", name="rejoindre")
     */
    public function rejoindrePartie(Request $request){
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) { //Si connecté, on continue le script
            $user = $this->getUser(); //On récupère l'ID de l'utilisateur

            //On récupère les parties où partie_tour coïncide avec l'ID de l'utilisateur $user
            $parties = $this->getDoctrine()->getRepository("App:Partie")->findBy(['partie_tour' => $user]);

            $partie = $request->request->get('partie');

            $partieFinie = 0;
            $partieManche = 0;

            return $this->redirectToRoute('afficher_partie', ['id' => $partie]);
        } else{
            return $this->redirectToRoute('index'); //Si non connecté, on redirige vers la page d'accueil
        }
    }

    /**
     * @Route("/afficher/{id}", name="afficher_partie")
     */
    public function afficherPartie(Partie $partie, Request $request)
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) { //Si connecté, on continue le script)
            //On récupère l'id de l'utilisateur connecté
            $user = $this->getUser();

            //On récupère l'id de la partie
            $idPartie = $partie->getId();

            //On récupère l'id de l'utilisateur du tour en cours
            $userTour = intval($partie->getPartieTour());
            $userInTour = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $userTour]); //Et ici, ses infos en fonction de son ID

            //On récupère le numéro du tour de la partie en cours
            $partieFinie = $partie->getPartieFinie();

            //On récupère l'id du joueur1
            $joueur1 = $partie->getJoueur1();

            //On récupère l'id du joueur2
            $joueur2 = $partie->getJoueur2();

            //On récupère la manche en cours de la partie
            $partieManche = $partie->getPartieManche();

            //On récupère partieGagne
            $partieGagne = $partie->getPartieGagne();

            //On récupère les jetons
            $jetonJ1 = json_decode($partie->getJetonJ1());
            $jetonJ2 = json_decode($partie->getJetonJ2());

            //On récupère les scores si la manche est supérieure à 1
            if($partieManche == 1 and $partieGagne == 1){
                $scoreJ1 = $partie->getScoreJ1();
                $scoreJ2 = $partie->getScoreJ2();
            } elseif($partieManche >= 2 and $partieGagne == 1){
                $scoreJ1 = $partie->getScoreJ1();
                $scoreJ2 = $partie->getScoreJ2();
            } elseif($partieManche == 1 and $partieGagne != 1) {
                $scoreJ1 = $partie->getScoreJ1();
                $scoreJ2 = $partie->getScoreJ2();
            } elseif($partieManche >= 2 and $partieGagne != 1) {
                $scoreJ1 = $partie->getScoreJ1();
                $scoreJ2 = $partie->getScoreJ2();
            } else{
                $scoreJ1 = "";
                $scoreJ2 = "";
            }

            //On récupère les infos de l'adversaire
            //$adversaireID = $partie->getJoueur2(); //Ici, on récupère son ID
            //$adversaire = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $adversaireID]); //Et ici, ses infos en fonction de son ID

            if($user->getId() == $joueur1->getId()){
                $adversaireID = $joueur2->getId();
                $adversaire = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $adversaireID]);
            } elseif($user->getId() == $joueur2->getId()){
                $adversaireID = $joueur1->getId();
                $adversaire = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $adversaireID]);
            }

            $tActions = array(); //On récupère les actions pour le monopole
            if($joueur1->getId() == $userTour){ //Si c'est le tour du j1, on récupère les actions du j2
                $tActions = $partie->getJ2Actions();
            } else if($adversaire->getId()){
                $tActions = $partie->getJ1Actions();
            } else{
                echo 'Erreur';
            }
            $etatMonopole = $tActions->monopole->etat;
            $nbMonopoleBDD = count($tActions->monopole->carte);
            $monopoleBDD = $tActions->monopole->carte;

            $allCarteMonopole = array();
            foreach ($monopoleBDD as $result) {
                $allCarteMonopole[] = $this->getDoctrine()->getRepository("App:Carte")->findBy(['id' => $result]);
            }

            $etatNegociation = $tActions->negociation->etat;
            $nbNegociationBDD = count($tActions->negociation->carte->paire1) + count($tActions->negociation->carte->paire2);
            //dump(count($tActions->negociation->carte));

            if($tActions->negociation->carte->paire1!= null and$tActions->negociation->carte->paire2 != null) {
                $Negociation1 = $tActions->negociation->carte->paire1;
                $Negociation2 = $tActions->negociation->carte->paire2;

                //On récupère les ID de la 1ère paire
                $idpaire1Negociation = array();
                foreach ($Negociation1 as $result) {
                        $idpaire1Negociation[] = intval($result);

                }
                $allCarteNegociation = array();
                foreach ($idpaire1Negociation as $result) {
                    $paire1Negociation[] = $this->getDoctrine()->getRepository("App:Carte")->findBy(['id' => $result]);
                }

                //On récupère les ID de la 2ème paire
                $idpaire2Negociation = array();
                foreach ($Negociation2 as $result) {
                        $idpaire2Negociation[] = intval($result);
                }
                $allCarteNegociation = array();
                foreach ($idpaire2Negociation as $result) {
                    $paire2Negociation[] = $this->getDoctrine()->getRepository("App:Carte")->findBy(['id' => $result]);
                }
            } else{
                $Negociation1 = null;
                $Negociation2 = null;

                $paire1Negociation = null;
                $paire2Negociation = null;

                //On récupère les ID de la 1ère paire
                $idpaire1Negociation = array();
                $allCarteNegociation = array();

                //On récupère les ID de la 2ème paire
                $idpaire2Negociation = array();
                $allCarteNegociation = array();
            }

            //On distribue la 1ère carte au joueur du 1er tour
            $cartePioche = $partie->getPioche(); //On récupère la pioche
            //Pioche en fonction de l'utilisateur du tour en cours. Lorsqu'il termine son action, on distribue une carte à l'adversaire.
            if ($joueur1->getId() == $userInTour->getId()) { //Si c'est le tour du joueur1
                //ON LUI DISTRIBUE UNE CARTE DE LA PIOCHE
                $main = $partie->getJ1Main(); //1 - on récupère sa main actuelle
                $mainJ2 = $partie->getJ2Main();
                //dump($main);
                if (count($cartePioche) == 8) {
                    $carte_piochee = array_pop($cartePioche); //On retire une carte de la pioche
                    $main[] = $carte_piochee;
                }
                $partie->setJ1Main(json_encode($main)); //on MAJ la main du joueur
                $partie->setPioche(json_encode($cartePioche)); //on MAJ la pioche
            } elseif($joueur2->getId() == $userInTour->getId()) {
                $main = $partie->getJ2Main();
                $mainJ2 = $partie->getJ1Main();
                //dump($main);
                if (count($cartePioche) == 8) {
                    $carte_piochee = array_pop($cartePioche); //On retire une carte de la pioche
                    $main[] = $carte_piochee;
                }
                $partie->setJ2Main(json_encode($main)); //on MAJ la main du joueur
                $partie->setPioche(json_encode($cartePioche)); //on MAJ la pioche
            }
            else{
                dump($userTour);
            }
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            //LE JOUEUR CHOISIT L'UNE DES ACTIONS
            if($userInTour->getId() == $joueur1->getId()){
                $actionJ1 = $partie->getJ1Actions();
                $actionJ2 = $partie->getJ2Actions();

                $action= $partie->getJ1Actions();
                $pacte = $action->pacte->etat;
                $abandon = $action->abandon->etat;
                $monopole = $action->monopole->etat;
                $negociation = $action->negociation->etat;
            } elseif($userInTour->getId() == $joueur2->getId()){
                $actionJ1 = $partie->getJ1Actions();
                $actionJ2 = $partie->getJ2Actions();

                $action= $partie->getJ2Actions();
                $pacte = $action->pacte->etat;
                $abandon = $action->abandon->etat;
                $monopole = $action->monopole->etat;
                $negociation = $action->negociation->etat;
            }

            //$partie
            $objets = $this->getDoctrine()->getRepository("App:Carte")->findAll();
            $tObjets = array();

            $objectifs = $this->getDoctrine()->getRepository("App:Objectifs")->findAll();
            $tObjectifs = array();

            foreach($objets as $objet) {
                $tObjets[$objet->getId()] = $objet;
            }

            foreach($objectifs as $objectif) {
                $tObjectifs[$objectif->getId()] = $objectif;
            }

            //On récupère les états de chaque action pour déterminer si la manche est finie ou non
            if($partieManche == 1){
                if($pacte != 0 and $abandon != 0 and $monopole != 0 and $negociation != 0 and $partieFinie == 13 and empty($main) and empty($mainJ2) and empty($cartePioche)){
                    $this->finDeManche($partie);
                }
            } elseif($partieManche == 2){
                if($pacte != 0 and $abandon != 0 and $monopole != 0 and $negociation != 0 and $partieFinie == 13 and empty($main) and empty($mainJ2) and empty($cartePioche)){
                    $this->finDeManche($partie);
                }
            } elseif($partieManche == 3) {
                if ($pacte != 0 and $abandon != 0 and $monopole != 0 and $negociation != 0 and $partieFinie == 13 and empty($main) and empty($mainJ2) and empty($cartePioche)) {
                    $this->finDeManche($partie);
                }
            }

            return $this->render('partie/afficher.html.twig',
                ['objets'=>$tObjets,
                    'user'=>$user,
                    'joueur1'=>$joueur1,
                    'joueur2'=>$joueur2,
                    'adversaire'=>$adversaire,
                    'objectifs'=>$tObjectifs,
                    'main'=>$main,
                    'mainJ2'=>$main,
                    'scoreJ1'=>$scoreJ1,
                    'scoreJ2'=>$scoreJ2,
                    'pacte'=>$pacte,
                    'abandon'=> $abandon,
                    'monopoleUser'=> $monopole,
                    'negociationUser'=> $negociation,
                    'etatMonopole'=>$etatMonopole,
                    'etatNegociation'=>$etatNegociation,
                    'negociation'=> $negociation,
                    'partieFinie'=>$partieFinie,
                    'partieManche'=>$partieManche,
                    'partieGagne'=>$partieGagne,
                    'jetonJ1'=>$jetonJ1,
                    'jetonJ2'=>$jetonJ2,
                    'idUserTour'=>$userTour,
                    'tourJoueur'=>$userInTour,
                    'nbCarteMonopole'=>$nbMonopoleBDD,
                    'allCarteMonopole'=>$allCarteMonopole,
                    'carteMonopole'=>$monopoleBDD,
                    'nbCarteNegociation'=>$nbNegociationBDD,
                    'paire1Negociation'=>$paire1Negociation,
                    'paire2Negociation'=>$paire2Negociation,
                    'partie' => $partie]);
        } else{
            return $this->redirectToRoute('index'); //Si non connecté, on redirige vers la page d'accueil
        }
    }

    /**
     * @Route("/actionPacte/{id}", name="action_pacte")
     */
    public function actionPacte(Partie $partie, Request $request){
        //On récupère le joueur connecté
        $user = $this->getUser();

        //On récupère l'ID de la partie
        $idPartie = $partie->getId();

        //On récupère l'id du joueur1
        $joueur1 = $partie->getJoueur1();

        //On récupère l'id du joueur2
        $joueur2 = $partie->getJoueur2();

        //On récupère les infos de l'adversaire
        $adversaireID = $partie->getJoueur2(); //Ici, on récupère son ID
        $adversaire= $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $adversaireID]); //Et ici, ses infos en fonction de son ID

        $userTour = $partie->getPartieTour();

        $partieFinie = $partie->getPartieFinie();

        //On récupère les jetons
        $jetonJ1 = json_decode($partie->getJetonJ1());
        $jetonJ2 = json_decode($partie->getJetonJ2());

        if($user->getId() == $joueur1->getId()){
            //On récupère le terrain de l'utilisateur
            $UserPartie = $this->getDoctrine()->getRepository("App:Partie")->findOneBy(['id_j1' => $user, 'id' => $idPartie]);
            $terrainJ1 = $UserPartie->getTerrainJ1();
            $terrainJ1Pacte = $UserPartie->getTerrainJ1()->pacte;
        } elseif($user->getId() == $joueur2->getId()){
            //On récupère le terrain de l'utilisateur
            $UserPartie = $this->getDoctrine()->getRepository("App:Partie")->findOneBy(['id_j2' => $joueur2->getId(), 'id' => $idPartie]);
            $terrainJ2 = $UserPartie->getTerrainJ2();
            $terrainJ2Pacte = $UserPartie->getTerrainJ2()->pacte;
        } else {
        }

        //---------------------------------------------------------DEBUT ACTION PACTE -------------------------------------------------------------------------
        //On récupère l'ID de la carte passée dans le formulaire
        $cartePacte = $request->request->get('cartePacte');

        $tMain = array(); //On récupère la main
        if($joueur1->getId() == $userTour){
            $tMain = $partie->getJ1Main();
        } else if($adversaire->getId()){
            $tMain = $partie->getJ2Main();
        } else{
            echo 'Erreur';
        }

        $tActions = array(); //On récupère les actions
        if($joueur1->getId() == $userTour){
            $tActions = $partie->getJ1Actions();
        } else if($adversaire->getId()){
            $tActions = $partie->getJ2Actions();
        } else{
            echo 'Erreur';
        }


        if($tActions->pacte->etat != 1){ //Si l'état de l'action est différent de 1 (donc 0), on effectue l'action
            //On supprime la carte de la main du joueur
            unset($tMain[array_search($cartePacte, $tMain)]);
            $tMain = array_values($tMain); //on reconstruit les clefs du tableau
            sort($tMain);

            $tActions->pacte->etat = 1;
            $tActions->pacte->carte = $cartePacte;

            //On enregistre dans la BDD
            if($joueur1->getId() == $userTour){
                $partie->setJ1Main(json_encode($tMain)); //on MAJ la main du joueur
                $partie->setJ1Actions(json_encode($tActions)); //on MAJ la pioche
                $partieFinie+=1;
                $partie->setPartieFinie($partieFinie);
                $terrainJ1->pacte = $cartePacte;
                $partie->setTerrainJ1(json_encode($terrainJ1));
                $jetonJ1->pacte = $cartePacte;
                $partie->setJetonJ1(json_encode($jetonJ1));

            } else if($adversaire->getId() == $userTour){
                $partie->setJ2Main(json_encode($tMain)); //on MAJ la main de l'adversaire
                $partie->setJ2Actions(json_encode($tActions)); //on MAJ la pioche
                $partieFinie+=1;
                $partie->setPartieFinie($partieFinie);
                $terrainJ2->pacte = $cartePacte;
                $partie->setTerrainJ2(json_encode($terrainJ2));
                $jetonJ2->pacte = $cartePacte;
                $partie->setJetonJ2(json_encode($jetonJ2));
            } else{
                echo 'Erreur';
            }

            //On change de tour
            $cartePioche = $partie->getPioche(); //On récupère la pioche
            $carte_piochee = array_pop($cartePioche); //On retire une carte de la pioche
            //Pioche en fonction de l'utilisateur du tour en cours. Lorsqu'il termine son action, on distribue une carte à l'adversaire.
            if ($userTour == $joueur1->getId()) { //Si c'est le tour du joueur1
                //ON LUI DISTRIBUE UNE CARTE DE LA PIOCHE
                $main = $partie->getJ2Main(); //1 - on récupère sa main actuelle
                if ($carte_piochee != null) {
                    $main[] = $carte_piochee;
                }
                $partie->setPartieTour($joueur2->getId());
                $partie->setJ2Main(json_encode($main)); //on MAJ la main du joueur
                $partie->setPioche(json_encode($cartePioche)); //on MAJ la pioche
            } else {
                $main = $partie->getJ1Main();
                if ($carte_piochee != null) {
                    $main[] = $carte_piochee;
                }
                $partie->setPartieTour($joueur1->getId());
                $partie->setJ1Main(json_encode($main)); //on MAJ la main du joueur
                $partie->setPioche(json_encode($cartePioche)); //on MAJ la pioche
            }
            $em = $this->getDoctrine()->getManager();
            $em->flush();
        } else{
            return $this->redirectToRoute('afficher_partie', ['id' => $partie->getId(), 'adversaire'=>$adversaire]);
            //Sinon si l'état == 1, on redirige vers la partie sans rien faire (ou on ne redirige pas ?)
        }

        //---------------------------------------------------------FIN ACTION PACTE -------------------------------------------------------------------------

        return $this->redirectToRoute('afficher_partie', ['id' => $partie->getId(), 'adversaire'=>$adversaire]);
        //Si non connecté, on redirige vers la page d'accueil
    }


    /**
     * @Route("/actionAbandon/{id}", name="action_abandon")
     */
    public function actionAbandon(Partie $partie, Request $request){
        //On récupère l'id du joueur1
        $joueur1 = $partie->getJoueur1();

        //On récupère l'id du joueur2
        $joueur2 = $partie->getJoueur2();

        //On récupère l'id de l'utilisateur du tour en cours
        $userTour = intval($partie->getPartieTour());
        $userInTour = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $userTour]); //Et ici, ses infos en fonction de son ID

        //On récupère les infos de l'adversaire
        $adversaireID = $partie->getJoueur2(); //Ici, on récupère son ID
        $adversaire= $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $adversaireID]); //Et ici, ses infos en fonction de son ID

        $partieFinie = $partie->getPartieFinie();

        //---------------------------------------------------------DEBUT ACTION ABANDON -------------------------------------------------------------------------
        //On récupère l'ID des cartes passées dans le formulaire
        $carteAbandon1 = $request->request->get('carteAbandon1');
        $carteAbandon2 = $request->request->get('carteAbandon2');

        $tMain = array(); //On récupère la main
        if($joueur1->getId() == $userTour){
            $tMain = $partie->getJ1Main();
        } else if($adversaire->getId()){
            $tMain = $partie->getJ2Main();
        } else{
            echo 'Erreur';
        }

        $tActions = array(); //On récupère les actions
        if($joueur1->getId() == $userTour){
            $tActions = $partie->getJ1Actions();
        } else if($adversaire->getId()){
            $tActions = $partie->getJ2Actions();
        } else{
            echo 'Erreur';
        }

        if($tActions->abandon->etat != 1){
            //On supprime les 2 cartes de la main
            unset($tMain[array_search($carteAbandon1, $tMain)]);
            unset($tMain[array_search($carteAbandon2, $tMain)]);

            $tMain = array_values($tMain); //on reconstruit les clefs du tableau
            sort($tMain);

            $carteAbandon = array(); //tableau stockant les ID des 2 cartes défaussées
            $carteAbandon[] = $carteAbandon1;
            $carteAbandon[] = $carteAbandon2;

            $tActions->abandon->etat = 1;
            $tActions->abandon->carte = $carteAbandon;

            //dump($carteAbandon);

            //On enregistre dans la BDD
            if($joueur1->getId() == $userTour){
                $partie->setJ1Main(json_encode($tMain)); //on MAJ la main du joueur
                $partie->setJ1Actions(json_encode($tActions)); //on MAJ la pioche
                $partieFinie+=1;
                $partie->setPartieFinie($partieFinie);
            } else if($adversaire->getId() == $userTour){
                $partie->setJ2Main(json_encode($tMain)); //on MAJ la main de l'adversaire
                $partie->setJ2Actions(json_encode($tActions)); //on MAJ la pioche
                $partieFinie+=1;
                $partie->setPartieFinie($partieFinie);
            } else{
                echo 'Erreur';
            }

            $cartePioche = $partie->getPioche(); //On récupère la pioche
            $carte_piochee = array_pop($cartePioche); //On retire une carte de la pioche
            //Pioche en fonction de l'utilisateur du tour en cours. Lorsqu'il termine son action, on distribue une carte à l'adversaire.
            if ($userTour == $joueur1->getId()) { //Si c'est le tour du joueur1
                //ON LUI DISTRIBUE UNE CARTE DE LA PIOCHE
                $main = $partie->getJ2Main(); //1 - on récupère sa main actuelle
                if ($carte_piochee != null) {
                    $main[] = $carte_piochee;
                }
                $partie->setPartieTour($joueur2->getId());
                $partie->setJ2Main(json_encode($main)); //on MAJ la main du joueur
                $partie->setPioche(json_encode($cartePioche)); //on MAJ la pioche
            } else {
                $main = $partie->getJ1Main();
                if ($carte_piochee != null) {
                    $main[] = $carte_piochee;
                }
                $partie->setPartieTour($joueur1->getId());
                $partie->setJ1Main(json_encode($main)); //on MAJ la main du joueur
                $partie->setPioche(json_encode($cartePioche)); //on MAJ la pioche
            }
            $em = $this->getDoctrine()->getManager();
            $em->flush();

        } else{
            return $this->redirectToRoute('afficher_partie', ['id' => $partie->getId(), 'adversaire'=>$adversaire]);
            //Sinon si l'état == 1, on redirige vers la partie sans rien faire (ou on ne redirige pas ?)
        }

        //---------------------------------------------------------FIN ACTION ABANDON -------------------------------------------------------------------------

        return $this->redirectToRoute('afficher_partie', ['id' => $partie->getId(), 'adversaire'=>$adversaire]); //Si non connecté, on redirige vers la page d'accueil
    }

    /**
     * @Route("/actionMonopole/{id}", name="action_monopole")
     */
    public function actionMonopole(Partie $partie, Request $request){
        //On récupère l'id du joueur1
        $joueur1 = $partie->getJoueur1();

        //On récupère l'id du joueur2
        $joueur2 = $partie->getJoueur2();

        //On récupère l'id de l'utilisateur du tour en cours
        $userTour = intval($partie->getPartieTour());
        $userInTour = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $userTour]); //Et ici, ses infos en fonction de son ID

        //On récupère les infos de l'adversaire
        $adversaireID = $partie->getJoueur2(); //Ici, on récupère son ID
        $adversaire= $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $adversaireID]); //Et ici, ses infos en fonction de son ID

        $partieFinie = $partie->getPartieFinie();

        //On récupère les jetons
        $jetonJ1 = json_decode($partie->getJetonJ1());
        $jetonJ2 = json_decode($partie->getJetonJ2());

        //---------------------------------------------------------DEBUT ACTION MONOPOLE -------------------------------------------------------------------------
        //On récupère l'ID des cartes passées dans le formulaire
        $carteMonopole1 = $request->request->get('carteMonopole1');
        $carteMonopole2 = $request->request->get('carteMonopole2');
        $carteMonopole3 = $request->request->get('carteMonopole3');

        $tMain = array(); //On récupère la main
        if($joueur1->getId() == $userTour){
            $tMain = $partie->getJ1Main();
        } else if($adversaire->getId()){
            $tMain = $partie->getJ2Main();
        } else{
            echo 'Erreur';
        }

        $tActions = array(); //On récupère les actions
        if($joueur1->getId() == $userTour){
            $tActions = $partie->getJ1Actions();
        } else if($adversaire->getId()){
            $tActions = $partie->getJ2Actions();
        } else{
            echo 'Erreur';
        }

        if($tActions->monopole->etat != 1){
            //On supprime les 3 cartes de la main
            unset($tMain[array_search($carteMonopole1, $tMain)]);
            unset($tMain[array_search($carteMonopole2, $tMain)]);
            unset($tMain[array_search($carteMonopole3, $tMain)]);

            $tMain = array_values($tMain); //on reconstruit les clefs du tableau
            sort($tMain);

            $carteMonopole = array(); //tableau stockant les ID des 3 cartes défaussées
            $carteMonopole[] = $carteMonopole1;
            $carteMonopole[] = $carteMonopole2;
            $carteMonopole[] = $carteMonopole3;

            //On stock les 3 cartes dans la BDD. L'étape d'après est de les proposer à l'adversaire. --> Si $tActions->monopole->etat != 1 && !empty($tActions->monopole->carte), on donne le choix des 3 cartes.
            //Lorsque celui-ci en aura choisit une, on la stock dans sa table et on laisse les 2 autres dans la table du joueur1.
            //Une fois ceci fait, on passe l'état de l'action à 1 et le joueur2 garde la main (il faut donc rafraichir pour la pioche, ou activer la pioche au moment de la sélection de la carte)

            $tActions->monopole->etat = 1;
            $tActions->monopole->carte = $carteMonopole;

            //On enregistre dans la BDD
            if($joueur1->getId() == $userTour){
                $partie->setJ1Main(json_encode($tMain)); //on MAJ la main du joueur
                $partie->setJ1Actions(json_encode($tActions)); //on MAJ la pioche
                $partieFinie+=1;
                $partie->setPartieFinie($partieFinie);
            } else if($adversaire->getId() == $userTour){
                $partie->setJ2Main(json_encode($tMain)); //on MAJ la main de l'adversaire
                $partie->setJ2Actions(json_encode($tActions)); //on MAJ la pioche
                $partieFinie+=1;
                $partie->setPartieFinie($partieFinie);
            } else{
                echo 'Erreur';
            }

            $etatMonopole = $tActions->monopole->etat;
            $etatNegociation = $tActions->negociation->etat;
            $monopoleBDD = $tActions->monopole->carte;

            $cartePioche = $partie->getPioche(); //On récupère la pioche
            $carte_piochee = array_pop($cartePioche); //On retire une carte de la pioche
            //Pioche en fonction de l'utilisateur du tour en cours. Lorsqu'il termine son action, on distribue une carte à l'adversaire.
            if ($userTour == $joueur1->getId()) { //Si c'est le tour du joueur1
                //ON LUI DISTRIBUE UNE CARTE DE LA PIOCHE
                $main = $partie->getJ2Main(); //1 - on récupère sa main actuelle
                if ($carte_piochee != null) {
                    $main[] = $carte_piochee;
                }
                $partie->setPartieTour($joueur2->getId());
                $partie->setJ2Main(json_encode($main)); //on MAJ la main du joueur
                $partie->setPioche(json_encode($cartePioche)); //on MAJ la pioche
            } else {
                $main = $partie->getJ1Main();
                if ($carte_piochee != null) {
                    $main[] = $carte_piochee;
                }
                $partie->setPartieTour($joueur1->getId());
                $partie->setJ1Main(json_encode($main)); //on MAJ la main du joueur
                $partie->setPioche(json_encode($cartePioche)); //on MAJ la pioche
            }
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->redirectToRoute('afficher_partie', ['id' => $partie->getId(), 'adversaire'=>$adversaire, 'etatMonopole'=>$etatMonopole, 'carteMonopole'=>$monopoleBDD]);
        } else{
            return $this->redirectToRoute('afficher_partie', ['id' => $partie->getId(), 'adversaire'=>$adversaire]);
            //Sinon si l'état == 1, on redirige vers la partie sans rien faire (ou on ne redirige pas ?)
        }

        return $this->redirectToRoute('afficher_partie'); //Si non connecté, on redirige vers la page d'accueil
    }


    /**
     * @Route("/validMonopole/{id}", name="valid_monopole")
     */
    public function validMonopole(Partie $partie, Request $request)
    {
        //On récupère l'id du joueur1
        $joueur1 = $partie->getJoueur1();

        //On récupère l'id du joueur2
        $joueur2 = $partie->getJoueur2();

        //On récupère le user connecté
        $user = $this->getUser();

        //On récupère l'id de l'utilisateur du tour en cours
        $userTour = intval($partie->getPartieTour());
        $userInTour = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $userTour]); //Et ici, ses infos en fonction de son ID

        //On récupère les infos de l'adversaire
        $adversaireID = $partie->getJoueur2(); //Ici, on récupère son ID
        $adversaire = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $adversaireID]); //Et ici, ses infos en fonction de son ID

        $partieFinie = $partie->getPartieFinie();

        //On récupère les jetons
        $jetonJ1 = json_decode($partie->getJetonJ1());
        $jetonJ2 = json_decode($partie->getJetonJ2());

        //On récupère l'ID de la carte choisie par l'adversaire
        $carteMonopole1 = $request->request->get('carteMonopole');
        $carteMonopole1 = intval($carteMonopole1);

        //On récupère l'id de la partie
        $idPartie = $request->request->get('id');
        $idPartie = intval($idPartie); //Changer "2" en 2


        if($user->getId() == $joueur1->getId()){
            $adversaireID = $joueur2->getId();
            $adversaire = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $adversaireID]);

            //On récupère le terrain de l'utilisateur
            $UserPartie = $this->getDoctrine()->getRepository("App:Partie")->findOneBy(['id_j1' => $user, 'id' => $idPartie]);
            $terrainJ1 = $UserPartie->getTerrainJ1();
            $terrainJ1Monopole = $UserPartie->getTerrainJ1()->monopole;
        } elseif($user->getId() == $joueur2->getId()){
            $adversaireID = $joueur1->getId();
            $adversaire = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $adversaireID]);

            //On récupère le terrain de l'utilisateur
            $UserPartie = $this->getDoctrine()->getRepository("App:Partie")->findOneBy(['id_j2' => $joueur2->getId(), 'id' => $idPartie]);
            $terrainJ2 = $UserPartie->getTerrainJ2();
            $terrainJ2Monopole = $UserPartie->getTerrainJ2()->monopole;
        } else {
        }

        $tActions = array(); //On récupère les actions pour le monopole
        if($joueur1->getId() == $userTour){ //Si c'est le tour du j1, on récupère les actions du j2 et vice versa
            $tActions = $partie->getJ2Actions();
        } else if($adversaire->getId()){
            $tActions = $partie->getJ1Actions();
        } else{
            echo 'Erreur';
        }
        $etatMonopole = $tActions->monopole->etat;
        $monopoleBDD = $tActions->monopole->carte;
        $tMonopole = $tActions->monopole->carte;

        //On enregistre dans la BDD
        if($joueur1->getId() == $userTour){
            //On supprime la carte choisie par le user
            unset($tMonopole[array_search($carteMonopole1, $tMonopole)]); //On supprime la carte du tableau
            $tActions->monopole->carte = $tMonopole; //On MAJ le tableau des actions
            $terrainJ1->monopole = $carteMonopole1; //On MAJ le terrain du joueur

            $jetonJ1->monopoleRecu = $carteMonopole1;

            $tMonopole = array_values($tMonopole);

            $jetonJ2->monopoleGarde1 = $tMonopole[0];
            $jetonJ2->monopoleGarde2 = $tMonopole[1];

            $tActions->monopole->carte = array_values($tActions->monopole->carte);

            $partie->setJ2Actions(json_encode($tActions)); //on MAJ la pioche
            $partie->setTerrainJ1(json_encode($terrainJ1));
            $partie->setJetonJ1(json_encode($jetonJ1));
            $partie->setJetonJ2(json_encode($jetonJ2));
            $partieFinie += 1;
            $partie->setPartieFinie($partieFinie);

        } else if($adversaire->getId()){
            //On supprime la carte choisie par le user
            unset($tMonopole[array_search($carteMonopole1, $tMonopole)]); //On supprime la carte du tableau
            $tActions->monopole->carte = $tMonopole; //On MAJ le tableau des actions
            $terrainJ2->monopole = $carteMonopole1; //On MAJ le terrain du joueur

            $jetonJ2->monopoleRecu = $carteMonopole1; //On MAJ le jetonJ2 du user qui a choisit une carte

            $tMonopole = array_values($tMonopole);

            $jetonJ1->monopoleGarde1 = $tMonopole[0];
            $jetonJ1->monopoleGarde2 = $tMonopole[1];

            $tActions->monopole->carte = array_values($tActions->monopole->carte);

            $partie->setJ1Actions(json_encode($tActions)); //on MAJ la pioche
            $partie->setTerrainJ2(json_encode($terrainJ2));
            $partie->setJetonJ2(json_encode($jetonJ2));
            $partie->setJetonJ1(json_encode($jetonJ1));
            $partieFinie += 1;
            $partie->setPartieFinie($partieFinie);
        } else{
            echo 'Erreur';
        }

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        //---------------------------------------------------------FIN ACTION MONOPOLE -------------------------------------------------------------------------

        return $this->redirectToRoute('afficher_partie', ['id' => $partie->getId(), 'adversaire'=>$adversaire]);

        //On la stocke dans le tableau de joueur2 (ou dans qq chose d'autre)
        //On laisse la main au joueur2, qui vient de choisir sa carte
    }

    /**
     * @Route("/actionNegociation/{id}", name="action_negociation")
     */
    public function actionNegociation(Partie $partie, Request $request)
    {
        //On récupère l'id du joueur1
        $joueur1 = $partie->getJoueur1();

        //On récupère l'id du joueur2
        $joueur2 = $partie->getJoueur2();

        //On récupère le user connecté
        $user = $this->getUser();

        //On récupère l'id de l'utilisateur du tour en cours
        $userTour = intval($partie->getPartieTour());
        $userInTour = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $userTour]); //Et ici, ses infos en fonction de son ID

        //On récupère les infos de l'adversaire
        $adversaireID = $partie->getJoueur2(); //Ici, on récupère son ID
        $adversaire = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $adversaireID]); //Et ici, ses infos en fonction de son ID

        $partieFinie = $partie->getPartieFinie();

        //On récupère les jetons
        $jetonJ1 = json_decode($partie->getJetonJ1());
        $jetonJ2 = json_decode($partie->getJetonJ2());

        //---------------------------------------------------------DEBUT ACTION NEGOCIATION -------------------------------------------------------------------------

        //On récupère l'ID des 4 cartes choisies
        $carteNegociation1 = $request->request->get('carteNegociation1');
        $carteNegociation2 = $request->request->get('carteNegociation2');
        $carteNegociation3 = $request->request->get('carteNegociation3');
        $carteNegociation4 = $request->request->get('carteNegociation4');

        $Paire1 = array($carteNegociation1, $carteNegociation2);
        $Paire2 = array($carteNegociation3, $carteNegociation4);

        $tMain = array(); //On récupère la main
        if ($joueur1->getId() == $userTour) {
            $tMain = $partie->getJ1Main();
        } else if ($adversaire->getId()) {
            $tMain = $partie->getJ2Main();
        } else {
            echo 'Erreur';
        }

        $tActions = array(); //On récupère les actions
        if ($joueur1->getId() == $userTour) {
            $tActions = $partie->getJ1Actions();
        } else if ($adversaire->getId()) {
            $tActions = $partie->getJ2Actions();
        } else {
            echo 'Erreur';
        }

        if ($tActions->negociation->etat != 1) {
            //On supprime les 2 cartes de la main
            unset($tMain[array_search($carteNegociation1, $tMain)]);
            unset($tMain[array_search($carteNegociation2, $tMain)]);
            unset($tMain[array_search($carteNegociation3, $tMain)]);
            unset($tMain[array_search($carteNegociation4, $tMain)]);

            $tMain = array_values($tMain); //on reconstruit les clefs du tableau
            sort($tMain);

            $carteNegociation = array('Paire1'=>array(), 'Paire2'=>array()); //tableau stockant les ID des 4 cartes défaussées
            $carteNegociation['Paire1'][0] = $carteNegociation1;
            $carteNegociation['Paire1'][1] = $carteNegociation2;
            $carteNegociation['Paire2'][0] = $carteNegociation3;
            $carteNegociation['Paire2'][1] = $carteNegociation4;

            //On stock les 3 cartes dans la BDD. L'étape d'après est de les proposer à l'adversaire. --> Si $tActions->monopole->etat != 1 && !empty($tActions->monopole->carte), on donne le choix des 3 cartes.
            //Lorsque celui-ci en aura choisit une, on la stock dans sa table et on laisse les 2 autres dans la table du joueur1.
            //Une fois ceci fait, on passe l'état de l'action à 1 et le joueur2 garde la main (il faut donc rafraichir pour la pioche, ou activer la pioche au moment de la sélection de la carte)

            $tActions->negociation->etat = 1;
            $tActions->negociation->carte->paire1 = $Paire1;
            $tActions->negociation->carte->paire2 = $Paire2;
            //dump($tActions);

            //On enregistre dans la BDD
            if ($joueur1->getId() == $userTour) {
                $partie->setJ1Main(json_encode($tMain)); //on MAJ la main du joueur
                $partie->setJ1Actions(json_encode($tActions)); //on MAJ la pioche
                $partieFinie+=1;
                $partie->setPartieFinie($partieFinie);
            } else if ($adversaire->getId() == $userTour) {
                $partie->setJ2Main(json_encode($tMain)); //on MAJ la main de l'adversaire
                $partie->setJ2Actions(json_encode($tActions)); //on MAJ la pioche
                $partieFinie+=1;
                $partie->setPartieFinie($partieFinie);
            } else {
                echo 'Erreur';
            }

            $etatNegociation = $tActions->negociation->etat;
            $carteNegociationBDD = $tActions->negociation->carte;

            //dump($carteNegociationBDD);

            $cartePioche = $partie->getPioche(); //On récupère la pioche
            $carte_piochee = array_pop($cartePioche); //On retire une carte de la pioche
            //Pioche en fonction de l'utilisateur du tour en cours. Lorsqu'il termine son action, on distribue une carte à l'adversaire.
            if ($userTour == $joueur1->getId()) { //Si c'est le tour du joueur1
                //ON LUI DISTRIBUE UNE CARTE DE LA PIOCHE
                $main = $partie->getJ2Main(); //1 - on récupère sa main actuelle
                if ($carte_piochee != null) {
                    $main[] = $carte_piochee;
                }
                $partie->setPartieTour($joueur2->getId());
                $partie->setJ2Main(json_encode($main)); //on MAJ la main du joueur
                $partie->setPioche(json_encode($cartePioche)); //on MAJ la pioche
            } else {
                $main = $partie->getJ1Main();
                if ($carte_piochee != null) {
                    $main[] = $carte_piochee;
                }
                $partie->setPartieTour($joueur1->getId());
                $partie->setJ1Main(json_encode($main)); //on MAJ la main du joueur
                $partie->setPioche(json_encode($cartePioche)); //on MAJ la pioche
            }

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->redirectToRoute('afficher_partie', ['id' => $partie->getId(), 'adversaire' => $adversaire, 'etatNegociation' => $etatNegociation, 'carteNegociationBDD' => $carteNegociationBDD]);
        } else {
            return $this->redirectToRoute('afficher_partie', ['id' => $partie->getId(), 'adversaire' => $adversaire]);
        }
    }

    /**
     * @Route("/validNegociation/{id}", name="valid_negociation")
     */
    public function validNegociation(Partie $partie, Request $request)
    {
        //On récupère l'id du joueur1
        $joueur1 = $partie->getJoueur1();

        //On récupère l'id du joueur2
        $joueur2 = $partie->getJoueur2();

        //On récupère le user connecté
        $user = $this->getUser();

        //On récupère l'id de l'utilisateur du tour en cours
        $userTour = intval($partie->getPartieTour());
        $userInTour = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $userTour]); //Et ici, ses infos en fonction de son ID

        //On récupère les infos de l'adversaire
        $adversaireID = $partie->getJoueur2(); //Ici, on récupère son ID
        $adversaire = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $adversaireID]); //Et ici, ses infos en fonction de son ID

        $partieFinie = $partie->getPartieFinie();


        //On récupère les jetons
        $jetonJ1 = json_decode($partie->getJetonJ1());
        $jetonJ2 = json_decode($partie->getJetonJ2());

        //On récupère l'ID de la carte choisie par l'adversaire
        $carteNegociation = $request->request->get('carteNegociation');

        //On récupère l'id de la partie
        $idPartie = $request->request->get('id');
        $idPartie = intval($idPartie); //Changer "2" en 2

        if($user->getId() == $joueur1->getId()){
            $adversaireID = $joueur2->getId();
            $adversaire = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $adversaireID]);

            //On récupère le terrain de l'utilisateur
            $UserPartie = $this->getDoctrine()->getRepository("App:Partie")->findOneBy(['id_j1' => $user, 'id' => $idPartie]);
            $terrainJ1 = $UserPartie->getTerrainJ1();
            $terrainJ1Negociation = $UserPartie->getTerrainJ1()->negociation;
        } elseif($user->getId() == $joueur2->getId()){
            $adversaireID = $joueur1->getId();
            $adversaire = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $adversaireID]);

            //On récupère le terrain de l'utilisateur
            $UserPartie = $this->getDoctrine()->getRepository("App:Partie")->findOneBy(['id_j2' => $joueur2->getId(), 'id' => $idPartie]);
            $terrainJ2 = $UserPartie->getTerrainJ2();
            $terrainJ2Negociation = $UserPartie->getTerrainJ2()->negociation;
        } else {
        }

        $tActions = array(); //On récupère les actions pour le monopole
        if($joueur1->getId() == $userTour){ //Si c'est le tour du j1, on récupère les actions du j2 et vice versa
            $tActions = $partie->getJ2Actions();
        } else if($adversaire->getId()){
            $tActions = $partie->getJ1Actions();
        } else{
            echo 'Erreur';
        }
        $etatMonopole = $tActions->negociation->etat;
        $monopoleBDD = $tActions->negociation->carte;
        $tNegociation = $tActions->negociation->carte;

        //On enregistre dans la BDD
        if($joueur1->getId() == $userTour){
            //On supprime la paire choisie par le user
            //On récupère les 2 cartes de la paire restante

            $paireRestante = array();
            foreach ($tNegociation as $paire){
                $paireRestante = $paire;
            }

            $terrainJ1->negociation = $tNegociation->$carteNegociation; //On MAJ le terrain du joueur

            //dump($tNegociation->$carteNegociation);

            $jetonJ1->negociationRecu1 = $tNegociation->$carteNegociation[0];
            $jetonJ1->negociationRecu2 = $tNegociation->$carteNegociation[1];

            if($tNegociation->paire1 != ""){
                $paireQuiReste = $tNegociation->paire1;
            } elseif($tNegociation->paire2 != ""){
                $paireQuiReste = $tNegociation->paire2;
            }

            $jetonJ2->negociationGarde1 = $paireQuiReste[0];
            $jetonJ2->negociationGarde2 = $paireQuiReste[1];

            $tNegociation->$carteNegociation = ""; //On supprime la carte du tableau
            $tActions->negociation->carte = $tNegociation; //On MAJ le tableau des actions

            $partie->setJ2Actions(json_encode($tActions)); //on MAJ la pioche
            $partie->setTerrainJ1(json_encode($terrainJ1));
            $partie->setJetonJ1(json_encode($jetonJ1));
            $partie->setJetonJ2(json_encode($jetonJ2));

            $partieFinie += 1;
            $partie->setPartieFinie($partieFinie);
        } else if($adversaire->getId()){
            //On supprime la carte choisie par le user

            //On récupère les 2 cartes de la paire restante
            $paireRestante = array();
            foreach ($tNegociation as $paire){
                $paireRestante = $paire;
            }

            $tActions->negociation->carte = $tNegociation; //On MAJ le tableau des actions
            $terrainJ2->negociation = $tNegociation->$carteNegociation; //On MAJ le terrain du joueur

            $jetonJ2->negociationRecu1 = $tNegociation->$carteNegociation[0];
            $jetonJ2->negociationRecu2 = $tNegociation->$carteNegociation[1];

            $tNegociation->$carteNegociation = ""; //On supprime la carte du tableau

            if($tNegociation->paire1 != ""){
                $paireQuiReste = $tNegociation->paire1;
            } elseif($tNegociation->paire2 != ""){
                $paireQuiReste = $tNegociation->paire2;
            }

            $jetonJ1->negociationGarde1 = $paireQuiReste[0];
            $jetonJ1->negociationGarde2 = $paireQuiReste[1];

            $partie->setJ1Actions(json_encode($tActions)); //on MAJ la pioche
            $partie->setTerrainJ2(json_encode($terrainJ2));
            $partie->setJetonJ2(json_encode($jetonJ2));
            $partie->setJetonJ1(json_encode($jetonJ1));
            $partieFinie += 1;
            $partie->setPartieFinie($partieFinie);
        } else{
            echo 'Erreur';
        }

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        //---------------------------------------------------------FIN ACTION NEGOCIATION -------------------------------------------------------------------------

        return $this->redirectToRoute('afficher_partie', ['id' => $partie->getId(), 'adversaire'=>$adversaire]);

        //On la stocke dans le tableau de joueur2 (ou dans qq chose d'autre)
        //On laisse la main au joueur2, qui vient de choisir sa carte
    }

    /**
     * @Route("/finmanche/{id}", name="fin_manche")
     */
    public function finDeManche(Partie $partie)
    {
        //On récupère le user connecté
        $user = $this->getUser();

        //On récupère l'id de l'utilisateur du tour en cours
        $userTour = intval($partie->getPartieTour());

        //On récupère l'id du joueur1
        $joueur1 = $partie->getJoueur1();

        //On récupère l'id du joueur2
        $joueur2 = $partie->getJoueur2();

        //On récupère l'id de la partie
        $idPartie = $partie->getId();

        //On récupère le terrain de points
        $finiJ1 = json_decode($partie->getFiniJ1());
        $finiJ2 = json_decode($partie->getFiniJ2());

        if($user->getId() == $joueur1->getId()){
            $adversaireID = $joueur2->getId();
            $adversaire = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $adversaireID]);

            //On récupère le terrain de l'utilisateur
            $UserPartie = $this->getDoctrine()->getRepository("App:Partie")->findOneBy(['id_j1' => $user, 'id' => $idPartie]);
            $terrainJ1 = $UserPartie->getTerrainJ1();
            $terrainJ2 = $UserPartie->getTerrainJ2();
        } elseif($user->getId() == $joueur2->getId()){
            $adversaireID = $joueur1->getId();
            $adversaire = $this->getDoctrine()->getRepository("App:User")->findOneBy(['id' => $adversaireID]);

            //On récupère le terrain de l'utilisateur
            $UserPartie = $this->getDoctrine()->getRepository("App:Partie")->findOneBy(['id_j2' => $joueur2->getId(), 'id' => $idPartie]);
            $terrainJ1 = $UserPartie->getTerrainJ2();
            $terrainJ2 = $UserPartie->getTerrainJ2();
        } else {
        }

        //On récupère les actions
        $tActions = array();
        if ($joueur1->getId() == $userTour) {
            $tActionsJ1 = $partie->getJ1Actions();
            $tActionsJ2 = $partie->getJ2Actions();
        } else if ($adversaire->getId()) {
            $tActionsJ1 = $partie->getJ1Actions();
            $tActionsJ2 = $partie->getJ2Actions();
        } else {
            echo 'Erreur';
        }

        $etatObj1J1A1 = $finiJ1->obj1->etatA1;
        $etatObj2J1A1 = $finiJ1->obj2->etatA1;
        $etatObj3J1A1 = $finiJ1->obj3->etatA1;
        $etatObj4J1A1 = $finiJ1->obj4->etatA1;
        $etatObj5J1A1 = $finiJ1->obj5->etatA1;
        $etatObj6J1A1 = $finiJ1->obj6->etatA1;
        $etatObj7J1A1 = $finiJ1->obj7->etatA1;

        $etatObj1J2A1 = $finiJ2->obj1->etatA1;
        $etatObj2J2A1 = $finiJ2->obj2->etatA1;
        $etatObj3J2A1 = $finiJ2->obj3->etatA1;
        $etatObj4J2A1 = $finiJ2->obj4->etatA1;
        $etatObj5J2A1 = $finiJ2->obj5->etatA1;
        $etatObj6J2A1 = $finiJ2->obj6->etatA1;
        $etatObj7J2A1 = $finiJ2->obj7->etatA1;


        $etatObj1J1A3R = $finiJ1->obj1->etatA3recu;
        $etatObj2J1A3R = $finiJ1->obj2->etatA3recu;
        $etatObj3J1A3R = $finiJ1->obj3->etatA3recu;
        $etatObj4J1A3R = $finiJ1->obj4->etatA3recu;
        $etatObj5J1A3R = $finiJ1->obj5->etatA3recu;
        $etatObj6J1A3R = $finiJ1->obj6->etatA3recu;
        $etatObj7J1A3R = $finiJ1->obj7->etatA3recu;

        $etatObj1J2A3R = $finiJ2->obj1->etatA3recu;
        $etatObj2J2A3R = $finiJ2->obj2->etatA3recu;
        $etatObj3J2A3R = $finiJ2->obj3->etatA3recu;
        $etatObj4J2A3R = $finiJ2->obj4->etatA3recu;
        $etatObj5J2A3R = $finiJ2->obj5->etatA3recu;
        $etatObj6J2A3R = $finiJ2->obj6->etatA3recu;
        $etatObj7J2A3R = $finiJ2->obj7->etatA3recu;

        //Action3 - carte 1 gardée
        $etatObj1J1A3G1 = $finiJ1->obj1->etatA3garde1;
        $etatObj2J1A3G1 = $finiJ1->obj2->etatA3garde1;
        $etatObj3J1A3G1 = $finiJ1->obj3->etatA3garde1;
        $etatObj4J1A3G1 = $finiJ1->obj4->etatA3garde1;
        $etatObj5J1A3G1 = $finiJ1->obj5->etatA3garde1;
        $etatObj6J1A3G1 = $finiJ1->obj6->etatA3garde1;
        $etatObj7J1A3G1 = $finiJ1->obj7->etatA3garde1;

        $etatObj1J2A3G1 = $finiJ2->obj1->etatA3garde1;
        $etatObj2J2A3G1 = $finiJ2->obj2->etatA3garde1;
        $etatObj3J2A3G1 = $finiJ2->obj3->etatA3garde1;
        $etatObj4J2A3G1 = $finiJ2->obj4->etatA3garde1;
        $etatObj5J2A3G1 = $finiJ2->obj5->etatA3garde1;
        $etatObj6J2A3G1 = $finiJ2->obj6->etatA3garde1;
        $etatObj7J2A3G1 = $finiJ2->obj7->etatA3garde1;

        //Action3 - carte 2 gardée
        $etatObj1J1A3G2 = $finiJ1->obj1->etatA3garde2;
        $etatObj2J1A3G2 = $finiJ1->obj2->etatA3garde2;
        $etatObj3J1A3G2 = $finiJ1->obj3->etatA3garde2;
        $etatObj4J1A3G2 = $finiJ1->obj4->etatA3garde2;
        $etatObj5J1A3G2 = $finiJ1->obj5->etatA3garde2;
        $etatObj6J1A3G2 = $finiJ1->obj6->etatA3garde2;
        $etatObj7J1A3G2 = $finiJ1->obj7->etatA3garde2;

        $etatObj1J2A3G2 = $finiJ2->obj1->etatA3garde2;
        $etatObj2J2A3G2 = $finiJ2->obj2->etatA3garde2;
        $etatObj3J2A3G2 = $finiJ2->obj3->etatA3garde2;
        $etatObj4J2A3G2 = $finiJ2->obj4->etatA3garde2;
        $etatObj5J2A3G2 = $finiJ2->obj5->etatA3garde2;
        $etatObj6J2A3G2 = $finiJ2->obj6->etatA3garde2;
        $etatObj7J2A3G2 = $finiJ2->obj7->etatA3garde2;


        $etatObj1J1A4R1 = $finiJ1->obj1->etatA4recu1;
        $etatObj2J1A4R1 = $finiJ1->obj2->etatA4recu1;
        $etatObj3J1A4R1 = $finiJ1->obj3->etatA4recu1;
        $etatObj4J1A4R1 = $finiJ1->obj4->etatA4recu1;
        $etatObj5J1A4R1 = $finiJ1->obj5->etatA4recu1;
        $etatObj6J1A4R1 = $finiJ1->obj6->etatA4recu1;
        $etatObj7J1A4R1 = $finiJ1->obj7->etatA4recu1;

        $etatObj1J1A4R2 = $finiJ1->obj1->etatA4recu2;
        $etatObj2J1A4R2 = $finiJ1->obj2->etatA4recu2;
        $etatObj3J1A4R2 = $finiJ1->obj3->etatA4recu2;
        $etatObj4J1A4R2 = $finiJ1->obj4->etatA4recu2;
        $etatObj5J1A4R2 = $finiJ1->obj5->etatA4recu2;
        $etatObj6J1A4R2 = $finiJ1->obj6->etatA4recu2;
        $etatObj7J1A4R2 = $finiJ1->obj7->etatA4recu2;


        $etatObj1J2A4R1 = $finiJ2->obj1->etatA4recu1;
        $etatObj2J2A4R1 = $finiJ2->obj2->etatA4recu1;
        $etatObj3J2A4R1 = $finiJ2->obj3->etatA4recu1;
        $etatObj4J2A4R1 = $finiJ2->obj4->etatA4recu1;
        $etatObj5J2A4R1 = $finiJ2->obj5->etatA4recu1;
        $etatObj6J2A4R1 = $finiJ2->obj6->etatA4recu1;
        $etatObj7J2A4R1 = $finiJ2->obj7->etatA4recu1;

        $etatObj1J2A4R2 = $finiJ2->obj1->etatA4recu2;
        $etatObj2J2A4R2 = $finiJ2->obj2->etatA4recu2;
        $etatObj3J2A4R2 = $finiJ2->obj3->etatA4recu2;
        $etatObj4J2A4R2 = $finiJ2->obj4->etatA4recu2;
        $etatObj5J2A4R2 = $finiJ2->obj5->etatA4recu2;
        $etatObj6J2A4R2 = $finiJ2->obj6->etatA4recu2;
        $etatObj7J2A4R2 = $finiJ2->obj7->etatA4recu2;


        $etatObj1J1A4G1 = $finiJ1->obj1->etatA4garde1;
        $etatObj2J1A4G1 = $finiJ1->obj2->etatA4garde1;
        $etatObj3J1A4G1 = $finiJ1->obj3->etatA4garde1;
        $etatObj4J1A4G1 = $finiJ1->obj4->etatA4garde1;
        $etatObj5J1A4G1 = $finiJ1->obj5->etatA4garde1;
        $etatObj6J1A4G1 = $finiJ1->obj6->etatA4garde1;
        $etatObj7J1A4G1 = $finiJ1->obj7->etatA4garde1;

        $etatObj1J1A4G2 = $finiJ1->obj1->etatA4garde2;
        $etatObj2J1A4G2 = $finiJ1->obj2->etatA4garde2;
        $etatObj3J1A4G2 = $finiJ1->obj3->etatA4garde2;
        $etatObj4J1A4G2 = $finiJ1->obj4->etatA4garde2;
        $etatObj5J1A4G2 = $finiJ1->obj5->etatA4garde2;
        $etatObj6J1A4G2 = $finiJ1->obj6->etatA4garde2;
        $etatObj7J1A4G2 = $finiJ1->obj7->etatA4garde2;


        $etatObj1J2A4G1 = $finiJ1->obj1->etatA4garde1;
        $etatObj2J2A4G1 = $finiJ1->obj2->etatA4garde1;
        $etatObj3J2A4G1 = $finiJ1->obj3->etatA4garde1;
        $etatObj4J2A4G1 = $finiJ1->obj4->etatA4garde1;
        $etatObj5J2A4G1 = $finiJ1->obj5->etatA4garde1;
        $etatObj6J2A4G1 = $finiJ1->obj6->etatA4garde1;
        $etatObj7J2A4G1 = $finiJ1->obj7->etatA4garde1;

        $etatObj1J2A4G2 = $finiJ1->obj1->etatA4garde2;
        $etatObj2J2A4G2 = $finiJ1->obj2->etatA4garde2;
        $etatObj3J2A4G2 = $finiJ1->obj3->etatA4garde2;
        $etatObj4J2A4G2 = $finiJ1->obj4->etatA4garde2;
        $etatObj5J2A4G2 = $finiJ1->obj5->etatA4garde2;
        $etatObj6J2A4G2 = $finiJ1->obj6->etatA4garde2;
        $etatObj7J2A4G2 = $finiJ1->obj7->etatA4garde2;


        //On récupère partieGagne pour savoir si elle est déjà finie ou non
        $partieGagne = $partie->getPartieGagne();

        if($partieGagne == 1){
        } else {
            //Pacte J1
            if ($etatObj1J1A1 != 1) {
                if ($terrainJ1->pacte == '1' or $terrainJ1->pacte == '2') {
                    $finiJ1->obj1->etatA1 = 1;
                    $finiJ1->obj1->points += 2;
                }
            }
            if ($etatObj2J1A1 != 1) {
                if ($terrainJ1->pacte == '3' or $terrainJ1->pacte == '4') {
                    $finiJ1->obj2->etatA1 = 1;
                    $finiJ1->obj2->points += 2;
                }
            }
            if ($etatObj3J1A1 != 1) {
                if ($terrainJ1->pacte == '5' or $terrainJ1->pacte == '6') {
                    $finiJ1->obj3->etatA1 = 1;
                    $finiJ1->obj3->points += 2;
                }
            }
            if ($etatObj4J1A1 != 1) {
                if ($terrainJ1->pacte == '7' or $terrainJ1->pacte == '8' or $terrainJ1->pacte == '9') {
                    $finiJ1->obj4->etatA1 = 1;
                    $finiJ1->obj4->points += 3;
                }
            }
            if ($etatObj5J1A1 != 1) {
                if ($terrainJ1->pacte == '10' or $terrainJ1->pacte == '11' or $terrainJ1->pacte == '12') {
                    $finiJ1->obj5->etatA1 = 1;
                    $finiJ1->obj5->points += 3;
                }
            }
            if ($etatObj6J1A1 != 1) {
                if ($terrainJ1->pacte == '13' or $terrainJ1->pacte == '14' or $terrainJ1->pacte == '15' or $terrainJ1->pacte == '16') {
                    $finiJ1->obj6->etatA1 = 1;
                    $finiJ1->obj6->points += 4;
                }
            }
            if ($etatObj7J1A1 != 1) {
                if ($terrainJ1->pacte == '17' or $terrainJ1->pacte == '18' or $terrainJ1->pacte == '19' or $terrainJ1->pacte == '20' or $terrainJ1->pacte == '21') {
                    $finiJ1->obj7->etatA1 = 1;
                    $finiJ1->obj7->points += 5;
                }
            }

            //Pacte J2
            if ($etatObj1J2A1 != 1) {
                if ($terrainJ2->pacte == '1' or $terrainJ2->pacte == '2') {
                    $finiJ2->obj1->etatA1 = 1;
                    $finiJ2->obj1->points += 2;
                }
            }
            if ($etatObj2J2A1 != 1) {
                if ($terrainJ2->pacte == '3' or $terrainJ2->pacte == '4') {
                    $finiJ2->obj2->etatA1 = 1;
                    $finiJ2->obj2->points += 2;
                }
            }
            if ($etatObj3J2A1 != 1) {
                if ($terrainJ2->pacte == '5' or $terrainJ2->pacte == '6') {
                    $finiJ2->obj3->etatA1 = 1;
                    $finiJ2->obj3->points += 2;
                }
            }
            if ($etatObj4J2A1 != 1) {
                if ($terrainJ2->pacte == '7' or $terrainJ2->pacte == '8' or $terrainJ2->pacte == '9') {
                    $finiJ2->obj4->etatA1 = 1;
                    $finiJ2->obj4->points += 3;
                }
            }
            if ($etatObj5J2A1 != 1) {
                if ($terrainJ2->pacte == '10' or $terrainJ2->pacte == '11' or $terrainJ2->pacte == '12') {
                    $finiJ2->obj5->etatA1 = 1;
                    $finiJ2->obj5->points += 3;
                }
            }
            if ($etatObj6J2A1 != 1) {
                if ($terrainJ2->pacte == '13' or $terrainJ2->pacte == '14' or $terrainJ2->pacte == '15' or $terrainJ2->pacte == '16') {
                    $finiJ2->obj6->etatA1 = 1;
                    $finiJ2->obj6->points += 4;
                }
            }
            if ($etatObj7J2A1 != 1) {
                if ($terrainJ2->pacte == '17' or $terrainJ2->pacte == '18' or $terrainJ2->pacte == '19' or $terrainJ2->pacte == '20' or $terrainJ2->pacte == '21') {
                    $finiJ2->obj7->etatA1 = 1;
                    $finiJ2->obj7->points += 5;
                }
            }

            //Monopole reçu J1
            if ($etatObj1J1A3R != 1) {
                if ($terrainJ1->monopole == '1' or $terrainJ1->monopole == '2') {
                    $finiJ1->obj1->etatA3recu = 1;
                    $finiJ1->obj1->points += 2;
                }
            }
            if ($etatObj2J1A3R != 1) {
                if ($terrainJ1->monopole == '3' or $terrainJ1->monopole == '4') {
                    $finiJ1->obj2->etatA3recu = 1;
                    $finiJ1->obj2->points += 2;
                }
            }
            if ($etatObj3J1A3R != 1) {
                if ($terrainJ1->monopole == '5' or $terrainJ1->monopole == '6') {
                    $finiJ1->obj3->etatA3recu = 1;
                    $finiJ1->obj3->points += 2;
                }
            }
            if ($etatObj4J1A3R != 1) {
                if ($terrainJ1->monopole == '7' or $terrainJ1->monopole == '8' or $terrainJ1->monopole == '9') {
                    $finiJ1->obj4->etatA3recu = 1;
                    $finiJ1->obj4->points += 3;
                }
            }
            if ($etatObj5J1A3R != 1) {
                if ($terrainJ1->monopole == '10' or $terrainJ1->monopole == '11' or $terrainJ1->monopole == '12') {
                    $finiJ1->obj5->etatA3recu = 1;
                    $finiJ1->obj5->points += 3;
                }
            }
            if ($etatObj6J1A3R != 1) {
                if ($terrainJ1->monopole == '13' or $terrainJ1->monopole == '14' or $terrainJ1->monopole == '15' or $terrainJ1->monopole == '16') {
                    $finiJ1->obj6->etatA3recu = 1;
                    $finiJ1->obj6->points += 4;
                }
            }
            if ($etatObj7J1A3R != 1) {
                if ($terrainJ1->monopole == '17' or $terrainJ1->monopole == '18' or $terrainJ1->monopole == '19' or $terrainJ1->monopole == '20' or $terrainJ1->monopole == '21') {
                    $finiJ1->obj7->etatA3recu = 1;
                    $finiJ1->obj7->points += 5;
                }
            }

            //Monopole reçu J2
            if ($etatObj1J2A3R != 1) {
                if ($terrainJ2->monopole == '1' or $terrainJ2->monopole == '2') {
                    $finiJ2->obj1->etatA3recu = 1;
                    $finiJ2->obj1->points += 2;
                }
            }
            if ($etatObj2J2A3R != 1) {
                if ($terrainJ2->monopole == '3' or $terrainJ2->monopole == '4') {
                    $finiJ2->obj2->etatA3recu = 1;
                    $finiJ2->obj2->points += 2;
                }
            }
            if ($etatObj3J2A3R != 1) {
                if ($terrainJ2->monopole == '5' or $terrainJ2->monopole == '6') {
                    $finiJ2->obj3->etatA3recu = 1;
                    $finiJ2->obj3->points += 2;
                }
            }
            if ($etatObj4J2A3R != 1) {
                if ($terrainJ2->monopole == '7' or $terrainJ2->monopole == '8' or $terrainJ2->monopole == '9') {
                    $finiJ2->obj4->etatA3recu = 1;
                    $finiJ2->obj4->points += 3;
                }
            }
            if ($etatObj5J2A3R != 1) {
                if ($terrainJ2->monopole == '10' or $terrainJ2->monopole == '11' or $terrainJ2->monopole == '12') {
                    $finiJ2->obj5->etatA3recu = 1;
                    $finiJ2->obj5->points += 3;
                }
            }
            if ($etatObj6J2A3R != 1) {
                if ($terrainJ2->monopole == '13' or $terrainJ2->monopole == '14' or $terrainJ2->monopole == '15' or $terrainJ2->monopole == '16') {
                    $finiJ2->obj6->etatA3recu = 1;
                    $finiJ2->obj6->points += 4;
                }
            }
            if ($etatObj7J2A3R != 1) {
                if ($terrainJ2->monopole == '17' or $terrainJ2->monopole == '18' or $terrainJ2->monopole == '19' or $terrainJ2->monopole == '20' or $terrainJ2->monopole == '21') {
                    $finiJ2->obj7->etatA3recu = 1;
                    $finiJ2->obj7->points += 5;
                }
            }

            //dump($tActionsJ2->monopole->carte);

            //Monopole gardé J2 - carte 1
            if ($etatObj1J2A3G1 != 1) {
                if ($tActionsJ2->monopole->carte[0] == '1' or $tActionsJ2->monopole->carte[0] == '2') {
                    $finiJ2->obj1->etatA3garde1 = 1;
                    $finiJ2->obj1->points += 2;
                }
            }
            if ($etatObj2J2A3G1 != 1) {
                if ($tActionsJ2->monopole->carte[0] == '3' or $tActionsJ2->monopole->carte[0] == '4') {
                    $finiJ2->obj2->etatA3garde1 = 1;
                    $finiJ2->obj2->points += 2;
                }
            }
            if ($etatObj3J2A3G1 != 1) {
                if ($tActionsJ2->monopole->carte[0] == '5' or $tActionsJ2->monopole->carte[0] == '6') {
                    $finiJ2->obj3->etatA3garde1 = 1;
                    $finiJ2->obj3->points += 2;
                }
            }
            if ($etatObj4J2A3G1 != 1) {
                if ($tActionsJ2->monopole->carte[0] == '7' or $tActionsJ2->monopole->carte[0] == '8' or $tActionsJ2->monopole->carte[0] == '9') {
                    $finiJ2->obj4->etatA3garde1 = 1;
                    $finiJ2->obj4->points += 3;
                }
            }
            if ($etatObj5J2A3G1 != 1) {
                if ($tActionsJ2->monopole->carte[0] == '10' or $tActionsJ2->monopole->carte[0] == '11' or $tActionsJ2->monopole->carte[0] == '12') {
                    $finiJ2->obj5->etatA3garde1 = 1;
                    $finiJ2->obj5->points += 3;
                }
            }
            if ($etatObj6J2A3G1 != 1) {
                if ($tActionsJ2->monopole->carte[0] == '13' or $tActionsJ2->monopole->carte[0] == '14' or $tActionsJ2->monopole->carte[0] == '15' or $tActionsJ2->monopole->carte[0] == '16') {
                    $finiJ2->obj6->etatA3garde1 = 1;
                    $finiJ2->obj6->points += 4;
                }
            }
            if ($etatObj7J2A3G1 != 1) {
                if ($tActionsJ2->monopole->carte[0] == '17' or $tActionsJ2->monopole->carte[0] == '18' or $tActionsJ2->monopole->carte[0] == '19' or $tActionsJ2->monopole->carte[0] == '20' or $tActionsJ2->monopole->carte[0] == '21') {
                    $finiJ2->obj7->etatA3garde1 = 1;
                    $finiJ2->obj7->points += 5;
                }
            }

            //Monopole gardé J2 - carte 2
            if ($etatObj1J2A3G2 != 1) {
                if ($tActionsJ2->monopole->carte[1] == '1' or $tActionsJ2->monopole->carte[1] == '2') {
                    $finiJ2->obj1->etatA3garde2 = 1;
                    $finiJ2->obj1->points += 2;
                }
            }
            if ($etatObj2J2A3G2 != 1) {
                if ($tActionsJ2->monopole->carte[1] == '3' or $tActionsJ2->monopole->carte[1] == '4') {
                    $finiJ2->obj2->etatA3garde2 = 1;
                    $finiJ2->obj2->points += 2;
                }
            }
            if ($etatObj3J2A3G2 != 1) {
                if ($tActionsJ2->monopole->carte[1] == '5' or $tActionsJ2->monopole->carte[1] == '6') {
                    $finiJ2->obj3->etatA3garde2 = 1;
                    $finiJ2->obj3->points += 2;
                }
            }
            if ($etatObj4J2A3G2 != 1) {
                if ($tActionsJ2->monopole->carte[1] == '7' or $tActionsJ2->monopole->carte[1] == '8' or $tActionsJ2->monopole->carte[1] == '9') {
                    $finiJ2->obj4->etatA3garde2 = 1;
                    $finiJ2->obj4->points += 3;
                }
            }
            if ($etatObj5J2A3G2 != 1) {
                if ($tActionsJ2->monopole->carte[1] == '10' or $tActionsJ2->monopole->carte[1] == '11' or $tActionsJ2->monopole->carte[1] == '12') {
                    $finiJ2->obj5->etatA3garde2 = 1;
                    $finiJ2->obj5->points += 3;
                }
            }
            if ($etatObj6J2A3G2 != 1) {
                if ($tActionsJ2->monopole->carte[1] == '13' or $tActionsJ2->monopole->carte[1] == '14' or $tActionsJ2->monopole->carte[1] == '15' or $tActionsJ2->monopole->carte[1] == '16') {
                    $finiJ2->obj6->etatA3garde2 = 1;
                    $finiJ2->obj6->points += 4;
                }
            }
            if ($etatObj7J2A3G2 != 1) {
                if ($tActionsJ2->monopole->carte[1] == '17' or $tActionsJ2->monopole->carte[1] == '18' or $tActionsJ2->monopole->carte[1] == '19' or $tActionsJ2->monopole->carte[1] == '20' or $tActionsJ2->monopole->carte[1] == '21') {
                    $finiJ2->obj7->etatA3garde2 = 1;
                    $finiJ2->obj7->points += 5;
                }
            }

            //Negociation reçu J1 - carte 1
            if ($etatObj1J1A4R1 != 1) {
                if ($terrainJ1->negociation[0] == '1' or $terrainJ1->negociation[0] == '2') {
                    $finiJ1->obj1->etatA4recu1 = 1;
                    $finiJ1->obj1->points += 2;
                }
            }
            if ($etatObj2J1A4R1 != 1) {
                if ($terrainJ1->negociation[0] == '3' or $terrainJ1->negociation[0] == '4') {
                    $finiJ1->obj2->etatA4recu1 = 1;
                    $finiJ1->obj2->points += 2;
                }
            }
            if ($etatObj3J1A4R1 != 1) {
                if ($terrainJ1->negociation[0] == '5' or $terrainJ1->negociation[0] == '6') {
                    $finiJ1->obj3->etatA4recu1 = 1;
                    $finiJ1->obj3->points += 2;
                }
            }
            if ($etatObj4J1A4R1 != 1) {
                if ($terrainJ1->negociation[0] == '7' or $terrainJ1->negociation[0] == '8' or $terrainJ1->negociation[0] == '9') {
                    $finiJ1->obj4->etatA4recu1 = 1;
                    $finiJ1->obj4->points += 3;
                }
            }
            if ($etatObj5J1A4R1 != 1) {
                if ($terrainJ1->negociation[0] == '10' or $terrainJ1->negociation[0] == '11' or $terrainJ1->negociation[0] == '12') {
                    $finiJ1->obj5->etatA4recu1 = 1;
                    $finiJ1->obj5->points += 3;
                }
            }
            if ($etatObj6J1A4R1 != 1) {
                if ($terrainJ1->negociation[0] == '13' or $terrainJ1->negociation[0] == '14' or $terrainJ1->negociation[0] == '15' or $terrainJ1->negociation[0] == '16') {
                    $finiJ1->obj6->etatA4recu1 = 1;
                    $finiJ1->obj6->points += 4;
                }
            }
            if ($etatObj7J1A4R1 != 1) {
                if ($terrainJ1->negociation[0] == '17' or $terrainJ1->negociation[0] == '18' or $terrainJ1->negociation[0] == '19' or $terrainJ1->negociation[0] == '20' or $terrainJ1->negociation[0] == '21') {
                    $finiJ1->obj7->etatA4recu1 = 1;
                    $finiJ1->obj7->points += 5;
                }
            }

            //Negociation reçu J1 - carte 2
            if ($etatObj1J1A4R2 != 1) {
                if ($terrainJ1->negociation[1] == '1' or $terrainJ1->negociation[1] == '2') {
                    $finiJ1->obj1->etatA4recu2 = 1;
                    $finiJ1->obj1->points += 2;
                }
            }
            if ($etatObj2J1A4R2 != 1) {
                if ($terrainJ1->negociation[1] == '3' or $terrainJ1->negociation[1] == '4') {
                    $finiJ1->obj2->etatA4recu2 = 1;
                    $finiJ1->obj2->points += 2;
                }
            }
            if ($etatObj3J1A4R2 != 1) {
                if ($terrainJ1->negociation[1] == '5' or $terrainJ1->negociation[1] == '6') {
                    $finiJ1->obj3->etatA4recu2 = 1;
                    $finiJ1->obj3->points += 2;
                }
            }
            if ($etatObj4J1A4R2 != 1) {
                if ($terrainJ1->negociation[1] == '7' or $terrainJ1->negociation[1] == '8' or $terrainJ1->negociation[1] == '9') {
                    $finiJ1->obj4->etatA4recu2 = 1;
                    $finiJ1->obj4->points += 3;
                }
            }
            if ($etatObj5J1A4R2 != 1) {
                if ($terrainJ1->negociation[1] == '10' or $terrainJ1->negociation[1] == '11' or $terrainJ1->negociation[1] == '12') {
                    $finiJ1->obj5->etatA4recu2 = 1;
                    $finiJ1->obj5->points += 3;
                }
            }
            if ($etatObj6J1A4R2 != 1) {
                if ($terrainJ1->negociation[1] == '13' or $terrainJ1->negociation[1] == '14' or $terrainJ1->negociation[1] == '15' or $terrainJ1->negociation[1] == '16') {
                    $finiJ1->obj6->etatA4recu2 = 1;
                    $finiJ1->obj6->points += 4;
                }
            }
            if ($etatObj7J1A4R2 != 1) {
                if ($terrainJ1->negociation[1] == '17' or $terrainJ1->negociation[1] == '18' or $terrainJ1->negociation[1] == '19' or $terrainJ1->negociation[1] == '20' or $terrainJ1->negociation[1] == '21') {
                    $finiJ1->obj7->etatA4recu2 = 1;
                    $finiJ1->obj7->points += 5;
                }
            }

            //Negociation reçu J2 - carte 1
            if ($etatObj1J2A4R1 != 1) {
                if ($terrainJ2->negociation[0] == '1' or $terrainJ2->negociation[0] == '2') {
                    $finiJ2->obj1->etatA4recu1 = 1;
                    $finiJ2->obj1->points += 2;
                }
            }
            if ($etatObj2J2A4R1 != 1) {
                if ($terrainJ2->negociation[0] == '3' or $terrainJ2->negociation[0] == '4') {
                    $finiJ2->obj2->etatA4recu1 = 1;
                    $finiJ2->obj2->points += 2;
                }
            }
            if ($etatObj3J2A4R1 != 1) {
                if ($terrainJ2->negociation[0] == '5' or $terrainJ2->negociation[0] == '6') {
                    $finiJ2->obj3->etatA4recu1 = 1;
                    $finiJ2->obj3->points += 2;
                }
            }
            if ($etatObj4J2A4R1 != 1) {
                if ($terrainJ2->negociation[0] == '7' or $terrainJ2->negociation[0] == '8' or $terrainJ2->negociation[0] == '9') {
                    $finiJ2->obj4->etatA4recu1 = 1;
                    $finiJ2->obj4->points += 3;
                }
            }
            if ($etatObj5J2A4R1 != 1) {
                if ($terrainJ2->negociation[0] == '10' or $terrainJ2->negociation[0] == '11' or $terrainJ2->negociation[0] == '12') {
                    $finiJ2->obj5->etatA4recu1 = 1;
                    $finiJ2->obj5->points += 3;
                }
            }
            if ($etatObj6J2A4R1 != 1) {
                if ($terrainJ2->negociation[0] == '13' or $terrainJ2->negociation[0] == '14' or $terrainJ2->negociation[0] == '15' or $terrainJ2->negociation[0] == '16') {
                    $finiJ2->obj6->etatA4recu1 = 1;
                    $finiJ2->obj6->points += 4;
                }
            }
            if ($etatObj7J2A4R1 != 1) {
                if ($terrainJ2->negociation[0] == '17' or $terrainJ2->negociation[0] == '18' or $terrainJ2->negociation[0] == '19' or $terrainJ2->negociation[0] == '20' or $terrainJ2->negociation[0] == '21') {
                    $finiJ2->obj7->etatA4recu1 = 1;
                    $finiJ2->obj7->points += 5;
                }
            }

            //Negociation reçu J2 - carte 2
            if ($etatObj1J2A4R2 != 1) {
                if ($terrainJ2->negociation[1] == '1' or $terrainJ2->negociation[1] == '2') {
                    $finiJ2->obj1->etatA4recu2 = 1;
                    $finiJ2->obj1->points += 2;
                }
            }
            if ($etatObj2J2A4R2 != 1) {
                if ($terrainJ2->negociation[1] == '3' or $terrainJ2->negociation[1] == '4') {
                    $finiJ2->obj2->etatA4recu2 = 1;
                    $finiJ2->obj2->points += 2;
                }
            }
            if ($etatObj3J2A4R2 != 1) {
                if ($terrainJ2->negociation[1] == '5' or $terrainJ2->negociation[1] == '6') {
                    $finiJ2->obj3->etatA4recu2 = 1;
                    $finiJ2->obj3->points += 2;
                }
            }
            if ($etatObj4J2A4R2 != 1) {
                if ($terrainJ2->negociation[1] == '7' or $terrainJ2->negociation[1] == '8' or $terrainJ2->negociation[1] == '9') {
                    $finiJ2->obj4->etatA4recu2 = 1;
                    $finiJ2->obj4->points += 3;
                }
            }
            if ($etatObj5J2A4R2 != 1) {
                if ($terrainJ2->negociation[1] == '10' or $terrainJ2->negociation[1] == '11' or $terrainJ2->negociation[1] == '12') {
                    $finiJ2->obj5->etatA4recu2 = 1;
                    $finiJ2->obj5->points += 3;
                }
            }
            if ($etatObj6J2A4R2 != 1) {
                if ($terrainJ2->negociation[1] == '13' or $terrainJ2->negociation[1] == '14' or $terrainJ2->negociation[1] == '15' or $terrainJ2->negociation[1] == '16') {
                    $finiJ2->obj6->etatA4recu2 = 1;
                    $finiJ2->obj6->points += 4;
                }
            }
            if ($etatObj7J2A4R2 != 1) {
                if ($terrainJ2->negociation[1] == '17' or $terrainJ2->negociation[1] == '18' or $terrainJ2->negociation[1] == '19' or $terrainJ2->negociation[1] == '20' or $terrainJ2->negociation[1] == '21') {
                    $finiJ2->obj7->etatA4recu2 = 1;
                    $finiJ2->obj7->points += 5;
                }
            }

            //Negociation gardé J1 - carte 1
            if ($etatObj1J1A4G1 != 1) {
                if (empty($tActionsJ1->negociation->carte->paire1)) {
                    if ($tActionsJ1->negociation->carte->paire2[0] == '1' or $tActionsJ1->negociation->carte->paire2[0] == '2') {
                        $finiJ1->obj1->etatA4garde1 = 1;
                        $finiJ1->obj1->points += 2;
                    }
                } elseif (empty($tActionsJ1->negociation->carte->paire2)) {
                    if ($tActionsJ1->negociation->carte->paire1[0] == '1' or $tActionsJ1->negociation->carte->paire1[0] == '2') {
                        $finiJ1->obj1->etatA4garde1 = 1;
                        $finiJ1->obj1->points += 2;
                    }
                }
            }
            if ($etatObj2J1A4G1 != 1) {
                if (empty($tActionsJ1->negociation->carte->paire1)) {
                    if ($tActionsJ1->negociation->carte->paire2[0] == '3' or $tActionsJ1->negociation->carte->paire2[0] == '4') {
                        $finiJ1->obj2->etatA4garde1 = 1;
                        $finiJ1->obj2->points += 2;
                    }
                } elseif (empty($tActionsJ1->negociation->carte->paire2)) {
                    if ($tActionsJ1->negociation->carte->paire1[0] == '3' or $tActionsJ1->negociation->carte->paire1[0] == '4') {
                        $finiJ1->obj2->etatA4garde1 = 1;
                        $finiJ1->obj2->points += 2;
                    }
                }
            }
            if ($etatObj3J1A4G1 != 1) {
                if (empty($tActionsJ1->negociation->carte->paire1)) {
                    if ($tActionsJ1->negociation->carte->paire2[0] == '5' or $tActionsJ1->negociation->carte->paire2[0] == '6') {
                        $finiJ1->obj3->etatA4garde1 = 1;
                        $finiJ1->obj3->points += 2;
                    }
                } elseif (empty($tActionsJ1->negociation->carte->paire2)) {
                    if ($tActionsJ1->negociation->carte->paire1[0] == '5' or $tActionsJ1->negociation->carte->paire1[0] == '6') {
                        $finiJ1->obj3->etatA4garde1 = 1;
                        $finiJ1->obj3->points += 2;
                    }
                }
            }
            if ($etatObj4J1A4G1 != 1) {
                if (empty($tActionsJ1->negociation->carte->paire1)) {
                    if ($tActionsJ1->negociation->carte->paire2[0] == '7' or $tActionsJ1->negociation->carte->paire2[0] == '8' or $tActionsJ1->negociation->carte->paire2[0] == '9') {
                        $finiJ1->obj4->etatA4garde1 = 1;
                        $finiJ1->obj4->points += 3;
                    }
                } elseif (empty($tActionsJ1->negociation->carte->paire2)) {
                    if ($tActionsJ1->negociation->carte->paire1[0] == '7' or $tActionsJ1->negociation->carte->paire1[0] == '8' or $tActionsJ1->negociation->carte->paire1[0] == '9') {
                        $finiJ1->obj4->etatA4garde1 = 1;
                        $finiJ1->obj4->points += 3;
                    }
                }
            }
            if ($etatObj5J1A4G1 != 1) {
                if (empty($tActionsJ1->negociation->carte->paire1)) {
                    if ($tActionsJ1->negociation->carte->paire2[0] == '10' or $tActionsJ1->negociation->carte->paire2[0] == '11' or $tActionsJ1->negociation->carte->paire2[0] == '12') {
                        $finiJ1->obj5->etatA4garde1 = 1;
                        $finiJ1->obj5->points += 3;
                    }
                } elseif (empty($tActionsJ1->negociation->carte->paire2)) {
                    if ($tActionsJ1->negociation->carte->paire1[0] == '10' or $tActionsJ1->negociation->carte->paire1[0] == '11' or $tActionsJ1->negociation->carte->paire1[0] == '12') {
                        $finiJ1->obj5->etatA4garde1 = 1;
                        $finiJ1->obj5->points += 3;
                    }
                }
            }
            if ($etatObj6J1A4G1 != 1) {
                if (empty($tActionsJ1->negociation->carte->paire1)) {
                    if ($tActionsJ1->negociation->carte->paire2[0] == '13' or $tActionsJ1->negociation->carte->paire2[0] == '14' or $tActionsJ1->negociation->carte->paire2[0] == '15' or $tActionsJ1->negociation->carte->paire2[0] == '16') {
                        $finiJ1->obj6->etatA4garde1 = 1;
                        $finiJ1->obj6->points += 4;
                    }
                } elseif (empty($tActionsJ1->negociation->carte->paire2)) {
                    if ($tActionsJ1->negociation->carte->paire1[0] == '13' or $tActionsJ1->negociation->carte->paire1[0] == '14' or $tActionsJ1->negociation->carte->paire1[0] == '15' or $tActionsJ1->negociation->carte->paire1[0] == '16') {
                        $finiJ1->obj6->etatA4garde1 = 1;
                        $finiJ1->obj6->points += 4;
                    }
                }
            }
            if ($etatObj7J1A4G1 != 1) {
                if (empty($tActionsJ1->negociation->carte->paire1)) {
                    if ($tActionsJ1->negociation->carte->paire2[0] == '17' or $tActionsJ1->negociation->carte->paire2[0] == '18' or $tActionsJ1->negociation->carte->paire2[0] == '19' or $tActionsJ1->negociation->carte->paire2[0] == '20' or $tActionsJ1->negociation->carte->paire2[0] == '21') {
                        $finiJ1->obj7->etatA4garde1 = 1;
                        $finiJ1->obj7->points += 5;
                    }
                } elseif (empty($tActionsJ1->negociation->carte->paire2)) {
                    if ($tActionsJ1->negociation->carte->paire1[0] == '17' or $tActionsJ1->negociation->carte->paire1[0] == '18' or $tActionsJ1->negociation->carte->paire1[0] == '19' or $tActionsJ1->negociation->carte->paire1[0] == '20' or $tActionsJ1->negociation->carte->paire1[0] == '21') {
                        $finiJ1->obj7->etatA4garde1 = 1;
                        $finiJ1->obj7->points += 5;
                    }
                }
            }

            //Negociation gardé J1 - carte 2
            if ($etatObj1J1A4G2 != 1) {
                if (empty($tActionsJ1->negociation->carte->paire1)) {
                    if ($tActionsJ1->negociation->carte->paire2[1] == '1' or $tActionsJ1->negociation->carte->paire2[1] == '2') {
                        $finiJ1->obj1->etatA4garde2 = 1;
                        $finiJ1->obj1->points += 2;
                    }
                } elseif (empty($tActionsJ1->negociation->carte->paire2)) {
                    if ($tActionsJ1->negociation->carte->paire1[1] == '1' or $tActionsJ1->negociation->carte->paire1[1] == '2') {
                        $finiJ1->obj1->etatA4garde2 = 1;
                        $finiJ1->obj1->points += 2;
                    }
                }
            }
            if ($etatObj2J1A4G2 != 1) {
                if (empty($tActionsJ1->negociation->carte->paire1)) {
                    if ($tActionsJ1->negociation->carte->paire2[1] == '3' or $tActionsJ1->negociation->carte->paire2[1] == '4') {
                        $finiJ1->obj2->etatA4garde2 = 1;
                        $finiJ1->obj2->points += 2;
                    }
                } elseif (empty($tActionsJ1->negociation->carte->paire2)) {
                    if ($tActionsJ1->negociation->carte->paire1[1] == '3' or $tActionsJ1->negociation->carte->paire1[1] == '4') {
                        $finiJ1->obj2->etatA4garde2 = 1;
                        $finiJ1->obj2->points += 2;
                    }
                }
            }
            if ($etatObj3J1A4G2 != 1) {
                if (empty($tActionsJ1->negociation->carte->paire1)) {
                    if ($tActionsJ1->negociation->carte->paire2[1] == '5' or $tActionsJ1->negociation->carte->paire2[1] == '6') {
                        $finiJ1->obj3->etatA4garde2 = 1;
                        $finiJ1->obj3->points += 2;
                    }
                } elseif (empty($tActionsJ1->negociation->carte->paire2)) {
                    if ($tActionsJ1->negociation->carte->paire1[1] == '5' or $tActionsJ1->negociation->carte->paire1[1] == '6') {
                        $finiJ1->obj3->etatA4garde2 = 1;
                        $finiJ1->obj3->points += 2;
                    }
                }
            }
            if ($etatObj4J1A4G2 != 1) {
                if (empty($tActionsJ1->negociation->carte->paire1)) {
                    if ($tActionsJ1->negociation->carte->paire2[1] == '7' or $tActionsJ1->negociation->carte->paire2[1] == '8' or $tActionsJ1->negociation->carte->paire2[1] == '9') {
                        $finiJ1->obj4->etatA4garde2 = 1;
                        $finiJ1->obj4->points += 3;
                    }
                } elseif (empty($tActionsJ1->negociation->carte->paire2)) {
                    if ($tActionsJ1->negociation->carte->paire1[1] == '7' or $tActionsJ1->negociation->carte->paire1[1] == '8' or $tActionsJ1->negociation->carte->paire1[1] == '9') {
                        $finiJ1->obj4->etatA4garde2 = 1;
                        $finiJ1->obj4->points += 3;
                    }
                }
            }
            if ($etatObj5J1A4G2 != 1) {
                if (empty($tActionsJ1->negociation->carte->paire1)) {
                    if ($tActionsJ1->negociation->carte->paire2[1] == '10' or $tActionsJ1->negociation->carte->paire2[1] == '11' or $tActionsJ1->negociation->carte->paire2[1] == '12') {
                        $finiJ1->obj5->etatA4garde2 = 1;
                        $finiJ1->obj5->points += 3;
                    }
                } elseif (empty($tActionsJ1->negociation->carte->paire2)) {
                    if ($tActionsJ1->negociation->carte->paire1[1] == '10' or $tActionsJ1->negociation->carte->paire1[1] == '11' or $tActionsJ1->negociation->carte->paire1[1] == '12') {
                        $finiJ1->obj5->etatA4garde2 = 1;
                        $finiJ1->obj5->points += 3;
                    }
                }
            }
            if ($etatObj6J1A4G2 != 1) {
                if (empty($tActionsJ1->negociation->carte->paire1)) {
                    if ($tActionsJ1->negociation->carte->paire2[1] == '13' or $tActionsJ1->negociation->carte->paire2[1] == '14' or $tActionsJ1->negociation->carte->paire2[1] == '15' or $tActionsJ1->negociation->carte->paire2[1] == '16') {
                        $finiJ1->obj6->etatA4garde2 = 1;
                        $finiJ1->obj6->points += 4;
                    }
                } elseif (empty($tActionsJ1->negociation->carte->paire2)) {
                    if ($tActionsJ1->negociation->carte->paire1[1] == '13' or $tActionsJ1->negociation->carte->paire1[1] == '14' or $tActionsJ1->negociation->carte->paire1[1] == '15' or $tActionsJ1->negociation->carte->paire1[1] == '16') {
                        $finiJ1->obj6->etatA4garde2 = 1;
                        $finiJ1->obj6->points += 4;
                    }
                }
            }
            if ($etatObj7J1A4G2 != 1) {
                if (empty($tActionsJ1->negociation->carte->paire1)) {
                    if ($tActionsJ1->negociation->carte->paire2[1] == '17' or $tActionsJ1->negociation->carte->paire2[1] == '18' or $tActionsJ1->negociation->carte->paire2[1] == '19' or $tActionsJ1->negociation->carte->paire2[1] == '20' or $tActionsJ1->negociation->carte->paire2[1] == '21') {
                        $finiJ1->obj7->etatA4garde2 = 1;
                        $finiJ1->obj7->points += 5;
                    }
                } elseif (empty($tActionsJ1->negociation->carte->paire2)) {
                    if ($tActionsJ1->negociation->carte->paire1[1] == '17' or $tActionsJ1->negociation->carte->paire1[1] == '18' or $tActionsJ1->negociation->carte->paire1[1] == '19' or $tActionsJ1->negociation->carte->paire1[1] == '20' or $tActionsJ1->negociation->carte->paire1[1] == '21') {
                        $finiJ1->obj7->etatA4garde2 = 1;
                        $finiJ1->obj7->points += 5;
                    }
                }
            }

            //Negociation gardé J2 - carte 1
            if ($etatObj1J2A4G1 != 1) {
                if (empty($tActionsJ2->negociation->carte->paire1)) {
                    if ($tActionsJ2->negociation->carte->paire2[0] == '1' or $tActionsJ2->negociation->carte->paire2[0] == '2') {
                        $finiJ2->obj1->etatA4garde1 = 1;
                        $finiJ2->obj1->points += 2;
                    }
                } elseif (empty($tActionsJ2->negociation->carte->paire2)) {
                    if ($tActionsJ2->negociation->carte->paire1[0] == '1' or $tActionsJ2->negociation->carte->paire1[0] == '2') {
                        $finiJ2->obj1->etatA4garde1 = 1;
                        $finiJ2->obj1->points += 2;
                    }
                }
            }
            if ($etatObj2J2A4G1 != 1) {
                if (empty($tActionsJ2->negociation->carte->paire1)) {
                    if ($tActionsJ2->negociation->carte->paire2[0] == '3' or $tActionsJ2->negociation->carte->paire2[0] == '4') {
                        $finiJ2->obj2->etatA4garde1 = 1;
                        $finiJ2->obj2->points += 2;
                    }
                } elseif (empty($tActionsJ2->negociation->carte->paire2)) {
                    if ($tActionsJ2->negociation->carte->paire1[0] == '3' or $tActionsJ2->negociation->carte->paire1[0] == '4') {
                        $finiJ2->obj2->etatA4garde1 = 1;
                        $finiJ2->obj2->points += 2;
                    }
                }
            }
            if ($etatObj3J2A4G1 != 1) {
                if (empty($tActionsJ2->negociation->carte->paire1)) {
                    if ($tActionsJ2->negociation->carte->paire2[0] == '5' or $tActionsJ2->negociation->carte->paire2[0] == '6') {
                        $finiJ2->obj3->etatA4garde1 = 1;
                        $finiJ2->obj3->points += 2;
                    }
                } elseif (empty($tActionsJ2->negociation->carte->paire2)) {
                    if ($tActionsJ2->negociation->carte->paire1[0] == '5' or $tActionsJ2->negociation->carte->paire1[0] == '6') {
                        $finiJ2->obj3->etatA4garde1 = 1;
                        $finiJ2->obj3->points += 2;
                    }
                }
            }
            if ($etatObj4J2A4G1 != 1) {
                if (empty($tActionsJ2->negociation->carte->paire1)) {
                    if ($tActionsJ2->negociation->carte->paire2[0] == '7' or $tActionsJ2->negociation->carte->paire2[0] == '8' or $tActionsJ2->negociation->carte->paire2[0] == '9') {
                        $finiJ2->obj4->etatA4garde1 = 1;
                        $finiJ2->obj4->points += 3;
                    }
                } elseif (empty($tActionsJ2->negociation->carte->paire2)) {
                    if ($tActionsJ2->negociation->carte->paire1[0] == '7' or $tActionsJ2->negociation->carte->paire1[0] == '8' or $tActionsJ2->negociation->carte->paire1[0] == '9') {
                        $finiJ2->obj4->etatA4garde1 = 1;
                        $finiJ2->obj4->points += 3;
                    }
                }
            }
            if ($etatObj5J2A4G1 != 1) {
                if (empty($tActionsJ2->negociation->carte->paire1)) {
                    if ($tActionsJ2->negociation->carte->paire2[0] == '10' or $tActionsJ2->negociation->carte->paire2[0] == '11' or $tActionsJ2->negociation->carte->paire2[0] == '12') {
                        $finiJ2->obj5->etatA4garde1 = 1;
                        $finiJ2->obj5->points += 3;
                    }
                } elseif (empty($tActionsJ2->negociation->carte->paire2)) {
                    if ($tActionsJ2->negociation->carte->paire1[0] == '10' or $tActionsJ2->negociation->carte->paire1[0] == '11' or $tActionsJ2->negociation->carte->paire1[0] == '12') {
                        $finiJ2->obj5->etatA4garde1 = 1;
                        $finiJ2->obj5->points += 3;
                    }
                }
            }
            if ($etatObj6J2A4G1 != 1) {
                if (empty($tActionsJ2->negociation->carte->paire1)) {
                    if ($tActionsJ2->negociation->carte->paire2[0] == '13' or $tActionsJ2->negociation->carte->paire2[0] == '14' or $tActionsJ2->negociation->carte->paire2[0] == '15' or $tActionsJ2->negociation->carte->paire2[0] == '16') {
                        $finiJ2->obj6->etatA4garde1 = 1;
                        $finiJ2->obj6->points += 4;
                    }
                } elseif (empty($tActionsJ2->negociation->carte->paire2)) {
                    if ($tActionsJ2->negociation->carte->paire1[0] == '13' or $tActionsJ2->negociation->carte->paire1[0] == '14' or $tActionsJ2->negociation->carte->paire1[0] == '15' or $tActionsJ2->negociation->carte->paire1[0] == '16') {
                        $finiJ2->obj6->etatA4garde1 = 1;
                        $finiJ2->obj6->points += 4;
                    }
                }
            }
            if ($etatObj7J2A4G1 != 1) {
                if (empty($tActionsJ2->negociation->carte->paire1)) {
                    if ($tActionsJ2->negociation->carte->paire2[0] == '17' or $tActionsJ2->negociation->carte->paire2[0] == '18' or $tActionsJ2->negociation->carte->paire2[0] == '19' or $tActionsJ2->negociation->carte->paire2[0] == '20' or $tActionsJ2->negociation->carte->paire2[0] == '21') {
                        $finiJ2->obj7->etatA4garde1 = 1;
                        $finiJ2->obj7->points += 5;
                    }
                } elseif (empty($tActionsJ2->negociation->carte->paire2)) {
                    if ($tActionsJ2->negociation->carte->paire1[0] == '17' or $tActionsJ2->negociation->carte->paire1[0] == '18' or $tActionsJ2->negociation->carte->paire1[0] == '19' or $tActionsJ2->negociation->carte->paire1[0] == '20' or $tActionsJ2->negociation->carte->paire1[0] == '21') {
                        $finiJ2->obj7->etatA4garde1 = 1;
                        $finiJ2->obj7->points += 5;
                    }
                }
            }

            //Negociation gardé J1 - carte 2
            if ($etatObj1J2A4G2 != 1) {
                if (empty($tActionsJ2->negociation->carte->paire1)) {
                    if ($tActionsJ2->negociation->carte->paire2[1] == '1' or $tActionsJ2->negociation->carte->paire2[1] == '2') {
                        $finiJ2->obj1->etatA4garde2 = 1;
                        $finiJ2->obj1->points += 2;
                    }
                } elseif (empty($tActionsJ2->negociation->carte->paire2)) {
                    if ($tActionsJ2->negociation->carte->paire1[1] == '1' or $tActionsJ2->negociation->carte->paire1[1] == '2') {
                        $finiJ2->obj1->etatA4garde2 = 1;
                        $finiJ2->obj1->points += 2;
                    }
                }
            }
            if ($etatObj2J2A4G2 != 1) {
                if (empty($tActionsJ2->negociation->carte->paire1)) {
                    if ($tActionsJ2->negociation->carte->paire2[1] == '3' or $tActionsJ2->negociation->carte->paire2[1] == '4') {
                        $finiJ2->obj2->etatA4garde2 = 1;
                        $finiJ2->obj2->points += 2;
                    }
                } elseif (empty($tActionsJ2->negociation->carte->paire2)) {
                    if ($tActionsJ2->negociation->carte->paire1[1] == '3' or $tActionsJ2->negociation->carte->paire1[1] == '4') {
                        $finiJ2->obj2->etatA4garde2 = 1;
                        $finiJ2->obj2->points += 2;
                    }
                }
            }
            if ($etatObj3J2A4G2 != 1) {
                if (empty($tActionsJ2->negociation->carte->paire1)) {
                    if ($tActionsJ2->negociation->carte->paire2[1] == '5' or $tActionsJ2->negociation->carte->paire2[1] == '6') {
                        $finiJ2->obj3->etatA4garde2 = 1;
                        $finiJ2->obj3->points += 2;
                    }
                } elseif (empty($tActionsJ2->negociation->carte->paire2)) {
                    if ($tActionsJ2->negociation->carte->paire1[1] == '5' or $tActionsJ2->negociation->carte->paire1[1] == '6') {
                        $finiJ2->obj3->etatA4garde2 = 1;
                        $finiJ2->obj3->points += 2;
                    }
                }
            }
            if ($etatObj4J2A4G2 != 1) {
                if (empty($tActionsJ2->negociation->carte->paire1)) {
                    if ($tActionsJ2->negociation->carte->paire2[1] == '7' or $tActionsJ2->negociation->carte->paire2[1] == '8' or $tActionsJ2->negociation->carte->paire2[1] == '9') {
                        $finiJ2->obj4->etatA4garde2 = 1;
                        $finiJ2->obj4->points += 3;
                    }
                } elseif (empty($tActionsJ2->negociation->carte->paire2)) {
                    if ($tActionsJ2->negociation->carte->paire1[1] == '7' or $tActionsJ2->negociation->carte->paire1[1] == '8' or $tActionsJ2->negociation->carte->paire1[1] == '9') {
                        $finiJ2->obj4->etatA4garde2 = 1;
                        $finiJ2->obj4->points += 3;
                    }
                }
            }
            if ($etatObj5J2A4G2 != 1) {
                if (empty($tActionsJ2->negociation->carte->paire1)) {
                    if ($tActionsJ2->negociation->carte->paire2[1] == '10' or $tActionsJ2->negociation->carte->paire2[1] == '11' or $tActionsJ2->negociation->carte->paire2[1] == '12') {
                        $finiJ2->obj5->etatA4garde2 = 1;
                        $finiJ2->obj5->points += 3;
                    }
                } elseif (empty($tActionsJ2->negociation->carte->paire2)) {
                    if ($tActionsJ2->negociation->carte->paire1[1] == '10' or $tActionsJ2->negociation->carte->paire1[1] == '11' or $tActionsJ2->negociation->carte->paire1[1] == '12') {
                        $finiJ2->obj5->etatA4garde2 = 1;
                        $finiJ2->obj5->points += 3;
                    }
                }
            }
            if ($etatObj6J2A4G2 != 1) {
                if (empty($tActionsJ2->negociation->carte->paire1)) {
                    if ($tActionsJ2->negociation->carte->paire2[1] == '13' or $tActionsJ2->negociation->carte->paire2[1] == '14' or $tActionsJ2->negociation->carte->paire2[1] == '15' or $tActionsJ2->negociation->carte->paire2[1] == '16') {
                        $finiJ2->obj6->etatA4garde2 = 1;
                        $finiJ2->obj6->points += 4;
                    }
                } elseif (empty($tActionsJ2->negociation->carte->paire2)) {
                    if ($tActionsJ2->negociation->carte->paire1[1] == '13' or $tActionsJ2->negociation->carte->paire1[1] == '14' or $tActionsJ2->negociation->carte->paire1[1] == '15' or $tActionsJ2->negociation->carte->paire1[1] == '16') {
                        $finiJ2->obj6->etatA4garde2 = 1;
                        $finiJ2->obj6->points += 4;
                    }
                }
            }
            if ($etatObj7J2A4G2 != 1) {
                if (empty($tActionsJ2->negociation->carte->paire1)) {
                    if ($tActionsJ2->negociation->carte->paire2[1] == '17' or $tActionsJ2->negociation->carte->paire2[1] == '18' or $tActionsJ2->negociation->carte->paire2[1] == '19' or $tActionsJ2->negociation->carte->paire2[1] == '20' or $tActionsJ2->negociation->carte->paire2[1] == '21') {
                        $finiJ2->obj7->etatA4garde2 = 1;
                        $finiJ2->obj7->points += 5;
                    }
                } elseif (empty($tActionsJ2->negociation->carte->paire2)) {
                    if ($tActionsJ2->negociation->carte->paire1[1] == '17' or $tActionsJ2->negociation->carte->paire1[1] == '18' or $tActionsJ2->negociation->carte->paire1[1] == '19' or $tActionsJ2->negociation->carte->paire1[1] == '20' or $tActionsJ2->negociation->carte->paire1[1] == '21') {
                        $finiJ2->obj7->etatA4garde2 = 1;
                        $finiJ2->obj7->points += 5;
                    }
                }
            }
        }


        //Manche
        $manchePartie = $partie->getPartieManche();

        //Définition des points finaux
        $scoreJ1 = $partie->getScoreJ1();
        $scoreJ2 = $partie->getScoreJ2();

        //On récupère partieGagne pour savoir si elle est déjà finie ou non
        $partieGagne = $partie->getPartieGagne();

        //dump('Score J1: '.$scoreJ1);
        //dump('Score J2: '.$scoreJ2);

        $objJ1 = 0;
        $objJ2 = 0;

        if($partieGagne == 1){

        } else{
            if(($manchePartie == 1 and $scoreJ1 != 0 and $scoreJ2 != 0)) {
            } elseif(($manchePartie == 2 and $scoreJ2 >= 11) or ($manchePartie == 2 and $scoreJ1 >= 11)){
            } elseif(($manchePartie == 3 and $scoreJ2 >= 11) or ($manchePartie == 3 and $scoreJ1 >= 11)){
            } else{
                if($finiJ1->obj1->points > $finiJ2->obj1->points){
                    $scoreJ1+=2;
                    $objJ1 += 1;
                } elseif($finiJ1->obj1->points == $finiJ2->obj1->points){
                    $scoreJ1+=0;
                    $scoreJ2+=0;
                    $objJ1 += 0;
                    $objJ2 += 0;
                }else{
                    $scoreJ2 +=2;
                    $objJ2 += 1;
                }

                if($finiJ1->obj2->points > $finiJ2->obj2->points){
                    $scoreJ1+=2;
                    $objJ1 += 1;
                } elseif($finiJ1->obj2->points == $finiJ2->obj2->points){
                    $scoreJ1+=0;
                    $scoreJ2+=0;
                    $objJ1 += 0;
                    $objJ2 += 0;
                }else{
                    $scoreJ2 +=2;
                    $objJ2 += 1;
                }

                if($finiJ1->obj3->points > $finiJ2->obj3->points){
                    $scoreJ1+=2;
                    $objJ1 += 1;
                } elseif($finiJ1->obj3->points == $finiJ2->obj3->points){
                    $scoreJ1+=0;
                    $scoreJ2+=0;
                    $objJ1 += 0;
                    $objJ2 += 0;
                }else{
                    $scoreJ2 +=2;
                    $objJ2 += 1;
                }

                if($finiJ1->obj4->points > $finiJ2->obj4->points){
                    $scoreJ1+=3;
                    $objJ1 += 1;
                } elseif($finiJ1->obj4->points == $finiJ2->obj4->points){
                    $scoreJ1+=0;
                    $scoreJ2+=0;
                }else{
                    $scoreJ2 +=3;
                    $objJ2 += 1;
                }

                if($finiJ1->obj5->points > $finiJ2->obj5->points){
                    $scoreJ1+=3;
                    $objJ1 += 1;
                } elseif($finiJ1->obj5->points == $finiJ2->obj5->points){
                    $scoreJ1+=0;
                    $scoreJ2+=0;
                    $objJ1 += 0;
                    $objJ2 += 0;
                }else{
                    $scoreJ2 +=3;
                    $objJ2 += 1;
                }

                if($finiJ1->obj6->points > $finiJ2->obj6->points){
                    $scoreJ1+=4;
                    $objJ1 += 1;
                } elseif($finiJ1->obj6->points == $finiJ2->obj6->points){
                    $scoreJ1+=0;
                    $scoreJ2+=0;
                    $objJ1 += 0;
                    $objJ2 += 0;
                }else{
                    $scoreJ2 +=4;
                    $objJ2 += 1;
                }

                if($finiJ1->obj7->points > $finiJ2->obj7->points){
                    $scoreJ1+=5;
                    $objJ1 += 1;
                } elseif($finiJ1->obj7->points == $finiJ2->obj7->points){
                    $scoreJ1+=0;
                    $scoreJ2+=0;
                    $objJ1 += 0;
                    $objJ2 += 0;
                }else{
                    $scoreJ2 +=5;
                    $objJ2 += 1;
                }
            }
        }

        //$scoreJ1 = ($finiJ1->obj1->points) + ($finiJ1->obj2->points) + ($finiJ1->obj3->points) + ($finiJ1->obj4->points) + ($finiJ1->obj5->points) + ($finiJ1->obj6->points) + ($finiJ1->obj7->points);
        //$scoreJ2 = ($finiJ2->obj1->points) + ($finiJ2->obj2->points) + ($finiJ2->obj3->points) + ($finiJ2->obj4->points) + ($finiJ2->obj5->points) + ($finiJ2->obj6->points) + ($finiJ2->obj7->points);

        //dump($scoreJ1);
        //dump($scoreJ2);

        //dump('Manche: '.$manchePartie);
        //dump($scoreJ1);
        //dump($scoreJ2);

        if($partieGagne == 1){
            //Si la partie à déjà été gagnée, on ne fait rien
        } else{
            if($manchePartie == 1 and $partie->getScoreJ1() == 0 and $partie->getScoreJ2() == 0) {
                $partie->setFiniJ1(json_encode($finiJ1));
                $partie->setFiniJ2(json_encode($finiJ2));
                $partie->setScoreJ1($scoreJ1);
                $partie->setScoreJ2($scoreJ2);
            } elseif (($manchePartie == 2 and $scoreJ1 < $scoreJ2) or ($manchePartie == 2 and $scoreJ2 < $scoreJ1)) {
                $partie->setFiniJ1(json_encode($finiJ1));
                $partie->setFiniJ2(json_encode($finiJ2));
                $partie->setScoreJ1($scoreJ1);
                $partie->setScoreJ2($scoreJ2);
            } elseif(($manchePartie == 3 and $scoreJ1 < $scoreJ2) or ($manchePartie == 3 and $scoreJ2 < $scoreJ1)){
                $partie->setFiniJ1(json_encode($finiJ1));
                $partie->setFiniJ2(json_encode($finiJ2));
                $partie->setScoreJ1($scoreJ1);
                $partie->setScoreJ2($scoreJ2);
            }else{}
        }

        $em = $this->getDoctrine()->getManager();
        $em->flush();



        //dump('Score J1: '.$scoreJ1);
        //dump('Score J2: '.$scoreJ2);

        //dump('Nb obj J1: '.$objJ1);
        //dump('Nb obj J2: '.$objJ2);



        if($manchePartie == 1){
            if($scoreJ1 < 11 and $scoreJ2 < 11){
                if($objJ1 >= 4 and $objJ2 < 4){
                    //dump('Le joueur 1 est gagnant ! Il a conquit '.$objJ1.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    //return($this->finPartie($partie));
                    return $this->redirectToRoute('fin_partie', ['id' => $partie->getId(), 'scoreJ1' => $scoreJ1, 'scoreJ2' => $scoreJ2]);
                } elseif($objJ2 >= 4 and $objJ1 < 4){
                    //dump('Le joueur 2 est gagnant ! Il a conquit '.$objJ2.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    return $this->redirectToRoute('fin_partie', ['id' => $partie->getId(), 'scoreJ1' => $scoreJ1, 'scoreJ2' => $scoreJ2]);
                } else{
                    //dump('Aucun joueur n\'a gagné en ayant au moins 11 points');

                    $manche = 2;
                    $partie->setPartieManche($manche);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    return($this->creerPartie2($partie));
                }
            } elseif ($scoreJ1 >= 11 and $scoreJ2 < 11){
                if($objJ1 >= 4 and $objJ2 < 4){
                    //dump('Le joueur 1 est gagnant ! Il a conquit '.$objJ1.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    return $this->redirectToRoute('fin_partie', ['id' => $partie->getId(), 'scoreJ1' => $scoreJ1, 'scoreJ2' => $scoreJ2]);
                } elseif($objJ2 >= 4 and $objJ1 < 4){
                    //dump('Le joueur 2 est gagnant ! Il a conquit '.$objJ2.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    return $this->redirectToRoute('fin_partie');
                } else{}
            } elseif ($scoreJ2 >= 11 and $scoreJ1 < 11){
                if($objJ1 >= 4 and $objJ2 < 4){
                    //dump('Le joueur 1 est gagnant ! Il a conquit '.$objJ1.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    return $this->redirectToRoute('fin_partie', ['id' => $partie->getId(), 'scoreJ1' => $scoreJ1, 'scoreJ2' => $scoreJ2]);
                } elseif($objJ2 >= 4 and $objJ1 < 4){
                    //dump('Le joueur 2 est gagnant ! Il a conquit '.$objJ2.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    return $this->redirectToRoute('fin_partie', ['id' => $partie->getId(), 'scoreJ1' => $scoreJ1, 'scoreJ2' => $scoreJ2]);
                } else{
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                }
            }

        } elseif($manchePartie == 2){
            //Si le J1 a 4 objectfis ou plus de conquis
            if($scoreJ1 < 11 and $scoreJ2 < 11){
                if($objJ1 >= 4 and $objJ2 < 4){
                    //dump('Le joueur 1 est gagnant ! Il a conquit '.$objJ1.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    //return($this->finPartie($partie));
                    return $this->redirectToRoute('fin_partie', ['id' => $partie->getId(), 'scoreJ1' => $scoreJ1, 'scoreJ2' => $scoreJ2]);
                } elseif($objJ2 >= 4 and $objJ1 < 4){
                    //dump('Le joueur 2 est gagnant ! Il a conquit '.$objJ2.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    return $this->redirectToRoute('fin_partie', ['id' => $partie->getId(), 'scoreJ1' => $scoreJ1, 'scoreJ2' => $scoreJ2]);
                } else{
                    //dump('Aucun joueur n\'a gagné en conquérant au moins 4 objectifs');
                    $manche = 3;
                    $partie->setPartieManche($manche);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    return($this->creerPartie2($partie));
                }
            } elseif ($scoreJ1 >= 11 and $scoreJ2 < 11){
                if($objJ1 >= 4 and $objJ2 < 4){
                    //dump('Le joueur 1 est gagnant ! Il a conquit '.$objJ1.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    return $this->redirectToRoute('fin_partie', ['id' => $partie->getId(), 'scoreJ1' => $scoreJ1, 'scoreJ2' => $scoreJ2]);
                } elseif($objJ2 >= 4 and $objJ1 < 4){
                    //dump('Le joueur 2 est gagnant ! Il a conquit '.$objJ2.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    return $this->redirectToRoute('fin_partie');
                } else{
                }
            } elseif ($scoreJ2 >= 11 and $scoreJ1 < 11){
                if($objJ1 >= 4 and $objJ2 < 4){
                    //dump('Le joueur 1 est gagnant ! Il a conquit '.$objJ1.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    return $this->redirectToRoute('fin_partie', ['id' => $partie->getId(), 'scoreJ1' => $scoreJ1, 'scoreJ2' => $scoreJ2]);
                } elseif($objJ2 >= 4 and $objJ1 < 4){
                    //dump('Le joueur 2 est gagnant ! Il a conquit '.$objJ2.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    return $this->redirectToRoute('fin_partie', ['id' => $partie->getId(), 'scoreJ1' => $scoreJ1, 'scoreJ2' => $scoreJ2]);
                } else{
                    $partie->setPartieGagne(1);

                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    return $this->redirectToRoute('logout_etat');
                }
            }
        }   elseif($manchePartie == 3){
            //Si le J1 a 4 objectfis ou plus de conquis
            if($scoreJ1 < 11 and $scoreJ2 < 11){
                if($objJ1 >= 4 and $objJ2 < 4){
                    //dump('Le joueur 1 est gagnant ! Il a conquit '.$objJ1.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    //return($this->finPartie($partie));
                    return $this->redirectToRoute('fin_partie', ['id' => $partie->getId(), 'scoreJ1' => $scoreJ1, 'scoreJ2' => $scoreJ2]);
                } elseif($objJ2 >= 4 and $objJ1 < 4){
                    //dump('Le joueur 2 est gagnant ! Il a conquit '.$objJ2.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    return $this->redirectToRoute('fin_partie', ['id' => $partie->getId(), 'scoreJ1' => $scoreJ1, 'scoreJ2' => $scoreJ2]);
                } else{
                    //On ne fait rien car il ne peut pas y avoir plus de 3 manches dans une partie
                }
            } elseif ($scoreJ1 >= 11 and $scoreJ2 < 11){
                if($objJ1 >= 4 and $objJ2 < 4){
                    //dump('Le joueur 1 est gagnant ! Il a conquit '.$objJ1.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    return $this->redirectToRoute('fin_partie', ['id' => $partie->getId(), 'scoreJ1' => $scoreJ1, 'scoreJ2' => $scoreJ2]);
                } elseif($objJ2 >= 4 and $objJ1 < 4){
                    //dump('Le joueur 2 est gagnant ! Il a conquit '.$objJ2.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    return $this->redirectToRoute('fin_partie');
                } else{
                    echo 'we are here1';
                }
            } elseif ($scoreJ2 >= 11 and $scoreJ1 < 11){
                if($objJ1 >= 4 and $objJ2 < 4){
                    //dump('Le joueur 1 est gagnant ! Il a conquit '.$objJ1.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    return $this->redirectToRoute('fin_partie', ['id' => $partie->getId(), 'scoreJ1' => $scoreJ1, 'scoreJ2' => $scoreJ2]);
                } elseif($objJ2 >= 4 and $objJ1 < 4){
                    //dump('Le joueur 2 est gagnant ! Il a conquit '.$objJ2.' objectifs avec un total de '.$scoreJ2.' points.');
                    $partie->setPartieGagne(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    return $this->redirectToRoute('fin_partie', ['id' => $partie->getId(), 'scoreJ1' => $scoreJ1, 'scoreJ2' => $scoreJ2]);
                } else{
                    $partie->setPartieGagne(1);

                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    return $this->redirectToRoute('logout_etat');
                }
            }
        }

        //Si l'un des scores n'est pas >= 11, il faut passer à la manche 2
        //Donc remettre tout ce qui est actions, terrain, tour,... à 0
        //Refaire une pioche, jeter une carte, refaire les mainJ1 et mainJ2...
        //Mais stocker les scores déjà existants et recreer la partie en passant la manche à 2

        $partie->setFiniJ1(json_encode($finiJ1));
        $partie->setFiniJ2(json_encode($finiJ2));

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        //return $this->redirectToRoute('afficher_partie', ['id' => $partie->getId(), 'scoreJ1' => $scoreJ1, 'scoreJ2' => $scoreJ2]);
    }

    /**
     * @Route("/finpartie/{id}", name="fin_partie")
     */
    public function finPartie(Partie $partie)
    {
        dump('YAAAAAAAY');
        //On récupère le user connecté
        $user = $this->getUser();

        //On récupère l'id de l'utilisateur du tour en cours
        $userTour = intval($partie->getPartieTour());

        //On récupère l'id du joueur1
        $joueur1 = $partie->getJoueur1();

        //On récupère l'id du joueur2
        $joueur2 = $partie->getJoueur2();

        //On récupère l'id de la partie
        $idPartie = $partie->getId();

        return $this->redirectToRoute('nouvelle_partie');
    }

    /**
     * @Route("/creer2", name="creer_parti2")
     */
    public function creerPartie2(Partie $partie)
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) { //Si connecté, on continue le script
            $user = $this->getUser(); //ID de l'utilisateur connecté
            $joueur1 = $partie->getJoueur1();
            $joueur2 = $partie->getJoueur2();

            $idAdversaire = 0;
            if($user->getId() == $joueur1->getId()){
                $idAdversaire = $joueur2->getId();
            } elseif ($user->getId() == $joueur2->getId()){
                $idAdversaire = $joueur1->getId();
            }
            //$user = $this->getDoctrine()->getRepository(User::class)->find(1);
            $adversaire = $this->getDoctrine()->getRepository(User::class)->find($idAdversaire);

            //Récupérer les cartes objets depuis la BDD
            $cartes = $this->getDoctrine()->getRepository(Carte::class)->findAll();
            $tCartes = array();

            //Récupérer les cartes objectifs depuis la BDD
            $objectifs = $this->getDoctrine()->getRepository(Objectifs::class)->findAll();
            $tObjectifs = array();

            //Création des actions de départ
            $pacte = array('etat'=>0, 'carte'=>0);
            $abandon = array('etat'=>0, 'carte'=>array());
            $monopole = array('etat'=>0, 'carte'=>array());
            $negociation = array('etat'=>0, 'carte'=>array('paire1'=>array(), 'paire2'=>array()));// A revoir

            $tAction = array("pacte"=>$pacte, "abandon"=>$abandon, "monopole"=>$monopole, "negociation"=>$negociation);

            $terrainJ1 = array('pacte'=>0, 'monopole'=>0, 'negociation'=>array());

            $terrainJ2 = array('pacte'=>0, 'monopole'=>0, 'negociation'=>array());

            $fini = array('obj1'=> array('etatA1'=>0, 'etatA3recu'=>0, 'etatA3garde1'=>0, 'etatA3garde2'=>0, 'etatA4recu1'=>0, 'etatA4recu2'=>0, 'etatA4garde1'=>0, 'etatA4garde2'=>0, 'points'=>0),
                'obj2'=> array('etatA1'=>0, 'etatA3recu'=>0, 'etatA3garde1'=>0, 'etatA3garde2'=>0, 'etatA4recu1'=>0, 'etatA4recu2'=>0, 'etatA4garde1'=>0, 'etatA4garde2'=>0, 'points'=>0),
                'obj3'=> array('etatA1'=>0, 'etatA3recu'=>0, 'etatA3garde1'=>0, 'etatA3garde2'=>0, 'etatA4recu1'=>0, 'etatA4recu2'=>0, 'etatA4garde1'=>0, 'etatA4garde2'=>0, 'points'=>0),
                'obj4' => array('etatA1'=>0, 'etatA3recu'=>0, 'etatA3garde1'=>0, 'etatA3garde2'=>0, 'etatA4recu1'=>0, 'etatA4recu2'=>0, 'etatA4garde1'=>0, 'etatA4garde2'=>0, 'points'=>0),
                'obj5' => array('etatA1'=>0, 'etatA3recu'=>0, 'etatA3garde1'=>0, 'etatA3garde2'=>0, 'etatA4recu1'=>0, 'etatA4recu2'=>0, 'etatA4garde1'=>0, 'etatA4garde2'=>0, 'points'=>0),
                'obj6' => array('etatA1'=>0, 'etatA3recu'=>0, 'etatA3garde1'=>0, 'etatA3garde2'=>0, 'etatA4recu1'=>0, 'etatA4recu2'=>0, 'etatA4garde1'=>0, 'etatA4garde2'=>0, 'points'=>0),
                'obj7' => array('etatA1'=>0, 'etatA3recu'=>0, 'etatA3garde1'=>0, 'etatA3garde2'=>0, 'etatA4recu1'=>0, 'etatA4recu2'=>0, 'etatA4garde1'=>0, 'etatA4garde2'=>0, 'points'=>0));

            $jetonJ1 = array('pacte'=>0, 'monopoleRecu'=>0, 'monopoleGarde1'=>0, 'monopoleGarde2'=>0, 'negociationRecu1'=>0, 'negociationRecu2'=>0, 'negociationGarde1'=>0, 'negociationGarde2'=>0);
            $jetonJ2 = array('pacte'=>0, 'monopoleRecu'=>0, 'monopoleGarde1'=>0, 'monopoleGarde2'=>0, 'negociationRecu1'=>0, 'negociationRecu2'=>0, 'negociationGarde1'=>0, 'negociationGarde2'=>0);

            //Mélanger les cartes
            foreach ($cartes as $carte) {
                $tCartes[] = $carte->getId();
            }

            shuffle($tCartes); //Mélange le tableau contenant les id

            //Retrait de la première carte
            $cartejetee = array_pop($tCartes);

            //Distribution des cartes aux joueurs
            $tMainJ1 = array();
            for ($i = 0; $i < 6; $i++) {
                $tMainJ1[] = array_pop($tCartes);
            }

            $tMainJ2 = array();
            for ($i = 0; $i < 6; $i++) {
                $tMainJ2[] = array_pop($tCartes);
            }

            //Créer la pioche
            $tPioche = $tCartes; //Sauvegarde des dernières cartes dans la pioche

            //AU LIEU DE RECREER UNE PARTIE, IL FAUT UPDATE L'EXISTANT EN REMETTANT LE TOUT À 0 CAR LÀ, ÇA CRÉÉ UNE NOUVELLE PARTIE ET C'EST PAS TROP CE QUE L'ON VEUT QUOI LOL MDR XD PTDRRRRRRR JPP SA RACE DE MOUETE
            $partie->setCarteJetee($cartejetee);
            $partie->setJ1Main(json_encode($tMainJ1));
            $partie->setJ2Main(json_encode($tMainJ2));
            $partie->setPioche(json_encode($tPioche));
            $idtour = rand(1,20) % 2 ? $user->getId() : $adversaire->getId(); //notation terenaire
            $partie->setPartieTour($idtour);
            $partie->setPartieFinie(1); //Partie finie correspond au nombre de tour. Si == 9, et manche == 1, alors la partie est considéré comme finie
            //$partie->setObjectifs(json_encode($tObjectifs));
            $partie->setObjectifs('[1, 2, 3, 4, 5, 6, 7]'); //Cartes objectifs passées manuellement car MANYTOMANY ne fonctionne pas (dans Carte.php)
            $partie->setJ1Actions(json_encode($tAction)); //A faire avec le MANYTOMANY
            $partie->setJ2Actions(json_encode($tAction)); //A faire avec le MANYTOMANY
            $partie->setTerrainJ1(json_encode($terrainJ1)); //A quoi sert setTerrain ?
            $partie->setTerrainJ2(json_encode($terrainJ2)); //Idem
            $partie->setFiniJ1(json_encode($fini));
            $partie->setFiniJ2(json_encode($fini));
            $partie->setJetonJ1(json_encode($jetonJ1));
            $partie->setJetonJ2(json_encode($jetonJ2));

            //Récupérer le manager de doctrine (connexion BDD)
            $em = $this->getDoctrine()->getManager();

            //Sauvegarde mon objet Partie dans la BDD
            $em->persist($partie);
            $em->flush();

            return $this->redirectToRoute('afficher_partie', ['id' => $partie->getId(), 'adversaire'=>$adversaire]);
        } else{
            return $this->redirectToRoute('index'); //Si non connecté, on redirige vers la page d'accueil
        }
    }
    }
