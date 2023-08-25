<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\QuestionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MainController extends AbstractController {

    public function __construct(
        private CategoryRepository $categoryRepository,
        private QuestionRepository $questionRepository,
    ) {
    }

    #[Route('/', name: 'home')]
    public function home(Request $request): Response {
        $query = $request->query->get("q");
        if($query == null)
            return $this->browseBaseCategories($request);
        $query = strval($query);
        $questions = $this->questionRepository->search($query);
        return $this->render('search.html.twig', [
            'query' => $query,
            'questions' => $questions,
        ]);
    }
    
    #[Route('/{path}', name: 'browse_category', requirements: ['path'=>'([a-zA-Z0-9-]+\/|[a-zA-Z0-9-]+)+'])]
    public function getCategory(Request $request, string $path): Response {
        $categoryContent = $this->categoryRepository->findOneByUrl($path);
        if($categoryContent == null)
            throw $this->createNotFoundException();
        return $this->browseCategory($request, $categoryContent);
    }
    
    private function browseCategory(Request $request, Category $category): Response {
        $categoriesTree = $this->categoryRepository->categoriesTree($category->getId());
        if(!empty($categoriesTree) && $categoriesTree[0]->getParentCategory() != null)
            throw $this->createNotFoundException();
        $subCategories = $this->categoryRepository->findAllByParentCategory($category->getId());
        $questions = $this->questionRepository->findAllByCategory($category->getId());
        return $this->render('browseCategories.html.twig', [
            'categoriesTree' => $categoriesTree,
            'category' => $category,
            'categories' => $subCategories,
            'questions' => $questions,
        ]);
    }
    
    private function browseBaseCategories(Request $request): Response {
        $categories = $this->categoryRepository->findAllByParentCategory(null);
        return $this->render('browseCategories.html.twig', [
            'categoriesTree' => array(),
            'category' => null,
            'categories' => $categories,
        ]);
    }

    public function categories(): Response {
        $category = null;
        $categories = $this->categoryRepository->findAllByParentCategory(null);
        return $this->render('browseCategories.html.twig', [
            'categoriesTree' => array(),
            'category' => null,
            'categories' => $categories,
        ]);
    }
}