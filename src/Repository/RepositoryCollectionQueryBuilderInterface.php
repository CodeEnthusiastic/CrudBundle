<?php

namespace Coen\CrudBundle\Repository;
use Coen\CrudBundle\Reflection\ReflectionEntityProperty;

interface RepositoryCollectionQueryBuilderInterface
{
    public function getPropertyCollectionQueryBuilder(ReflectionEntityProperty $property): ?callable;
}