<?php

namespace Coen\CrudBundle\Form;
use Coen\CrudBundle\Enum\CrudAction;
use Coen\CrudBundle\Helper\TranslationKeyFactory;
use Coen\CrudBundle\Reflection\ReflectionEntity;
use Coen\CrudBundle\Reflection\ReflectionEntityProperty;
use Coen\CrudBundle\Repository\RepositoryCollectionQueryBuilderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
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

class CrudEntityType extends AbstractType
{
    /**
     * @throws Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var CrudAction $action */
        $action = $options['crud_action'];
        /** @var ReflectionEntity $entityReflection */
        $entityReflection = $options['entity_reflection'];

        foreach($entityReflection->getUsableProperties($action) as $property) {
            $this->addProperty($builder, $property, $options);
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

    protected function addDefaultField(
        FormBuilderInterface $builder,
        array $options,
        ReflectionEntityProperty $property,
        ServiceEntityRepository $repository
    )
    {
        $type = $property->getFormType();
        $propertyName = $property->getName();

        echo $propertyName . "<br>";
        var_dump($options);
        echo "<hr>";

        switch ($type) {
            case 'int':
                $builder->add($propertyName, IntegerType::class, $options);
                break;

            case 'float':
                $options['scale'] = $property->getOrmScale();
                $builder->add($propertyName, NumberType::class, $options);
                break;

            case 'bool':
                $builder->add($propertyName, CheckboxType::class, $options);
                break;

            case 'string':
                $builder->add($propertyName, TextType::class, $options);
                break;

            case 'text':
                $builder->add($propertyName, TextareaType::class, $options);
                break;

            case 'array':
                $options['choices'] = ['IMPLEMENT' => 'Please add a Form Customisation',];
                $builder->add($propertyName, ChoiceType::class, $options);
                break;

            case 'date':
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

                $builder->add($propertyName, $formType, $options);
                break;

            case 'enum':
                $options['class'] = $property->getEnumType();
                $builder->add($propertyName, EnumType::class, $options);
                break;

            case 'collection':
                $collectionType = $property->getCollectionType();

                if(in_array($collectionType, [ManyToMany::class, OneToMany::class])) {
                    $options['multiple'] = true;
                    $options['expanded'] = true;
                }

                $options['class'] = $property->getTargetEntity();
                $options['query_builder'] = $this->addQueryBuilder($property, $repository);

                $builder->add($propertyName, EntityType::class, $options);
                break;

            default:
                throw new \Exception('Unknown Property Type for "' . $propertyName . '" with type "' . $type . '" and orm type "' . $property->getOrmType() . '"');
        }
    }

    protected function addQueryBuilder(ReflectionEntityProperty $property, ServiceEntityRepository $repository): ?callable
    {
        if($repository instanceof RepositoryCollectionQueryBuilderInterface) {
            $queryBuilder = $repository->getPropertyCollectionQueryBuilder($property);

            if(null !== $queryBuilder) {
                return $queryBuilder;
            }
        }

        return null;
    }

    private function addCustomisationField(
        FormBuilderInterface $builder,
        array $defaultOptions,
        ReflectionEntityProperty $property,
        array $formCustomisation
    ): void
    {
        $formCustomisation[$property->getName()](
            $builder,
            $defaultOptions,
            $property
        );
    }

    protected function hasCustomisationField(
        ReflectionEntityProperty $property,
        array $formCustomisation
    ): bool
    {
        return array_key_exists($property->getName(), $formCustomisation);
    }

    protected function addProperty(FormBuilderInterface $builder, ReflectionEntityProperty $property, array $options)
    {
        /** @var ReflectionEntity $entityReflection */
        $entityReflection = $options['entity_reflection'];
        /** @var array $formCustomisation */
        $formCustomisation = $options['form_customisation'];
        /** @var ServiceEntityRepository $repository */
        $repository = $options['entity_repository'];

        echo "<pre>";

        $defaultOptions = [
            'required' => $property->isRequired(),
            'disabled' => $property->isDisabled(),
            'label' => TranslationKeyFactory::tagResolver(TranslationKeyFactory::PROPERTY, $entityReflection, $property)
        ];

        if($this->hasCustomisationField($property, $formCustomisation)) {
            $this->addCustomisationField($builder, $defaultOptions, $property, $formCustomisation);
        }

        if(!$builder->has($property->getName())) {
            $this->addDefaultField($builder, $defaultOptions, $property, $repository);
        }

        echo "</pre>";

    }

    protected function createDefaultOptions(ReflectionEntity $entityReflection, ReflectionEntityProperty $property): array
    {
        return [
            'required' => $property->isRequired(),
            'disabled' => $property->isDisabled(),
            'label' => TranslationKeyFactory::tagResolver(TranslationKeyFactory::PROPERTY, $entityReflection, $property)
        ];
    }
}