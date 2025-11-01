<?php

namespace Coen\CrudBundle\Helper;

use Coen\CrudBundle\Configurator\EntityConfigurator;
use Coen\CrudBundle\Decorator\FormBuilderDecorator;
use Coen\CrudBundle\Reflection\ReflectionEntity;
use Coen\CrudBundle\Reflection\ReflectionProperty;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

final class EntityContext
{
    private EntityConfigurator $configurator;

    public function __construct(
        private readonly ReflectionEntity $reflection,
        private readonly EntityRepository $repository,
        private readonly string           $baseRoute,
    )
    {
        $this->configurator = new EntityConfigurator($this);
    }

    public function getReflection(): ReflectionEntity
    {
        return $this->reflection;
    }

    public function getBaseRoute(): string
    {
        return $this->baseRoute;
    }

    public function getRepository(): EntityRepository
    {
        return $this->repository;
    }

    public function getAppTemplate(): string
    {
        return $this->configurator->getAppTemplate();
    }

    public function getConfigurator(): EntityConfigurator
    {
        return $this->configurator;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('e');

        $this->configurator->doQueryBuilderConfig($queryBuilder);

        return $queryBuilder;
    }

    public function createNewEntity(): mixed
    {
        $entityClass = $this->reflection->getClass();
        $entity = new $entityClass();

        $this->configurator->doNewEntityConfiguration($entity);

        return $entity;
    }
}