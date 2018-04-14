<?php
namespace App\Controller;

use App\Form\InscriptionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle;

class RegController extends Controller
{
    /**
     * @Route("/register", name="user_registration")
     */
    public function registerAction(Request $request, UserPasswordEncoderInterface $passwordEncoder, \Swift_Mailer $mailer)
    {
        $user = new User();
        $form = $this->createForm(InscriptionType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $password = $passwordEncoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password);

            // Par defaut, l'utilisateur aura toujours le rôle ROLE_USER
            $user->setRoles(['ROLE_USER']);

            // Par défaut, l'état de l'utilisateur est passé à 0.
            $user->setUserEtat(0);

            // Par défaut, l'image de l'utilisateur est déifnie comme une string vide
            $user->setImage('');

            // On enregistre l'utilisateur dans la base
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $userMail = $user->getUserMail();

            $username = $user->getUsername();

            $body = "<h3>Inscription au jeu Space Conquest</h3>
                            <br>
                            Bonjour ".$username ." ! <br>
                            <br>
                            Vous recevez ce mail suite à votre inscription au jeu Space Conquest. Nhésitez-pas à consulter le tutoriel et à échanger sur le tchat ! <br>
                            En espérant que vous passerez un agréable moment à jouer à notre jeu Space Conquest. <br>
                            <br>
                            L'équipe de Space Conquest.";

            $message = (new \Swift_Message('Inscription - Space conquest'))
                ->setFrom('spaceconquest@brandonleininger.fr')
                ->setTo($userMail)
                ->setBody($body, 'text/html');

            $mailer->send($message);

            return $this->redirectToRoute('security_login');
        }

        return $this->render('home/reg.html.twig', array('form' => $form->createView())
        );
    }

    /**
     * @Route("/forget", name="user_forget")
     */
    public function forgetPassword(Request $request, UserPasswordEncoderInterface $passwordEncoder, \Swift_Mailer $mailer){
        $form = $this->createFormBuilder()
            ->add('user_mail', EmailType::class, array('label' => 'Adresse email'))
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $userMail = $data['user_mail'];

            //On récupère les différents mails de la BDD
            $entityManager = $this->getDoctrine()->getManager();
            $mailDB = $entityManager->getRepository(User::class)->findBy(array('user_mail' => $userMail));

            if(empty($mailDB)){
                $error = "L'adresse mail saisie ne correspond pas"; //On affiche un message d'erreur
                return $this->render('home/forget.html.twig', array('form' => $form->createView(), 'error'=>$error));
            } else{
                foreach ($mailDB as $result) {
                    //Ligne qui génère un mot de passe aléatoire grâce à la fonction Genere_Password()
                    $clear = $this->Genere_Password(10);
                    $password = $passwordEncoder->encodePassword($result, $clear);
                    $user = $this->getDoctrine()->getRepository("App:User")->findBy(['user_mail' => $userMail]);

                    $error="Un mail vient de vous être envoyé à l'adresse mail saisie ci-dessous.";

                    foreach($user as $result){
                        $userName = $result->getUsername();
                    }

                    //echo $clear;

                    $result->setPassword($password); //Mise à jour du mot de passe
                    $entityManager->flush();


                    $message = (new \Swift_Message('Récupération de mot de passe - Space conquest'))
                        ->setFrom('spaceconquest@brandonleininger.fr')
                        ->setTo('dragonwar10@icloud.com')
                        ->setBody(
                            $this->renderView(
                            // templates/home/forgetMail.html.twig
                                'home/forgetMail.html.twig',
                                array('clear' => $clear,
                                    'username' => $userName)
                            ),
                            'text/html'
                        );

                    $mailer->send($message);

                    return $this->render('home/forget.html.twig', array('form' => $form->createView(), 'error'=>$error));
                }
            }
        } else{
            $error = ""; //On affiche un message d'erreur
            return $this->render('home/forget.html.twig', array('form' => $form->createView(), 'error'=>$error));
        }
    }

    function Genere_Password($length)
    {
        // Initialisation des caractères utilisables
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $password = substr( str_shuffle( $chars ), 0, $length );
        return $password;

    }
}
