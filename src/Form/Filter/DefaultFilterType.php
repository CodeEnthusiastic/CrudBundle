<?php

namespace Coen\CrudBundle\Form\Filter;

use Coen\CrudBundle\Form\FormBuilderLogger;
use Coen\CrudBundle\Reflection\ReflectionProperty;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class DefaultFilterType extends AbstractFilterType
{
    protected function buildFilter(
        FormBuilderInterface $builder,
        FormBuilderLogger    $formBuilderLogger,
        array $options
    ): void
    {
        $config = $this->getPropertyBuilderConfig(
            $this->entityContext->getReflection()->getPropertyByName($builder->getName()),
            $formBuilderLogger
        );
        $builder->add('criteria',  $config['type'], array_merge($config['options'], ['required' => false]));
    }

    public function appendFilterToQueryBuilder(QueryBuilder $qb, ReflectionProperty $property, mixed $data): void
    {
        $criteria = $data['criteria'];
        if($criteria) {
            $parameter = $this->generateParameterName($property);
            $qb
                ->andWhere($qb->expr()->eq($this->generateFieldAlias($property, $qb), $parameter))
                ->setParameter($parameter, $criteria);
        }
    }
}