<?php

namespace Coen\CrudBundle\Reflection;

use Coen\CrudBundle\Annotation\Entity;
use Coen\CrudBundle\Enum\CrudAction;
use Doctrine\ORM\Mapping\Column as ORMColumn;
use Doctrine\ORM\Mapping\Entity as ORMEntity;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Exception;
use ReflectionProperty as NotCrudReflectionProperty;

class ReflectionEntity
{
    protected \ReflectionClass $reflectionClass;
    protected Entity $crudAnnotation;
    protected ORMEntity $ormAnnotation;

    /** @var ReflectionProperty[] */
    protected array $properties;

    public function __construct(\ReflectionClass $reflectionClass)
    {
        $this->reflectionClass = $reflectionClass;

        $crudIndex = 0;
        $ormIndex = 1;
        list($this->crudAnnotation, $this->ormAnnotation) = array_reduce(
            $this->reflectionClass->getAttributes(),
            function ($carry, $attribute) use ($crudIndex, $ormIndex) {
                $attributeInstance = $attribute->newInstance();

                if ($attributeInstance instanceof Entity) {
                    $carry[$crudIndex] = $attributeInstance;
                } elseif ($attributeInstance instanceof ORMEntity) {
                    $carry[$ormIndex] = $attributeInstance;
                }

                return $carry;
            },
            [$crudIndex => new Entity(), $ormIndex => null]
        );

        $ormColumnAnnotationClasses = [
            ORMColumn::class,
            ManyToMany::class,
            ManyToOne::class,
            OneToMany::class,
            OneToOne::class
        ];

        $this->properties = array_map(
            function (NotCrudReflectionProperty $property) use ($ormColumnAnnotationClasses) {
                $isOrmProperty = false;
                foreach($property->getAttributes() as $attribute) {
                    $attributeInstance = $attribute->newInstance();

                    if(in_array(get_class($attributeInstance), $ormColumnAnnotationClasses)) {
                        $isOrmProperty = true;
                        break;
                    }
                }

                if($isOrmProperty) {
                    return new ReflectionProperty($this, $property);
                }

                return null;
            },
            $this->reflectionClass->getProperties()
        );

        $this->properties = array_filter($this->properties, function($element) {
            return !is_null($element);
        });
    }

    /**
     * @return ReflectionProperty[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param CrudAction $action
     * @return ReflectionProperty[]
     */
    public function getUsableProperties(CrudAction $action): array
    {
        return array_filter(
            $this->properties,
            function (ReflectionProperty $property) use ($action) {
                return $property->isUsableForAction($action);
            }
        );
    }

    public function getAccessRole(CrudAction $action): ?string
    {
        return $this->crudAnnotation->getAccessRole($action);
    }

    public function hasAction(CrudAction $action): bool
    {
        return $this->crudAnnotation->hasAction($action);
    }

    public function getIdentifier(): string
    {
        return strtolower($this->getName());
    }

    public function getClass(): string
    {
        return $this->reflectionClass->getName();
    }

    public function getNamespace(): string
    {
        return $this->reflectionClass->getNamespaceName();
    }

    public function getName(): string
    {
        return strtolower(
            str_replace($this->getNamespace() . '\\', '', $this->getClass())
        );
    }

    public function isFilterable(): bool
    {
        return $this->crudAnnotation->isFilterable();
    }

    public function getPropertyByName(string $propertyName)
    {
        foreach($this->properties as $property) {
            if($propertyName === $property->getName()) {
                return $property;
            }
        }
        return null;
    }
}