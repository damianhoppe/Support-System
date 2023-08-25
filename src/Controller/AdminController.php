<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Question;
use App\Repository\CategoryRepository;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController {

    public function __construct(
        private CategoryRepository $categoryRepository,
        private QuestionRepository $questionRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    #[Route('', name: 'index')]
    public function index(): Response {
        return $this->render('admin.html.twig',[]);
    }

    private function toList($categories, &$inputArray, $prefix = "") {
        $prefix = $prefix . "-";
        foreach($categories as $category) {
            $key = $prefix . $category->getTitle();
            $category->titleWithLevel = $key;
            array_push($inputArray, $category);
            $this->toList($category->getSubCategories(), $inputArray, $prefix);
        }
    }

    #[Route('/categories/new')]
    public function newCategory(Request $request): Response {
        return $this->newCategoryInCategory($request, null);
    }

    #[Route('/categories/new/{categoryId}')]
    public function newCategoryInCategory(Request $request, ?int $categoryId): Response {
        return $this->categoryForm($request, $categoryId);
    }

    #[Route('/categories/edit/{categoryId}')]
    public function editCategory(Request $request, int $categoryId): Response {
        return $this->categoryForm($request, null, $categoryId);
    }

    public function removeThisCategory(Category $category) {
        foreach($category->getSubCategories() as $subCategory) {
            $this->removeThisCategory($subCategory);
        }
        foreach($category->getQuestions() as $question) {
            $this->entityManager->remove($question);
        }
        $this->entityManager->remove($category);
    }

    #[Route('/categories/delete/{categoryId}')]
    public function deleteCategory(Request $request, int $categoryId): Response {
        $category = $this->categoryRepository->find($categoryId);
        if($category == null) {
            throw $this->createNotFoundException();
        }
        if($category->getParentCategory() == null) {
            $redirectTo = '/';
        }else {
            $redirectTo = '/' . $question->getParentCategory()->getUrl();
        }
        if(empty($category->getSubCategories()) && empty($category->getQuestions())) {
            $this->entityManager->remove($category);
            $this->entityManager->flush();
            return $this->redirect($redirectTo);
        }else if($request->query->get("force") != true) {
            if($request->headers->get('referer') != null) {
                $back = $request->headers->get('referer');
            }else {
                $back = "/" . $category->getId();
            }
            return $this->render('admin/deleteConfirmForm.html.twig',[
                'category' => $category,
                'action' => '/admin/categories/delete/' . $categoryId . '?force=true',
                'back' => $back,
            ]);
        }
        $this->removeThisCategory($category);
        $this->entityManager->flush();
        return $this->redirect($redirectTo);
    }

    private function categoryForm(Request $request, ?int $categoryId, ?int $loadCategory = null): Response {
        $baseCategories = $this->categoryRepository->findAllByParentCategory(null);
        $allCategories = array();
        $this->toList($baseCategories, $allCategories);

        if($loadCategory == null) {
            $category = new Category();
            if($categoryId != null) {
                foreach($allCategories as $cat) {
                    if($cat->getId() == $categoryId) {
                        $category->setParentCategory($cat);
                        break;
                    }
                }
            }
        }else {
            foreach($allCategories as $cat) {
                if($cat->getId() == $loadCategory) {
                    $category = $cat;
                    break;
                }
            }
        }
        $formBuilder = $this->createFormBuilder($category);
        $formBuilder->add('parentCategory', EntityType::class, [
            'class' => Category::class,
            'choices' => $allCategories,
            'required' => false,
            'choice_label' => 'titleWithLevel',
            'choice_value' => 'id',
            'placeholder' => "Brak (kategoria główna)",
        ])
        ->add('title', TextType::class)
        ->add('icon', TextType::class, [
            'required' => false,
        ])
        ->add('label', TextType::class)
        ->add('save', SubmitType::class);
        $form = $formBuilder->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $category = $form->getData();
            if($category->getParentCategory() == null) {
                $category->setUrl($category->getLabel());
            }else {
                $category->setUrl($category->getParentCategory()->getUrl() . "/" . $category->getLabel());
            }
            $this->entityManager->persist($category);
            $this->entityManager->flush();

            return $this->redirect('/' . $category->getUrl());
        }

        return $this->render('admin/form.html.twig',[
            'form' => $form,
        ]);
    }

    #[Route('/categories/{categoryId}/questions/new')]
    public function newQuestion(Request $request, int $categoryId): Response {
        return $this->questionForm($request, $categoryId, null);
    }

    #[Route('/questions/edit/{questionId}')]
    public function editQuestion(Request $request, int $questionId): Response {
        return $this->questionForm($request, null, $questionId);
    }

    #[Route('/questions/delete/{questionId}')]
    public function deleteQuestion(Request $request, int $questionId): Response {
        $question = $this->questionRepository->find($questionId);
        if($question == null) {
            throw $this->createNotFoundException();
        }
        if($question->getCategory() == null) {
            $redirectTo = '/';
        }else {
            $redirectTo = '/' . $question->getCategory()->getUrl();
        }
        $this->entityManager->remove($question);
        $this->entityManager->flush();
        return $this->redirect($redirectTo);
    }

    public function questionForm(Request $request, ?int $categoryId, ?int $questionToLoad = null): Response {
        $baseCategories = $this->categoryRepository->findAllByParentCategory(null);
        $allCategories = array();
        $this->toList($baseCategories, $allCategories);

        if($questionToLoad == null) {
            $question = new Question();
            foreach($allCategories as $cat) {
                if($cat->getId() == $categoryId)
                $question->setCategory($cat);
            }
        }else {
            $question = $this->questionRepository->find($questionToLoad);
            if($question == null) {
                throw $this->createNotFoundException();
            }
        }

        $formBuilder = $this->createFormBuilder($question);
        $formBuilder->add('category', EntityType::class, [
            'class' => Category::class,
            'choices' => $allCategories,
            'required' => true,
            'choice_label' => 'titleWithLevel',
            'choice_value' => 'id',
        ])
        ->add('title', TextType::class)
        ->add('content', TextareaType::class)
        ->add('save', SubmitType::class);
        $form = $formBuilder->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $question = $form->getData();
            $this->entityManager->persist($question);
            $this->entityManager->flush();

            return $this->redirect('/' . $question->getCategory()->getUrl());
        }

        return $this->render('admin/form.html.twig',[
            'form' => $form,
        ]);
    }
}