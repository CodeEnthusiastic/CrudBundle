<?php

namespace Coen\CrudBundle\Form\Filter;
use Coen\CrudBundle\Helper\FormBuilderLogger;
use Coen\CrudBundle\Reflection\ReflectionEntityProperty;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class BooleanFilterType extends AbstractFilterType
{
    protected function buildFilter(FormBuilderInterface $builder, array $options, ReflectionEntityProperty $property, FormBuilderLogger $formBuilderLogger,): void
    {
        $builder->add('criteria', ChoiceType::class, ['choices' => [
            '' => null,
            'true' => true,
            'false' => false,
        ]]);
    }
}