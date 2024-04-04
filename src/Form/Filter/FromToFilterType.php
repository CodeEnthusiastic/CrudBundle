<?php

namespace Coen\CrudBundle\Form\Filter;

use Coen\CrudBundle\Helper\FormBuilderLogger;
use Coen\CrudBundle\Reflection\ReflectionEntityProperty;
use Symfony\Component\Form\FormBuilderInterface;

class FromToFilterType extends AbstractFilterType
{
    protected function buildFilter(
        FormBuilderInterface $builder,
        array $options,
        ReflectionEntityProperty $property,
        FormBuilderLogger $formBuilderLogger,
    ): void
    {
        $config = $formBuilderLogger->all()[$property->getName()];
        $configOptions = $config['options'];
        $type = $config['type'];

        $configOptions['label'] = 'general.from';
        $builder->add('from', $type, $configOptions);

        $configOptions['label'] = 'general.to';
        $builder->add('to', $type, $configOptions);
    }
}