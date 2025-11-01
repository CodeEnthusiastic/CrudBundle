<?php

namespace Coen\CrudBundle\Reflection;

use Coen\CrudBundle\Form\Filter\BooleanFilterType;
use Coen\CrudBundle\Form\Filter\CollectionFilterType;
use Coen\CrudBundle\Form\Filter\DefaultFilterType;
use Coen\CrudBundle\Form\Filter\RangeFilterType;
use Coen\CrudBundle\Generator\TranslationKeyGenerator;
use Coen\CrudBundle\Annotation\Column;
use Coen\CrudBundle\Enum\CrudAction;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column as ORMColumn;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use ReflectionClass;

class ReflectionProperty
{
    private ReflectionEntity $reflectionEntity;
    private \ReflectionProperty $reflectionProperty;
    private Column $crudAnnotation;
    private ?ORMColumn $ormAnnotation;
    private ManyToMany|ManyToOne|OneToMany|OneToOne|null $ormMappingAnnotation;

    public function __construct(
        ReflectionEntity $reflectionEntity,
        \ReflectionProperty $reflectionProperty,
    )
    {
        $this->reflectionEntity = $reflectionEntity;
        $this->reflectionProperty = $reflectionProperty;

        list($crudAnnotation, $ormAnnotation, $ormMappingAnnotation) = $this->searchForAnnotations();

        $this->crudAnnotation = $crudAnnotation;
        $this->ormAnnotation = $ormAnnotation;
        $this->ormMappingAnnotation = $ormMappingAnnotation;
    }

    public function getName(): string
    {
        return $this->reflectionProperty->getName();
    }

    public function getIdentifier(): string
    {
        return strtolower($this->getName());
    }

