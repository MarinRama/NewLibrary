<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setLogin('MarinR');
        $user->setPassword('$2y$13$MgYWjvS8sgyXt8KUhRN/juuCLRtkVUDwT/NLy4uOVHF4bdW0Hj2Wm');
        $manager->persist($user);

        $admin = new User();
        $admin->setLogin('admin');
        $admin->setPassword('$2y$13$ioJKcjD66/MmIoRF8s/C8emPDQjbSTjS8VyBPTEjFAuS0Ko/u/HqC');
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        $manager->flush();
    }
}
