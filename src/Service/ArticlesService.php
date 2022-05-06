<?php

namespace App\Service;

use App\Entity\Category;
use App\Repository\ArticleRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ArticlesService
{
    public function __construct(
        private RequestStack $requestStack,
        private ArticleRepository $articleRepo,
        private PaginatorInterface $paginator
    ){

    }

    public function getPaginatedArticles(?Category $category = null){
        $request = $this->requestStack->getMainRequest();
        $page = $request->query->getInt('page',1);
        $limit = 5;

        $articlesQuery = $this->articleRepo->findForPagination($category);

        return $this->paginator->paginate($articlesQuery, $page, $limit);
    }
}