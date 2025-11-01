<?php

namespace Coen\CrudBundle\Form;

use Coen\CrudBundle\Helper\EntityContext;
use Coen\CrudBundle\Enum\CrudAction;
use Exception;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterType extends AbstractType
{
    protected CrudAction $currentAction;
    protected EntityContext $entityContext;
    protected array $options;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityContext = $options['entity_context'];

        if(!$entityContext instanceof EntityContext) {
            throw new Exception('Wrong class for Property \'entityContext\' expect \'' . EntityContext::class . '\' got ' . get_class($entityContext) . '\'');
        }

        foreach($entityContext->getReflection()->getUsableProperties(CrudAction::LIST) as $property) {
            $builder->add($property->getName(), $property->getFilterType(), [
                'current_action' => CrudAction::LIST,
                'entity_context' => $entityContext,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entity_context' => null
        ]);

        $resolver->setAllowedTypes('entity_context', [EntityContext::class]);
    }
}