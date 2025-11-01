<?php

namespace Coen\CrudBundle\Controller;

use Coen\CrudBundle\Enum\CrudAction;
use Coen\CrudBundle\Service\CrudServiceFactory;
use Coen\CrudBundle\Service\SymfonyServiceFacade;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use function PHPUnit\Framework\stringContains;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

abstract class AbstractActionController extends AbstractController
{
    protected CrudAction $currentAction;

    public function __construct(
        SymfonyServiceFacade $symfonyServiceFacade,
        CrudServiceFactory   $crudServiceFactory,
    )
    {
        parent::__construct($symfonyServiceFacade, $crudServiceFactory);

        $routeName = $symfonyServiceFacade->getRequestStack()->getCurrentRequest()->attributes->get('_route');
        $this->currentAction = CrudAction::LIST;

        foreach (CrudAction::cases() as $action) {
            if(str_contains($routeName, $action->toRoute())) {
                $this->currentAction = $action;
                break;
            }
        }

        $this->buttonGenerator->setCurrentAction($this->currentAction);
    }

    #[Route('/', name: '_list')]
    public function list(): Response
    {
        $this->checkAccess();

        try {
            return $this->renderCrud(
                [
                    'filterForm' => $this->filterService->getFilterForm(),
                    'entities' => $this->filterService->getFilteredEntities(),
                ],
            );
        } catch(\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    #[Route('/create', name: '_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $this->checkAccess();

        try {
            return $this->renderCrudForm(
                $request, $this->crudFormGenerator->createCreateForm()
            );
        } catch(\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    #[Route('/read/{id}', name: '_read', methods: ['GET', 'POST'])]
    public function read(int $id): Response
    {
        $entity = $this->entityContext->getRepository()->find($id);
        $this->checkAccess($entity);

        try {
            return $this->renderCrud(['entity' => $entity]);
        } catch(\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    #[Route('/update/{id}', name: '_update', methods: ['GET', 'POST'])]
    public function update(Request $request, int $id): Response
    {
        $entity = $this->entityContext->getRepository()->find($id);
        $this->checkAccess($entity);

        try {
            return $this->renderCrudForm(
                $request, $this->crudFormGenerator->createUpdateForm($entity)
            );
        } catch(\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    #[Route('/delete/{id}', name: '_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $entity = $this->entityContext->getRepository()->find($id);
        $this->checkAccess($entity);

        try {
            if(!$this->isCsrfTokenValid('delete' . $id, $request->get('_token'))) {
                throw new InvalidCsrfTokenException();
            }

            $this->beforePersist($entity);

            $this->entityManager->remove($entity);
            $this->entityManager->flush();

            return $this->successResponse();
        } catch(\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // Twig Helper Function

    protected function renderCrud(array $parameters = [], string $template = null, Response $response = null): Response
    {
        $parameters['twigTemplateSelector'] = $this->templateSelector;
        $parameters['translationKeyFactory'] = $this->translationKeyGenerator;
        $parameters['crudRouter'] = $this->router;

        $data = array_merge([
            'crudAction' => CrudAction::toTwigArray(),
            'currentAction' => $this->currentAction,
            'buttonGenerator' => $this->buttonGenerator
        ], $parameters);

        return parent::renderCrud(
            $data,
            $template ?? $this->templateSelector->getActionTemplate($this->currentAction)
        );
    }

    // Doctrine Helper Function

    protected function beforePersist(?object $entity): void
    {
        foreach($this->entityContext->getReflection()->getProperties() as $property) {
            if(
                $property->getFormType() == 'collection' &&
                (
                    $property->getCollectionType() == ManyToMany::class && $property->isMappedSite()
                )
            ) {
                $inversedProperty = $property->getInverseProperty();
                /** @var Collection $collection */
                $currentCollection = $property->getValue($entity);
                /** @var Collection $collection */
                $qb = $this->entityContext->getRepository()->createQueryBuilder('e');
                $qb
                    ->select('j.id')
                    ->leftJoin('e.' . $property->getIdentifier(), 'j')
                    ->where('e.id = :ID')
                    ->andWhere('j.id is not null')
                    ->setParameter('ID', $entity->getId());

                $oldCollection = $qb->getQuery()->getSingleColumnResult();

                $currentCollectionEntityIds = [];
                foreach ($currentCollection as $value) {
                    $currentCollectionEntityIds[] = $value->getId();
                    $inversedProperty->set($value, $entity);
                }

                $toDelete = array_diff($oldCollection, $currentCollectionEntityIds);

                foreach ($toDelete as $id) {
                    $unselectInverseEntity = $this->entityManager->find($property->getTargetEntity(), $id);
                    $inversedProperty->remove($unselectInverseEntity, $entity);
                    $this->entityManager->persist($unselectInverseEntity);
                }
            }
        }
    }

    protected function afterPersist(?object $entity): void
    { }

    // Success and Error Redirect

    protected function errorResponse(\Exception $e): Response
    {
        if($this->currentAction === CrudAction::LIST) {
            throw $e;
        }

        $this->addFlash('danger', $e->getMessage());
        return $this->router->redirectToAction(CrudAction::LIST);
    }

    protected function successResponse(): Response
    {
        $this->addFlash('success', $this->trans($this->successMsg()));
        return $this->router->redirectToAction(CrudAction::LIST);
    }

    protected function successMsg(): string
    {
        return match ($this->currentAction) {
            CrudAction::CREATE => 'crud.msg.create',
            CrudAction::UPDATE => 'crud.msg.update',
            CrudAction::DELETE => 'crud.msg.delete',
            CrudAction::LIST, CrudAction::READ => '',
        };
    }

    protected function checkAccess(object $entity = null): void
    {
        if(!$this->hasAccess($this->currentAction)) {
            throw new NotFoundHttpException();
        }
    }
}