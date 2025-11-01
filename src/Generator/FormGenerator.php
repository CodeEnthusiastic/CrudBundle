<?php

namespace Coen\CrudBundle\Generator;

use Coen\CrudBundle\Form\CrudType;
use Coen\CrudBundle\Form\FilterType;
use Coen\CrudBundle\Helper\EntityContext;
use Coen\CrudBundle\Enum\CrudAction;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class FormGenerator
{
    public function __construct(
        protected readonly EntityContext        $entityContext,
        protected readonly FormFactoryInterface $formBuilder,
        protected readonly Request              $request,
        protected readonly EntityManager        $entityManager
    ) { }

    public function createUpdateForm(object $entity, array $options = []): FormInterface
    {
        return $this->formBuilder->create(CrudType::class, $entity, array_merge($options, [
            'entity_context' => $this->entityContext,
            'current_action' => CrudAction::UPDATE,
        ]));
    }

    public function createCreateForm(array $options = []): FormInterface
    {
        return $this->formBuilder->create(CrudType::class, $this->createNewEntity(), array_merge($options, [
            'entity_context' => $this->entityContext,
            'current_action' => CrudAction::CREATE,
        ]));
    }

    public function createFilterForm(array $defaultFilter): FormInterface
    {
        return $this->formBuilder->create(FilterType::class, $defaultFilter, [
            'entity_context' => $this->entityContext,
        ]);
    }

    protected function createNewEntity(): mixed {
        $entity = $this->entityContext->createNewEntity();

        foreach($this->entityContext->getReflection()->getProperties() as $property) {
            if($property->isId()) {
                continue;
            }

            if($value = $this->request->get($property->getName(), null)) {
                if(!is_array($value)) {
                    $value = [$value];
                }

                foreach ($value as $val) {
                    $val = match ($property->getFormType()) {
                        'date' => strtotime($val),
                        'enum' => function ($property, $value) {
                            foreach($property->getEnumType()::cases() as $case) {
                                if($case->name == $value) {
                                    return $case;
                                }
                            }

                            return null;
                        },
                        'collection' => $this->entityManager->getRepository($property->getTargetEntity())->find($val),
                        default => $val
                    };

                    $property->set($entity, $val);
                }
            }
        }

        return $entity;
    }
}