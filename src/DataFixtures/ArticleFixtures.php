<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;

class ArticleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        $author = array();
        for($i=0 ; $i<4 ; $i++){
            $author[$i] = new User();
            $author[$i]->setLogin($faker->name);
            $author[$i]->setPassword($faker->name);
            $author[$i]->setNom($faker->lastName);
            $author[$i]->setPrenom($faker->firstName);
            $manager->persist($author[$i]);

        }

        $articles = array();
        for($i=0 ; $i<25 ; $i++){
            $articles[$i] = new Article();
            $articles[$i]->setTitre($faker->realText(15,1));
            $articles[$i]->setDescription($faker->realText(400, 3));
            $articles[$i]->setDateCreation($faker->dateTimeThisYear);
            $articles[$i]->setDateUpdate($faker->dateTimeThisMonth);
            $articles[$i]->setAuthor($author[$i % 3]);
            $articles[$i]->setImage('https://loremflickr.com/640/360');


            $manager->persist($articles[$i]);

        }

        $manager->flush();
    }
}
