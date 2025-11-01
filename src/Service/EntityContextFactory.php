<?php

namespace Coen\CrudBundle\Service;

use Coen\CrudBundle\Helper\EntityContext;
use Coen\CrudBundle\Reflection\ReflectionEntity;
use Doctrine\ORM\EntityManagerInterface;

class EntityContextFactory
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $reflectionCLass = ReflectionEntity::class
    )
    { }

    public function create(string $entityClass, string $baseRoute): EntityContext
    {
        $repository = $this->entityManager->getRepository($entityClass);

        if($repository === null) {
            throw new \Exception("Can't find Repository for class '" . $entityClass . "', maybe is not a doctrine entity?");
        }

        return new EntityContext(
            new ($this->reflectionCLass)(new \ReflectionClass($entityClass)),
            $repository,
            $baseRoute,
        );
    }
}