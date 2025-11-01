<?php

namespace Coen\CrudBundle\Controller;

use Coen\CrudBundle\Enum\CrudAction;
use Coen\CrudBundle\Form\CrudType;
use Coen\CrudBundle\Service\CrudServiceFactory;
use Coen\CrudBundle\Service\SymfonyServiceFacade;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\Annotation\Route;

abstract class AbstractEditableListControllerAbstract extends AbstractActionController
{
    public function __construct(
        SymfonyServiceFacade                    $symfonyServiceFacade,
        CrudServiceFactory                      $crudServiceFactory,
        protected readonly FormFactoryInterface $formFactory,
    ) {
        parent::__construct(
            $symfonyServiceFacade,
            $crudServiceFactory
        );
    }
    #[Route('/', name: '_list')] public function list(): Response
    {
        $this->currentAction = CrudAction::LIST;
        $this->checkAccess();

        $listFormBuilder = $this->formFactory->createBuilder(FormType::class, [
            'entities' => $this->filterService->getFilteredEntities()
        ]);
        $listFormBuilder->add('entities', CollectionType::class, [
            'allow_add' => true,
            'allow_delete' => true,
            'entry_type' => CrudType::class,
            'entry_options' => [
                'current_action' => $this->currentAction,
                'entity_context' => $this->entityContext,
                'data_class' => $this->entityContext->getReflection()->getClass(),
            ]
        ]);

        $listForm = $listFormBuilder->getForm();

        $request = $this->symfonyServiceFacade->getRequestStack()->getCurrentRequest();
        $listForm->handleRequest($request);

        if($listForm->isSubmitted() && $listForm->isValid()) {
            $entities = $listForm->getData()['entities'];

            foreach ($entities as $entity) {
                $this->saveEntity($entity);
            }

            return $this->successResponse();
        }

        try {
            return $this->renderCrud(
                [
                    'filterForm' => $this->filterService->getFilterForm(),
                    'entitiesForm' => $listFormBuilder->getForm(),
                ],
                $this->templateSelector->getTemplate('list/list_editable.html.twig')
            );
        } catch(\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function read(int $id): Response
    {
        throw new NotAcceptableHttpException();
    }

    public function update(Request $request, int $id): Response
    {
        throw new NotAcceptableHttpException();
    }
}