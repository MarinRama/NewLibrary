<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', null,[
                'attr' => [
                    'placeholder' => "Ajoutez un titre à l'article"
                ]
            ])
            ->add('description')
            ->add('dateCreation', null, [
                'widget' => 'single_text'
            ])
            ->add('dateUpdate', null, [
                'widget' => 'single_text'
            ])
            ->add('imageFile', FileType::class, [
                'mapped' => false,
                'required' => false
            ])
            ->add('categories',EntityType::class, [
                'class' => Category::class,
                'multiple' => true,
                'by_reference' => false
            ])
//            ->add('author', EntityType::class, [
//                'class' => User::class,
//            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
