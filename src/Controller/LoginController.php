<?php

namespace App\Controller;

use App\Entity\Partie;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            if($this->isGranted('ROLE_ADMIN')){
                return $this->redirectToRoute('admin_home');
                return $this->render('admin/index.html.twig'); //A MODIFIER
            }else{
                return $this->redirectToRoute('nouvelle_partie');
            }
        } else {
            return $this->render('base.html.twig');
        }
    }

    /**
     * @Route("/tchat", name="tchat_global")
     */
    public function chatGlobal(){
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user = $this->getUser();
            return $this->render('tchat/tchat.html.twig', ['user'=>$user]);
        } else {
            return $this->render('base.html.twig');
        }
    }

    /**
     * @Route("/admin", name="admin_home")
     */
    public function admin()
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            //On récupère l'utilisateur connecté
            $user = $this->getUser();
            return $this->render('admin.html.twig', ['userConnected' => $user]); //A MODIFIER
        } else {
            return $this->render('base.html.twig');
        }
    }

    /**
     * @Route("/admin/users", name="admin_users")
     */
    public function adminUsers()
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            //On récupère l'utilisateur connecté
            $user = $this->getUser();

            //On récupère les infos de tous les utilisateurs présents dans la BDD
            $Users = $this->getDoctrine()->getRepository("App:User")->findAll();

            $allUsers = array();
            foreach ($Users as $result){ //Permet de ne pas avoir d'admin dans le tableau
                if($result->getRoles() != array('ROLE_ADMIN')){
                    $allUsers[] = $result;
                }
            }

            return $this->render('admin/usersBack.html.twig', ['userConnected' => $user, 'allUsers' => $allUsers]); //A MODIFIER
        } else{
            return $this->render('base.html.twig');
        }
    }

    /**
     * @Route("/admin/parties", name="admin_parties")
     */
    public function adminParties()
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            //On récupère l'utilisateur connecté
            $user = $this->getUser();

            //On récupère les infos de toutes les parties présentes dans la BDD
            $allParties = $this->getDoctrine()->getRepository("App:Partie")->findAll();
            return $this->render('admin/partiesBack.html.twig', ['userConnected' => $user, 'allParties' => $allParties]); //A MODIFIER
        } else{
            return $this->render('base.html.twig');
        }
    }

    /**
     * @Route("/delUser", name="delete_user")
     */
    public function deleteUser(Request $request)
    {
        //On récupère l'id de la partie passé dans le formulaire
        $idPartie = $request->request->get('idUser');

        $user = $this->getDoctrine()->getRepository(User::class)->find($idPartie);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('admin_users');
    }

    /**
     * @Route("/delPartie", name="delete_partie")
     */
    public function deletePartie(Request $request)
    {
        //On récupère l'id de la partie passé dans le formulaire
        $idPartie = $request->request->get('idPartie');

        $partie = $this->getDoctrine()->getRepository(Partie::class)->find($idPartie);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($partie);
        $entityManager->flush();

        return $this->redirectToRoute('admin_parties');
    }
}