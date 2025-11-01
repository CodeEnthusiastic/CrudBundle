<?php

namespace Coen\CrudBundle\Form\Filter;

use Coen\CrudBundle\Form\CrudType;
use Coen\CrudBundle\Form\FormBuilderLogger;
use Coen\CrudBundle\Reflection\ReflectionProperty;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

abstract class AbstractFilterType extends CrudType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->currentAction = $options['current_action'];
        $this->entityContext = $options['entity_context'];

        $formBuilderLogger = new FormBuilderLogger($builder, $this->entityContext, $this->currentAction);

        $this->buildProperty($formBuilderLogger, $this->entityContext->getReflection()->getPropertyByName($builder->getName()));

        $this->buildFilter($builder, $formBuilderLogger, $options);

        $builder->add('order', HiddenType::class, ['required' => false]);
    }

    protected function getPropertyBuilderConfig(ReflectionProperty $property, FormBuilderLogger $formBuilderLogger): array
    {
        return $formBuilderLogger->getLoggedChildren()[$property->getName()];
    }

    protected function generateFieldAlias(ReflectionProperty $property, QueryBuilder $qb): string
    {
        return $qb->getAllAliases()[0] . '.' . $property->getColumnName();
    }

    protected function generateParameterName(ReflectionProperty $property, string $suffix = null): string
    {
        return strtoupper(':' . $property->getName() . ($suffix ? '_' . $suffix : ''));
    }

    protected abstract function buildFilter(
        FormBuilderInterface $builder,
        FormBuilderLogger    $formBuilderLogger,
        array $options
    ): void;

    public function appendToQueryBuilder(QueryBuilder $qb, ReflectionProperty $property, mixed $data): void
    {
        if($data['order']) {
            $qb->addOrderBy($this->generateFieldAlias($property, $qb), $data['order']);
        }

        $this->appendFilterToQueryBuilder($qb, $property, $data);
    }

    public abstract function appendFilterToQueryBuilder(QueryBuilder $qb, ReflectionProperty $property, mixed $data);
}