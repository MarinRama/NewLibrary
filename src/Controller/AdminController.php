<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Form\CategoryType;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/user_index', name: 'user_index', methods: ['GET'])]
    public function showUsers(UserRepository $userRepository): Response
    {
        return $this->render('admin/showIndexUsers.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }


    #[Route('/newCategory', name: 'new_category', methods: ['GET', 'POST'])]
    public function newCategory(Request $request, CategoryRepository $categoryRepository, EntityManagerInterface $manager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form['imageFile']->getData();
            if($uploadedFile){
                $destination = $this->getParameter('category_pictures_directory');

                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

                $uploadedFile->move(
                    $destination,
                    $uploadedFile->getClientOriginalName(),
                    $newFilename
                );
                $category->setImage($newFilename);

                $categoryRepository->add($category);
                $manager->persist($category);
                $manager->flush();

            }
            return $this->redirectToRoute('liste_categories', [], Response::HTTP_SEE_OTHER);
        }
        return $this->renderForm('admin/newCategory.html.twig', [
            'category' => $category,
            'form' => $form
        ]);
    }



}
