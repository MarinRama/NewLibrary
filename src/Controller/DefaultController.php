<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\User;
use App\Form\CommentType;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Service\ArticlesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    // / Accueil affiche les derniers articles
    /**
     * @Route ("/", name="accueil", methods={"GET"})
     */
    public function index(ArticlesService $articlesService): Response
    {
        return $this->render('default/index.html.twig', [
            'articles' => $articlesService->getPaginatedArticles()
        ]);
    }


    // Categories affiche toute les categories
    /**
     * @Route ("/categories", name="liste_categories", methods={"GET"})
     */
    public function listeCategories(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        return  $this->render('default/categories.html.twig', [
            'categories' => $categories
        ]);
    }



    //vuCategorie affiche tout les livres d'une categorie
    /**
     * @Route ("/category/{id}", name="affiche_categorie", requirements={"id"="\d+"}, methods={"GET"})
     */
    public function afficheCategorie(CategoryRepository $categoryRepository, $id){
        $category = $categoryRepository->find($id);

        $articles = $category->getArticles()->toArray();

        return $this->render('default/afficheCategorie.html.twig',[
            'category' => $category,
            'articles' => $articles
        ]);
    }


    //vuArticle affiche article
    /**
     *   @Route ("/articles/{id}", name="vue_article", requirements={"id"="\d+"}, methods={"GET", "POST"})
     */
    public function vueArticle(Article $article, ArticleRepository $articleRepository,Request $request, EntityManagerInterface $manager,$id){

        $comment = new Comment();
        $comment->setArticle($article);
        $form = $this->createForm(CommentType::class, $comment);
        $user = $this->getUser(['id']);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $comment->setAuthor($user->getUserIdentifier());
            $manager->persist($comment);
            $manager->flush();

            return $this->redirectToRoute('vue_article',  ['id' => $article->getId()]);
        }

        return $this->render('default/vuArticle.html.twig',[
           'article' => $article,
            'form' => $form->createView()

        ]);


    }








}
