<?php

namespace Coen\CrudBundle\Form;
use Coen\CrudBundle\Enum\CrudAction;
use Coen\CrudBundle\Reflection\ReflectionEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Exception;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterType extends AbstractType
{
    /**
     * @throws Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var ReflectionEntity $entityReflection */
        $entityReflection = $options['entity_reflection'];

        $subOptions = [
            'crud_action' => $options['crud_action'],
            'entity_reflection' =>  $options['entity_reflection'],
            'entity_repository' =>  $options['entity_repository'],
            'form_customisation' =>  $options['form_customisation'],
            'required' => false
        ];

        foreach($entityReflection->getUsableProperties(CrudAction::LIST) as $property) {
            $subOptions['property'] = $property;

            $builder->add($property->getName(), $property->getFilterType(), $subOptions);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'crud_action' => null,
            'entity_reflection' => null,
            'entity_repository' => null,
            'form_customisation' => null,
        ]);

        $resolver->setAllowedTypes('crud_action', [CrudAction::class]);
        $resolver->setAllowedTypes('entity_reflection', [ReflectionEntity::class]);
        $resolver->setAllowedTypes('entity_repository', [ServiceEntityRepository::class]);
        $resolver->setAllowedTypes('form_customisation', ['array']);
    }
}