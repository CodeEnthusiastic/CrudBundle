<?php

namespace Coen\CrudBundle\Form\Filter;

use Coen\CrudBundle\Helper\FormBuilderLogger;
use Coen\CrudBundle\Reflection\ReflectionEntityProperty;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;

class CollectionFilterType extends AbstractFilterType
{
    protected function buildFilter(
        FormBuilderInterface $builder,
        array $options,
        ReflectionEntityProperty $property,
        FormBuilderLogger $formBuilderLogger,
    ): void
    {
        $config = $formBuilderLogger->all()[$property->getName()];
        $type = $config['type'];
        $configOptions = $config['options'];

        if($type === EntityType::class) {
            $configOptions['multiple'] = true;
            $configOptions['expanded'] = false;
        }

        $builder->add('criteria', $type, $configOptions);
    }
}