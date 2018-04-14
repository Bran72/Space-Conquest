<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="security_login")
     */
    public function login(AuthenticationUtils $helper): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            if($this->isGranted('ROLE_ADMIN')){
                return $this->redirectToRoute('admin_home');
            }else {
                return $this->redirectToRoute('nouvelle_partie');
            }
        } else{
            return $this->render('home/login.html.twig', [
                // dernier username saisi (si il y en a un)
                'last_username' => $helper->getLastUsername(),
                // La derniere erreur de connexion (si il y en a une)
                'error' => $helper->getLastAuthenticationError(),
            ]);
        }
    }

    /**
     * La route pour se deconnecter.
     * MAIS celle ci ne doit jamais être executée car symfony l'interceptera avant.
     *
     * @Route("/logout", name="security_logout")
     */
    public function logout(): void
    {
        throw new \Exception('This should never be reached!');
    }

    /**
     * @Route("/logEtat", name="logout_etat")
     */
    public function logoutEtat(){ //Fonction pour passer l'état du user à 0. MAIS S'IL FERME SON NAVIGATEUR, L'ETAT RESTERA À 1...
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            //Passer $user_etat à 0 lorsqu'il se déconnecte
            $connect = $this->getUser(); //On récupère l'ID de l'utilisateur connecté

            //On ouvre la connexion à la base de donnée
            $entityManager = $this->getDoctrine()->getManager();
            $etat = $entityManager->getRepository(User::class)->find($connect);
            //if ($connect->getUserEtat() != 0) {
            $etat->setUserEtat(0);

            $entityManager->flush();

            return $this->redirectToRoute('security_logout');
        }
    }

    /**
     * @Route("/newpsswd", name="changePassword")
     */
    public function changePassword(Request $request, UserPasswordEncoderInterface $passwordEncoder){
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) { //Si connecté, on continue le script
            $user = $this->getUser(); //On récupère l'id du user connecté
            $newPassword =  $request->request->get('new_password'); //On récupère le mdp saisit dans le formulaire

            $encodePassword = $passwordEncoder->encodePassword($user, $newPassword);


            //On ouvre la connexion à la base de donnée
            $entityManager = $this->getDoctrine()->getManager();
            $password = $entityManager->getRepository(User::class)->find($user);

            $password->setPassword($encodePassword);

            $entityManager->flush();

            return $this->redirectToRoute('nouvelle_partie');

            //ENVOYER UN MAIL LORSQUE LE MDP EST CHANGÉ
        }
    }
}