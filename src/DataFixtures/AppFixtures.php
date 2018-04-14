<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture {
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        foreach ($this->getUserData() as [$pseudo, $password, $email, $roles]) {
            $user = new User();
            $user->setUsername($pseudo);
            $user->setPassword($this->passwordEncoder->encodePassword($user, $password));
            $user->setUserMail($email);
            $user->setRoles($roles);

            $manager->persist($user);
            $this->addReference($pseudo, $user);
        }

        $manager->flush();
    }

    private function getUserData(): array
    {
    return [
    // $userData = [$username, $password, $email, $roles];
    ['Brand72', 'Azerty', 'brandon.leininger@icloud.com', ['ROLE_ADMIN']],
    ['david', 'azerty', '14vl008@orange.fr', ['ROLE_USER']],
    ['lucie', 'azerty', 'luciedu10@gmail.com', ['ROLE_USER']],
    ];
}


}