<?php

namespace Coen\CrudBundle\Entity;

trait InheritablePropertyTrait
{
    private function getInheritablePropertyValue(string $getter, ?self $parent) {
        $property = $this->getPropertyName($getter);
        $currentValue = $this->$property;

        if($parent !== null) {
            $parentValue = $parent->$getter();

            if(!$currentValue && $parentValue) {
                return $parentValue;
            }
        }

        return $currentValue;
    }

    private function setInheritablePropertyValue(string $setter, ?self $parent, mixed $value) {
        $property = $this->getPropertyName($setter);

        if($parent !== null) {
            $getter = str_replace('set', 'get', $setter);
            if($value === $parent->$getter()) {
                return;
            }
        }

        $this->$property = $value;
    }

    private function getPropertyName(string $function): mixed {
        $property = str_replace(['get', 'set'], '', $function);
        return strtolower($property[0]) . substr($property, 1);
    }
}