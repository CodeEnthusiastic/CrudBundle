<?php

namespace Coen\CrudBundle\Form;

use Coen\CrudBundle\Decorator\FormBuilderDecorator;
use Coen\CrudBundle\Helper\EntityContext;
use Coen\CrudBundle\Enum\CrudAction;
use Coen\CrudBundle\Reflection\ReflectionProperty;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Exception;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CrudType extends AbstractType
{
    protected CrudAction $currentAction;
    protected EntityContext $entityContext;

    /**
     * @throws Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->currentAction = $options['current_action'];
        $this->entityContext = $options['entity_context'];

        $crudBuilder = new FormBuilderDecorator($builder, $this->entityContext, $this->currentAction);

        $this->entityContext->getConfigurator()->doFormConfiguration($crudBuilder);

        foreach($this->entityContext->getReflection()->getUsableProperties($this->currentAction) as $property) {
            $this->buildProperty($crudBuilder, $property);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'current_action' => null,
            'entity_context' => null
        ]);

        $resolver->setAllowedTypes('current_action', [CrudAction::class]);
        $resolver->setAllowedTypes('entity_context', [EntityContext::class]);
    }

    /**
     * @throws Exception
     */
    protected function buildProperty(FormBuilderDecorator $builder, ReflectionProperty $property): void
    {
        $builder->setCurrentProperty($property);

        $this->entityContext->getConfigurator()->doPropertyFormConfiguration($property, $builder);

        if($builder->needsDefaultField()) {
            $this->addPropertyField($builder);
        }
    }

    private function addPropertyField(FormBuilderDecorator $crudBuilder): void
    {
        $property = $crudBuilder->getCurrentProperty();
        switch ($property->getFormType()) {
            case 'int':
                $crudBuilder->addForProperty(IntegerType::class);
                break;

            case 'float':
                $crudBuilder->addForProperty(NumberType::class, [
                    'scale' => $property->getOrmScale()
                ]);
                break;

            case 'bool':
                $crudBuilder->addForProperty(CheckboxType::class);
                break;

            case 'string':
                $crudBuilder->addForProperty(TextType::class);
                break;

            case 'text':
                $crudBuilder->addForProperty(TextareaType::class);
                break;

            case 'array':
                $crudBuilder->addForProperty(ChoiceType::class, [
                    'choices' => $this->entityContext->getConfigurator()->getProperty($property)?->getChoices() ?? [],
                    'multiple' => true
                ]);
                break;

            case 'date':
                $options = [];
                $widgetType = 'single_text';
                switch ($property->getOrmType()) {
                    case Types::DATE_MUTABLE:
                    case Types::DATE_IMMUTABLE:
                        $formType = DateType::class;
                        $options['widget'] = $widgetType;
                        break;

                    case Types::TIME_MUTABLE:
                    case Types::TIME_IMMUTABLE:
                        $formType = TimeType::class;
                        $options['widget'] = $widgetType;
                        break;

                    case Types::DATETIME_MUTABLE:
                    case Types::DATETIME_IMMUTABLE:
                    default:
                        $formType = DateTimeType::class;
                        $options['time_widget'] = $widgetType;
                        $options['date_widget'] = $widgetType;
                        break;
                }

                $crudBuilder->addForProperty($formType, $options);
                break;

            case 'enum':
                $crudBuilder->addForProperty(EnumType::class, [
                    'class' => $property->getEnumType()
                ]);
                break;

            case 'collection':
                $collectionType = $property->getCollectionType();
                $isMany = in_array($collectionType, [ManyToMany::class, OneToMany::class]);
                $class = $property->getTargetEntity();

                if(!class_exists($class)) {
                    throw new Exception('Class "' . $class . '" for Property ' . $property->getName() . ' not exists.');
                }

                $crudBuilder->addForProperty(EntityType::class, [
                    'class' => $class,
                    'query_builder' => $this->entityContext->getConfigurator()->getProperty($property)?->getClosureForCollectionQueryBuilder() ?? null,
                    'multiple' => $isMany,
                    'expanded' => $isMany,
                ]);
                break;

            default:
                throw new \Exception('Unknown Property Type for "' . $property->getName() . '" with type "' . $property->getFormType() . '" and orm type "' . $property->getOrmType() . '"');
        }
    }
}