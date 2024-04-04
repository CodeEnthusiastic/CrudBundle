<?php

namespace Coen\CrudBundle\Form\Filter;

use Coen\CrudBundle\Helper\FormBuilderLogger;
use Coen\CrudBundle\Reflection\ReflectionEntityProperty;
use Symfony\Component\Form\FormBuilderInterface;

class DefaultFilterType extends AbstractFilterType
{
    protected function buildFilter(
        FormBuilderInterface $builder,
        array $options,
        ReflectionEntityProperty $property,
        FormBuilderLogger $formBuilderLogger,
    ): void
    {
        $config = $formBuilderLogger->all()[$property->getName()];

        $builder->add('criteria',  $config['type'], $config['options']);
    }
}