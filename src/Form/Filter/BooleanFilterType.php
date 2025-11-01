<?php

namespace Coen\CrudBundle\Form\Filter;
use Coen\CrudBundle\Form\FormBuilderLogger;
use Coen\CrudBundle\Reflection\ReflectionProperty;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class BooleanFilterType extends AbstractFilterType
{
    protected function buildFilter(
        FormBuilderInterface $builder,
        FormBuilderLogger    $formBuilderLogger,
        array $options
    ): void
    {
        $builder->add('criteria', ChoiceType::class, [
            'required' => false,
            'choices' => [
                'true' => true,
                'false' => false,
            ]
        ]);
    }

    public function appendFilterToQueryBuilder(QueryBuilder $qb, ReflectionProperty $property, mixed $data): void
    {
        $fieldAlias = $this->generateFieldAlias($property, $qb);

        if($data['criteria'] === 'true') {
            $qb
                ->andWhere($qb->expr()->eq($fieldAlias, 'true'));
        }

        if($data['criteria'] === 'false') {
            $qb
                ->andWhere($qb->expr()->eq($fieldAlias, 'true'));
        }
    }
}