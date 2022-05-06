<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\User;
use App\Form\ArticleType;
use App\Form\UserType;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;


#[Route('/user')]
class UserController extends AbstractController
{

    public const EDIT_ROUTE = 'user/edit.html.twig';
    public const HOME_ROUTE = 'accueil';

    #[Route('/new', name: 'new_user', methods: ['GET', 'POST'])]
    public function new(Request $request, UserRepository $userRepository, SluggerInterface $slugger): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->add($user);

            return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show_user', methods: ['GET'])]
    public function show(User $user, ArticleRepository $articleRepository): Response
    {
        if($this->getUser()->getId() != $user->getId() && $this->getUser()->getUserIdentifier() != 'admin'){
            return $this->redirectToRoute('show_user', ['id' => $this->getUser()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit_user', methods: ['GET', 'POST','PUT'])]
    public function edit(Request $request, User $user, UserRepository $userRepository, EntityManagerInterface $manager): Response
    {
        if($this->getUser()->getId() != $user->getId()){
            return $this->redirectToRoute('edit_user', ['id' => $this->getUser()->getId()]);
        }
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form['pictureFile']->getData();
            if($uploadedFile){
                $destination = $this->getParameter('pictures_directory');

                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

                $uploadedFile->move(
                    $destination,
                    $newFilename
                );
                $user->setPictureFilename($newFilename);
            }

            $userRepository->add($user);
            $manager->persist($user);
            $manager->flush();

            $this->addFlash('message','Profil mis à Jour');
            return $this->redirectToRoute('edit_user', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/editPass', name: 'editPass_user', methods: ['GET', 'POST','PUT'])]
    public function editPass(Request $request, User $user, UserRepository $userRepository, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $u = $this->getUser();

        if($u->getId() != $user->getId()){
            return $this->redirectToRoute('editPass_user', ['id' => $this->getUser()->getId()]);
        }
        if($request->isMethod('POST')){
            //verification si les 2 MDP identiques
            if($request->request->get('pass')==$request->request->get('pass2')){
                $mdp = $request->request->get('pass');
                $hashpassword = $passwordHasher->hashPassword(
                    $u,
                    $mdp
                );
                $u->setPassword($hashpassword);
                $manager->flush();
                $this->addFlash('message','Mot de passe mis à jour avec succès');

                return $this->redirectToRoute('edit_user', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
            }
            else{
                $this->addFlash('error','Les 2 mots de passe ne sont pas identiques');
            }
        }

        return $this->render('user/editPass.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'delete_user', methods: ['POST'])]
    public function delete(Request $request, User $user, UserRepository $userRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $userRepository->remove($user);
        }

        return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
    }

    // / affiche les articles de l'User
    #[Route('/{id}/showUserArticle', name: 'show_user_article', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository, User $user): Response
    {
        if($this->getUser()->getId() != $user->getId() && $this->getUser()->getUserIdentifier() != 'admin'){
            return $this->redirectToRoute('show_user_article', ['id' => $this->getUser()->getId()], Response::HTTP_SEE_OTHER);
        }
        $articles = $articleRepository->findBy([
            'author' => ['id' => $user->getId()]
        ]);
        return $this->render('user/article/indexUserArticle.html.twig', [
            'articles' => $articles
        ]);
    }

    #[Route('/{id}/editArticle', name: 'edit_article', methods: ['GET', 'POST','PUT'])]
    public function editArticle(Request $request, Article $article, ArticleRepository $articleRepository, EntityManagerInterface $manager): Response
    {
        if($this->getUser()->getId() != $article->getAuthor()->getId()){
            return $this->redirectToRoute('show_user_article', ['id' => $this->getUser()->getId()]);
        }
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form['imageFile']->getData();
            if($uploadedFile){
                $destination = $this->getParameter('article_pictures_directory');

                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

                $uploadedFile->move(
                    $destination,
                    $newFilename
                );
                $article->setImage($newFilename);
            }

            $datas = $form->getData();
            $articleRepository->add($article);
            $manager->persist($article);
            $manager->flush();

            $this->addFlash('message','Article mis à Jour');
            return $this->redirectToRoute('edit_article', ['id' => $article->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/article/editArticle.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'delete_article', methods: ['POST'])]
    public function deleteArticle(Request $request, Article $article, User $user, ArticleRepository $articleRepository): Response
    {
        if ($this->isCsrfTokenValid('deleteArticle'.$article->getId(), $request->request->get('_token'))) {
            $articleRepository->remove($article);
        }

        return $this->redirectToRoute('show_user_article', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/newArticle', name: 'new_article', methods: ['GET', 'POST'])]
    public function newArticle(Request $request, User $user, ArticleRepository $articleRepository, EntityManagerInterface $manager): Response
    {
        if($this->getUser()->getId() != $user->getId() && $this->getUser()->getUserIdentifier() != 'admin'){
            return $this->redirectToRoute('new_article', ['id' => $this->getUser()->getId()], Response::HTTP_SEE_OTHER);
        }
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setAuthor($this->getUser());
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form['imageFile']->getData();
            if($uploadedFile){
                $destination = $this->getParameter('article_pictures_directory');

                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();
                $uploadedFile->move(
                    $destination,
                    $newFilename
                );
                $article->setImage($newFilename);

                $articleRepository->add($article);
                $manager->persist($article);
                $manager->flush();

                $this->addFlash('message','New Article add');
            }
            return $this->redirectToRoute('show_user_article', ['id' => $this->getUser()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/article/new_article.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }
}
