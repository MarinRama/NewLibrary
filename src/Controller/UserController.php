<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
            $pictureFile = $form->get('picture')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($pictureFile) {
                $originalFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $pictureFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $pictureFile->move(
                        $this->getParameter('pictures_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'pictureFilename' property to store the PDF file name
                // instead of its contents
                $user->setBrochureFilename($newFilename);
            }

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
        $article = $articleRepository;

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }
    // / Accueil affiche les derniers articles
//    /**
//     * @Route ("/", name="accueil", methods={"GET"})
//     */
//    public function index(ArticleRepository $articleRepository): Response
//    {
//        $articles = $articleRepository->findAll();
//
//        return $this->render('default/index.html.twig', [
//            'articles' => $articles
//        ]);
//    }

    #[Route('/{id}/edit', name: 'edit_user', methods: ['GET', 'POST','PUT'])]
    public function edit(Request $request, User $user, UserRepository $userRepository, EntityManagerInterface $manager): Response
    {

        if($this->getUser()->getId() != $user->getId()){
            return $this->redirectToRoute('edit_user', ['id' => $this->getUser()->getId()]);
        }
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $datas = $form->getData();
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
}
