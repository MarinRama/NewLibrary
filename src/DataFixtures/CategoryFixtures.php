<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $bd = new Category();
        $bd->setName('Bandes Dessinés');
        $manager->persist( $bd);

        $roman = new Category();
        $roman->setName('Romans');
        $manager->persist($roman);

        $manga = new Category();
        $manga->setName('Mangas');
        $manager->persist($manga);

        $info = new Category();
        $info->setName('Informations');
        $manager->persist($info);

//      $info->addArticle($this->getReference('1'));


        $manager->flush();
    }

    public function getDependencies(){
        return [
            ArticleFixtures::class
        ];
    }
}
