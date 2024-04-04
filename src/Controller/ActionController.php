<?php

namespace Coen\CrudBundle\Controller;

use Coen\CrudBundle\Enum\CrudAction;
use Coen\CrudBundle\Form\Filter\BooleanFilterType;
use Coen\CrudBundle\Form\Filter\CollectionFilterType;
use Coen\CrudBundle\Form\Filter\DefaultFilterType;
use Coen\CrudBundle\Form\Filter\FromToFilterType;
use Coen\CrudBundle\Helper\FilterManager;
use App\Entity\DefaultFilter;
use App\Repository\DefaultFilterRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;

abstract class ActionController extends ExtendedSymfonyController
{
    private DefaultFilterRepository $defaultFilterRepository;

    #[Route('/', name: '_list')]
    public function listAction(Request $request): Response
    {
        $action = CrudAction::LIST;
        $this->checkAccess($action);

        $this->defaultFilterRepository = $this
            ->entityManager
            ->getRepository(DefaultFilter::class);

        $filterHandler = new FilterManager(
            $this->entityReflection,
            $this->entityRepository,
            $this->container->get('form.factory'),
            $this->getFormCustomisations(),
        );

        $filterCriteria = $this->loadDefaultFilter();
        $form = $filterHandler->handleRequest($request, $filterCriteria);
        if($form->isSubmitted() && $form->isValid()) {
            $filterCriteria = $form->getData();
            $this->saveDefaultFilter($filterCriteria);
        }

        if($request->get('deleteFilter', false)) {
            $this->removeDefaultFilter();
            return $this->redirectToAction(CrudAction::LIST);
        }

        return $this->renderCrud(
            $action,
            [
                'filterForm' => $form,
                'entities' => $this->getAllEntities($filterCriteria),
            ]
        );
    }

    #[Route('/create', name: '_create', methods: ['GET', 'POST'])]
    public function createAction(Request $request): Response
    {
        $action = CrudAction::CREATE;
        $this->checkAccess($action);

        $form = $this->createEntityForm($action, $this->createNewEntity());

        try {
            return $this->renderCrudForm($form, $action, $request);
        } catch(\Exception $e) {
            return $this->errorActionRedirect($action, $e);
        }
    }

    #[Route('/read/{id}', name: '_read', methods: ['GET', 'POST'])]
    public function readAction(Request $request, int $id): Response
    {
        $action = CrudAction::READ;
        $this->checkAccess($action);

        try {
            $entity = $this->entityRepository->find($id);
            return $this->renderCrud($action, ['entity' => $entity]);
        } catch(\Exception $e) {
            return $this->errorActionRedirect($action, $e);
        }
    }

    #[Route('/update/{id}', name: '_update', methods: ['GET', 'POST'])]
    public function updateAction(Request $request, int $id): Response
    {
        $action = CrudAction::UPDATE;
        $entity = $this->entityRepository->find($id);

        $this->checkAccess($action, $entity);

        $form = $this->createEntityForm($action, $this->entityRepository->find($id));

        try {
            return $this->renderCrudForm($form, $action, $request);
        } catch(\Exception $e) {
            return $this->errorActionRedirect($action, $e);
        }
    }

    #[Route('/delete/{id}', name: '_delete', methods: ['POST'])]
    public function deleteAction(Request $request, int $id): Response
    {
        $action = CrudAction::DELETE;
        $entity = $this->entityRepository->find($id);

        $this->checkAccess($action, $entity);

        try {
            if(!$this->isCsrfTokenValid('delete' . $id, $request->get('_token'))) {
                throw new InvalidCsrfTokenException();
            }

            $this->beforePersist($action, $entity);

            $this->entityManager->remove($entity);
            $this->entityManager->flush();

            return $this->successActionRedirect($action);
        } catch(\Exception $e) {
            return $this->errorActionRedirect($action, $e);
        }
    }

    protected function getAllEntities(array $filterCriterias): array
    {
        $queryBuilder = $this->createFilterQueryBuilder();

        if(count($filterCriterias) === 0) {
            return $queryBuilder->getQuery()->getResult();
        }

        foreach($filterCriterias as $propertyName => $filterCriteria) {
            $property = $this->entityReflection->getPropertyByName($propertyName);

            if($property === null) {
                continue;
            }

            $fieldAlias = 'e.' . $property->getColumnName();
            $parameter = strtoupper(':' . $property->getName());

            switch($filterCriteria['filter']) {
                case BooleanFilterType::class:
                    if($filterCriteria['criteria'] === 'true') {
                        $queryBuilder
                            ->andWhere($queryBuilder->expr()->eq($fieldAlias, 'true'));
                    } else {
                        $queryBuilder
                            ->andWhere($queryBuilder->expr()->eq($fieldAlias, 'false'));
                    }
                    break;

                case FromToFilterType::class:
                    $to = $filterCriteria['to'];
                    $from = $filterCriteria['from'];

                    if($from) {
                        $parameterFrom = $parameter . "_FROM";

                        $queryBuilder
                            ->andWhere($queryBuilder->expr()->gt($fieldAlias, $parameterFrom))
                            ->setParameter($parameterFrom, $from);
                    }

                    if($to) {
                        $parameterTo = $parameter . "_TO";

                        $queryBuilder
                            ->andWhere($queryBuilder->expr()->lt($fieldAlias, $parameterTo))
                            ->setParameter($parameterTo, $filterCriteria['to']);
                    }
                    break;

                case CollectionFilterType::class:
                    $criteria = $filterCriteria['criteria'];

                    if($criteria instanceof Collection) {
                        $criteria = $criteria->toArray();
                        if(count($criteria) > 0) {
                            $tableAlias = $property->getColumnName() . '_t';
                            $queryBuilder
                                ->join($fieldAlias, $tableAlias);

                            $ids = array_map(function($entity) {
                                return $entity->getId();
                            }, $criteria);

                            $queryBuilder->andWhere($queryBuilder->expr()->in($tableAlias . '.id', $ids));
                        }
                    }
                    break;

                case DefaultFilterType::class:
                default:
                    $criteria = $filterCriteria['criteria'];
                    if($criteria) {
                        $queryBuilder
                            ->andWhere($queryBuilder->expr()->eq($fieldAlias, $parameter))
                            ->setParameter($parameter, $criteria);
                    }
            }

            if($filterCriteria['order'] !== null) {
                $queryBuilder->addOrderBy($fieldAlias, $filterCriteria['order'] === 'asc' ? 'asc' : 'desc');
            }
        }

        return $queryBuilder->getQuery()->getResult();
    }

    private function loadDefaultFilter(): array
    {
        return $this->defaultFilterRepository->getForClass($this->getUser(), $this->entityReflection->getClass());
    }

    private function saveDefaultFilter(array $filterCriteria): void
    {
        $this->defaultFilterRepository->setForClass($this->getUser(), $this->entityReflection->getClass(), $filterCriteria);
    }

    private function removeDefaultFilter(): void
    {
        $this->defaultFilterRepository->removeForClass($this->getUser(), $this->entityReflection->getClass());
    }

    protected function createFilterQueryBuilder(): QueryBuilder
    {
        return $this->entityRepository->createQueryBuilder('e');
    }
}