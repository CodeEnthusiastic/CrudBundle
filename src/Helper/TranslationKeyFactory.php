<?php

namespace Coen\CrudBundle\Helper;
use Coen\CrudBundle\Enum\CrudAction;
use Coen\CrudBundle\Reflection\ReflectionEntity;
use Coen\CrudBundle\Reflection\ReflectionEntityProperty;

class TranslationKeyFactory {
    public const TITLE = 'crud.entity.{entityName}.title.{action}';
    public const ENTITY = 'crud.entity.{entityName}.name';
    public const PROPERTY = 'crud.entity.{entityName}.property.{propertyName}';
    public const ACTION = 'crud.action.{action}';
    public const TEXT = 'crud.label.{key}';

    private ReflectionEntity $entityReflection;

    public function __construct(ReflectionEntity $entityReflection)
    {
        $this->entityReflection = $entityReflection;
    }

    static public function tagResolver(string $translationKeyPattern)
    {
        foreach (func_get_args() as $value) {
            if($value instanceof CrudAction) {
                $replaces['{action}'] = $value->value;
            }

            if($value instanceof ReflectionEntity) {
                $replaces['{entityName}'] = $value->getIdentifier();
            }

            if($value instanceof ReflectionEntityProperty) {
                $replaces['{propertyName}'] = $value->getIdentifier();
            }
        }

        return str_replace(array_keys($replaces), array_values($replaces), $translationKeyPattern);
    }

    public function getTitle(CrudAction $action): string
    {
        return $this->tagResolver(self::TITLE, $this->entityReflection, $action);
    }

    public function getProperty(ReflectionEntityProperty $reflectionEntityProperty): string
    {
        return $this->tagResolver(self::PROPERTY, $this->entityReflection, $reflectionEntityProperty);
    }

    public function getPropertyByString(string $propertyIdentifier): string
    {
        $key = $this->tagResolver(self::PROPERTY, $this->entityReflection);
        return str_replace('{propertyName}', $propertyIdentifier, $key);
    }

    public function getAction(CrudAction $action): string
    {
        return $this->tagResolver(self::ACTION, $action);
    }

    public function getSave(): string
    {
        return str_replace( '{action}', 'save', self::ACTION);
    }

    public function getText($key): string
    {
        return str_replace( '{key}', $key, self::TEXT);
    }


}