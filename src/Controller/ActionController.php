<?php

namespace Coen\CrudBundle\Controller;

use Coen\CrudBundle\Enum\CrudAction;
use Coen\CrudBundle\Form\Filter\BooleanFilterType;
use Coen\CrudBundle\Form\Filter\CollectionFilterType;
use Coen\CrudBundle\Form\Filter\DefaultFilterType;
use Coen\CrudBundle\Form\Filter\FromToFilterType;
use Coen\CrudBundle\Helper\FilterManager;
use Coen\CrudBundle\Service\TwigTemplateSelector;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

abstract class ActionController extends ExtendedSymfonyController
{
    public function __construct(
        protected readonly CacheInterface $cachePool,
        EntityManagerInterface $entityManager,
        TwigTemplateSelector $twigTemplateSelector
    )
    {
        parent::__construct($entityManager, $twigTemplateSelector);
    }

    #[Route('/', name: '_list')]
    public function list(Request $request): Response
    {
        try {
            $action = CrudAction::LIST;
            $this->checkAccess($action);

            $filterHandler = new FilterManager(
                $this->entityReflection,
                $this->entityRepository,
                $this->container->get('form.factory'),
                $this->getFormCustomisations(),
            );
            $filterCacheKey = 'coen_crud_filter_' . md5($this->entityClass . $this->getUser()->getUserIdentifier());

            $form = $filterHandler->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()) {
                $this->cachePool->delete($filterCacheKey);
            }

            if($request->get('deleteFilter', false)) {
                $this->cachePool->delete($filterCacheKey);
                return $this->redirectToAction(CrudAction::LIST);
            }

            $filterCriteria = $this->cachePool->get($filterCacheKey, function (ItemInterface $item) use ($form) {
                $item->expiresAfter(2592000 ); // 1 Monat

                if($form->isSubmitted() && $form->isValid()) {
                    return $form->getData();
                }

                return [];
            });

            return $this->renderCrud(
                $action,
                [
                    'filterForm' => $form,
                    'entities' => $this->getAllEntities($filterCriteria),
                ]
            );
        } catch(\Exception $e) {
            return $this->errorActionRedirect($action, $e);
        }
    }

    #[Route('/create', name: '_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        try {
            $action = CrudAction::CREATE;
            $this->checkAccess($action);

            return $this->renderCrudForm($action, $request);
        } catch(\Exception $e) {
            return $this->errorActionRedirect($action, $e);
        }
    }

    #[Route('/read/{id}', name: '_read', methods: ['GET', 'POST'])]
    public function read(Request $request, int $id): Response
    {
        try {
            $action = CrudAction::READ;
            $entity = $this->entityRepository->find($id);
            $this->checkAccess($action, $entity);

            return $this->renderCrud($action, ['entity' => $entity]);
        } catch(\Exception $e) {
            return $this->errorActionRedirect($action, $e);
        }
    }

    #[Route('/update/{id}', name: '_update', methods: ['GET', 'POST'])]
    public function update(Request $request, int $id): Response
    {
        try {
            $action = CrudAction::UPDATE;
            $entity = $this->entityRepository->find($id);

            $this->checkAccess($action, $entity);

            return $this->renderCrudForm($action, $request, $entity);
        } catch(\Exception $e) {
            return $this->errorActionRedirect($action, $e);
        }
    }

    #[Route('/delete/{id}', name: '_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
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

    protected function createFilterQueryBuilder(): QueryBuilder
    {
        return $this->entityRepository->createQueryBuilder('e');
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
}