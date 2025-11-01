<?php

namespace Coen\CrudBundle\Configurator;

use Coen\CrudBundle\Decorator\FormBuilderDecorator;
use Coen\CrudBundle\Form\CrudType;
use Coen\CrudBundle\Helper\EntityContext;
use Coen\CrudBundle\Reflection\ReflectionEntity;
use Coen\CrudBundle\Reflection\ReflectionProperty;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;

class PropertyConfigurator
{
    private bool $hasDefaultFilter = false;
    private ?string $defaultFilterOrder = null;
    private mixed $defaultFilterCriteria = null;
    private \Closure $formCustomisation;
    private \Closure $collectionQueryBuilder;

    private array $choices = ['NO CHOICES CONFIGURATED' => 'NO CHOICES CONFIGURATED'];


    public function __construct(protected readonly ReflectionProperty $propertyReflection)
    {
        $this->formCustomisation = function() { };
        $this->collectionQueryBuilder = function() { };
    }

    public function setDefaultFilter(mixed $criteria, $order = 'ASC'): static
    {
        $this->hasDefaultFilter = true;
        $this->defaultFilterCriteria = $criteria;
        $this->defaultFilterOrder = $order;

        return $this;
    }

    public function setDefaultOrder(string $order = 'ASC'): static
    {
        $this->defaultFilterOrder = $order;

        return $this;
    }

    public function getDefaultFilterCriteria(): mixed
    {
        return $this->defaultFilterCriteria;
    }

    public function getDefaultOrder(): ?string
    {
        return $this->defaultFilterOrder;
    }

    public function doFormCustomisation(FormBuilderDecorator $formBuilder): void
    {
        ($this->formCustomisation)($formBuilder);
    }

    public function hasDefaultFilter(): bool
    {
        return $this->hasDefaultFilter;
    }

    public function setFormCustomisation(\Closure $formCustomisation): static
    {
        $this->formCustomisation = $formCustomisation;
        return $this;
    }

    public function asColorFieldType(): static
    {
        $this->setFormCustomisation(
            function(
                FormBuilderDecorator $builder
            ) {
                $builder->addForProperty(ColorType::class);
            }
        );
        return $this;
    }

    public function asChoiceFieldType(array $choices): static
    {
        $this->setFormCustomisation(
            function(
                FormBuilderDecorator $builder
            ) use ($choices) {
                $builder->addForProperty(ChoiceType::class, ['choices' => $choices]);
            }
        );
        return $this;
    }

    public function setChoices(array $choices): static
    {
        if($this->propertyReflection->getFormType() !== 'array') {
            throw new \Exception('Property ' . $this->propertyReflection->getName() . ' has wrong Type for ' . __FUNCTION__ . '().');
        }

        $this->choices = $choices;
        return $this;
    }

    public function getChoices(): array
    {
        return $this->choices;
    }

    public function setCollectionQueryBuilder(\Closure $collectionQueryBuilder): self
    {
        $this->collectionQueryBuilder = $collectionQueryBuilder;
        return $this;
    }

    public function getClosureForCollectionQueryBuilder(): \Closure
    {
        return $this->collectionQueryBuilder;
    }

    public function removeFromForm(): void
    {
        $this->setFormCustomisation(function (FormBuilderDecorator $formBuilder) {
            $formBuilder->removeProperty();
        });
    }

    public function asCollectionField(EntityManagerInterface $entityManager, \Closure $configure): void
    {
        $this->setFormCustomisation(
            function (FormBuilderDecorator $formBuilder) use($entityManager, $configure) {
                $class = $formBuilder->getCurrentProperty()->getTargetEntity();

                $entityContext = new EntityContext(
                    new ReflectionEntity(new ReflectionClass($class)),
                    $entityManager->getRepository($class),
                    ''
                );

                ($configure)($entityContext->getConfigurator());

                $formBuilder->addForProperty(CollectionType::class, [
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'entry_type' => CrudType::class,
                    'entry_options' => [
                        'data_class' => $class,
                        'current_action' => $formBuilder->getCurrentAction(),
                        'entity_context' => $entityContext,
                    ]
                ]);
            }
        );
    }
}