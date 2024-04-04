<?php

namespace Coen\CrudBundle\Form\Filter;
use Coen\CrudBundle\Form\CrudEntityType;
use Coen\CrudBundle\Helper\FormBuilderLogger;
use Coen\CrudBundle\Reflection\ReflectionEntityProperty;
use Exception;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractFilterType extends CrudEntityType
{
    /**
     * @throws Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $formBuilderLogger = new FormBuilderLogger($options);

        $this->addProperty($formBuilderLogger, $options['property'], $options);

        $this->buildFilter($builder, $options, $options['property'], $formBuilderLogger);

        $builder->add('order', HiddenType::class, ['required' => false]);
        $builder->add('filter', HiddenType::class, ['data' => get_class($this)]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'property' => null,
            'with_order' => true,
        ]);

        $resolver->setAllowedTypes('property', [ReflectionEntityProperty::class]);
        $resolver->setAllowedTypes('with_order', ['boolean']);
    }

    protected abstract function buildFilter(
        FormBuilderInterface $builder,
        array $options,
        ReflectionEntityProperty $property,
        FormBuilderLogger $formBuilderLogger,
    ): void;
}