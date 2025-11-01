<?php

namespace Coen\CrudBundle\Configurator;

use Coen\CrudBundle\Decorator\FormBuilderDecorator;
use Coen\CrudBundle\Helper\EntityContext;
use Coen\CrudBundle\Reflection\ReflectionProperty;
use Doctrine\ORM\QueryBuilder;

final class EntityConfigurator
{
    protected array $properties = [];
    protected string $appTemplate = '';
    protected \Closure $queryBuilderConfig;
    private \Closure $newEntityConfiguration;
    private \Closure $formCustomisation;

    public function __construct(protected readonly EntityContext $entityContext)
    {
        $this->queryBuilderConfig = function () {};
        $this->newEntityConfiguration = function () {};
        $this->formCustomisation = function () {};
    }

    public function property(string $name): PropertyConfigurator
    {
        $reflection = $this->entityContext->getReflection();
        $property = $reflection->getPropertyByName($name);

        if($property === null) {
            $class = $reflection->getClass();

            $names = [];
            foreach($reflection->getProperties() as $property) {
                $names[] = $property->getName();
            }

            throw new \Exception("Property with Name '$name' not exist for Entity '$class' existing Property: " . implode(', ', $names));
        }

        $configurator = new PropertyConfigurator($property);
        $this->properties[$property->getIdentifier()] = $configurator;

        return $configurator;
    }

    public function setAppTemplate(string $appBaseTemplate): EntityConfigurator
    {
        $this->appTemplate = $appBaseTemplate;
        return $this;
    }

    public function setQueryBuilder(\Closure $closure): EntityConfigurator
    {
        $this->queryBuilderConfig = $closure;
        return $this;
    }

    public function getAppTemplate(): string
    {
        return $this->appTemplate;
    }

    public function doQueryBuilderConfig(QueryBuilder $qb): void
    {
        ($this->queryBuilderConfig)($qb);
    }

    public function setNewEntityConfiguration(\Closure $newEntityConfiguration): EntityConfigurator
    {
        $this->newEntityConfiguration = $newEntityConfiguration;

        return $this;
    }

    public function doNewEntityConfiguration(object $entity): void
    {
        ($this->newEntityConfiguration)($entity);
    }

    public function setFormCustomisation(\Closure $formCustomisation): void
    {
        $this->formCustomisation = $formCustomisation;
    }

    public function doFormConfiguration(FormBuilderDecorator $crudBuilder): void
    {
        ($this->formCustomisation)($crudBuilder);
    }

    public function doPropertyFormConfiguration(ReflectionProperty $property, FormBuilderDecorator $builder): void
    {
        $propertyConfiguration = $this->getProperty($property);

        if(null !== $propertyConfiguration) {
            $propertyConfiguration->doFormCustomisation($builder);
        }
    }

    /**
     * @return PropertyConfigurator[]|array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getProperty(ReflectionProperty $property): ?PropertyConfigurator
    {
        $identifier = $property->getIdentifier();
        if(array_key_exists($identifier, $this->properties)) {
            return $this->properties[$identifier];
        }

        return null;
    }
}