    public function getColumnName(): string
    {
        return $this->ormAnnotation->name ?? $this->getName();
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function isRequired(): bool
    {
        return $this->crudAnnotation->isRequired();
    }

    public function isDisabled(): bool
    {
        return $this->crudAnnotation->isDisabled();
    }

    public function getCollectionType(): string
    {
        return get_class($this->ormMappingAnnotation) ?? '';
    }

    public function getCollection(): string
    {
        return get_class($this->ormMappingAnnotation) ?? '';
    }

    /**
     * @throws \ReflectionException
     */
    public function getInverseProperty(): ReflectionProperty
    {
        $propertyName = $this->ormMappingAnnotation->inversedBy ?? $this->ormMappingAnnotation->mappedBy ?? '';
        $otherReflectionClass = new ReflectionEntity(new ReflectionClass($this->getTargetEntity()));

        return $otherReflectionClass->getPropertyByName($propertyName);
    }

    public function isMappedSite(): bool
    {
        return (bool) $this->ormMappingAnnotation->mappedBy ?? false;
    }

    public function getTargetEntity(): ?string
    {
        $entityClass = $this->ormMappingAnnotation->targetEntity ?? $this->getType();

        if(in_array(get_class($this->ormMappingAnnotation), [
            OneToOne::class,
            ManyToOne::class
        ])) {
            $entityClass = $this->ormMappingAnnotation->inversedBy ? $this->getType() : $entityClass;
        }

        return $entityClass;
    }

    public function isId(): bool
    {
        $annotations = array_filter($this->reflectionProperty->getAttributes(), function ($element) {
            return $element->getName() === Id::class;
        });

        if(count($annotations) > 0 && $annotations[0]->newInstance() instanceof Id) {
            return true;
        }

        return false;
    }

    public function getFormType(): string
    {
        $type = $this->getType();

        if($this->getOrmType() === 'text') {
            $type = 'text';
        }

        if($this->ormMappingAnnotation !== null) {
            $type = 'collection';
        }

        if($this->getEnumType()) {
            $type = 'enum';
        }

        if($this->isDate() || $this->isTime() || $this->isDateTime()) {
            $type = 'date';
        }

        return $type;
    }

    public function getType(): string
    {
        $type = $this->reflectionProperty->getType()->getName();
        return $type == 'self' ? $this->reflectionEntity->getClass() : $type;
    }

    public function getOrmType(): string
    {
        return $this->ormAnnotation->type ?? '';
    }

    public function getEnumType(): string
    {
        return $this->ormAnnotation->enumType ?? '';
    }

    public function getValue(object $entity): mixed
    {
        if(!get_class($entity) == $this->reflectionEntity->getClass()) {
            throw new \Exception('Expect object of class' . $this->reflectionEntity->getClass() . ' get object of class ' . get_class($entity));
        }

        return $this->get($entity);
    }

    public function get(object $entity): mixed
    {
        $getter = 'get' . $this->getName();

        if($this->getType() == 'bool') {
            $getter = 'is' . $this->getName();
        }

        if($this->crudAnnotation->hasGetter()) {
            $getter = $this->crudAnnotation->getGetter();
        }

        return $entity->$getter();
    }

    public function set(object &$entity, mixed $value): void
    {
        $setter = 'set' . $this->getName();

        if($this->getType() == 'bool') {
            $setter = 'is' . $this->getName();
        }

        if($this->getFormType() == 'collection' and !method_exists($entity, $setter)) {
            $setter = 'add' . trim($this->getName(), 's');
        }

        if($this->crudAnnotation->hasSetter()) {
            // TODO ???
            $setter = $this->crudAnnotation->getGetter();
        }

        $entity->$setter($value);
    }

    public function add(object &$entity, mixed $value): void
    {
        $adder = 'add' . trim($this->getName(), 's');
        $entity->$adder($value);
    }

    public function remove(object &$entity, mixed $value): void
    {
        $remover = 'remove' . trim($this->getName(), 's');
        $entity->$remover($value);
    }

    public function isUsableForAction(CrudAction $action): bool
    {
        return $this->crudAnnotation->isUsableForAction($action) && !$this->isId();
    }

    public function isDateTime(): bool
    {
        return in_array($this->getOrmType(), [Types::DATETIME_MUTABLE, Types::DATETIME_IMMUTABLE]);
    }

    public function isDate(): bool
    {
        return in_array($this->getOrmType(), [Types::DATE_MUTABLE, Types::DATE_IMMUTABLE]);
    }

    public function isTime(): bool
    {
        return in_array($this->getOrmType(), [Types::TIME_MUTABLE, Types::TIME_IMMUTABLE]);
    }

    public function setListable(bool $listable)
    {
        $this->crudAnnotation->setListable($listable);
    }

    private function searchForAnnotations(): array
    {
        $crudIndex = 0;
        $ormIndex = 1;
        $ormMappingIndex = 2;

        $ormMappingClasses = [
            ManyToMany::class,
            ManyToOne::class,
            OneToMany::class,
            OneToOne::class
        ];

        return array_reduce(
            $this->reflectionProperty->getAttributes(),
            function ($carry, $attribute) use ($crudIndex, $ormIndex, $ormMappingIndex, $ormMappingClasses) {
                $attributeInstance = $attribute->newInstance();

                if ($attributeInstance instanceof Column) {
                    $carry[$crudIndex] = $attributeInstance;
                } elseif ($attributeInstance instanceof OrmColumn) {
                    $carry[$ormIndex] = $attributeInstance;
                }  elseif (in_array(get_class($attributeInstance), $ormMappingClasses)) {
                    $carry[$ormMappingIndex] = $attributeInstance;
                }

                return $carry;
            },
            [$crudIndex => new Column(), $ormIndex => new ORMColumn(), $ormMappingIndex => null]
        );
    }

    public function getOrmScale(): int
    {
        return $this->ormAnnotation->scale ?? 2;
    }

    public function getFilterType(): string
    {
        $filterType = $this->crudAnnotation->getFilterType();

        if(null === $filterType) {
            $filterType = match ($this->getFormType()) {
                'date', 'int', 'float' => RangeFilterType::class,
                'bool' => BooleanFilterType::class,
                'collection' => CollectionFilterType::class,
                default => DefaultFilterType::class,
            };
        }

        return $filterType;
    }

    public function getTranslationKey(): string
    {
        return TranslationKeyGenerator::tagResolver(TranslationKeyGenerator::PROPERTY, $this->reflectionEntity, $this);
    }
}