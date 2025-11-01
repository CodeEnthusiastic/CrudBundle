<?php

namespace Coen\CrudBundle\Form\Filter;

use Coen\CrudBundle\Form\FormBuilderLogger;
use Coen\CrudBundle\Reflection\ReflectionProperty;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class RangeFilterType extends AbstractFilterType
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
        $configOptions = $config['options'];
        $configOptions['required'] = false;

        $type = $config['type'];

        $configOptions['label'] = 'general.from';
        $builder->add('from', $type, $configOptions);

        $configOptions['label'] = 'general.to';
        $builder->add('to', $type, $configOptions);
    }

    public function appendFilterToQueryBuilder(QueryBuilder $qb, ReflectionProperty $property, mixed $data): void
    {
        $from = $data['from'];
        $to = $data['to'];
        $fieldAlias = $this->generateFieldAlias($property, $qb);

        if($from) {
            $fromParameter = $this->generateParameterName($property, 'FROM');
            $qb
                ->andWhere($qb->expr()->gt($fieldAlias, $fromParameter))
                ->setParameter($fromParameter, $from);
        }

        if($to) {
            $toParameter = $this->generateParameterName($property, 'to');
            $qb
                ->andWhere($qb->expr()->lt($fieldAlias, $toParameter))
                ->setParameter($toParameter, $to);
        }
    }
}