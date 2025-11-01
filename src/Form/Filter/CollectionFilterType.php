<?php

namespace Coen\CrudBundle\Form\Filter;

use Coen\CrudBundle\Form\FormBuilderLogger;
use Coen\CrudBundle\Reflection\ReflectionProperty;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;

class CollectionFilterType extends AbstractFilterType
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
        $type = $config['type'];

        $configOptions = $config['options'];
        if($type === EntityType::class) {
            $configOptions['multiple'] = true;
            $configOptions['expanded'] = false;
            $configOptions['required'] = false;
        }

        $builder->add('criteria', $type, $configOptions);
    }

    public function appendFilterToQueryBuilder(QueryBuilder $qb, ReflectionProperty $property, mixed $data): void
    {
        $criteria = $data['criteria'];
        if($criteria instanceof Collection) {
            $criteria = $criteria->toArray();

            if(count($criteria) > 0) {
                $tableAlias = $property->getColumnName() . '_t';
                $qb
                    ->join($this->generateFieldAlias($property, $qb), $tableAlias);

                $ids = array_map(function($entity) {
                    return $entity->getId();
                }, $criteria);

                $qb->andWhere($qb->expr()->in($tableAlias . '.id', $ids));
            }
        }
    }
}