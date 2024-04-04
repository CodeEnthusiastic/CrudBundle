<?php

namespace Coen\CrudBundle\Helper;

use Coen\CrudBundle\Enum\CrudAction;
use Coen\CrudBundle\Form\FilterType;
use Coen\CrudBundle\Reflection\ReflectionEntity;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class FilterManager {
    private ReflectionEntity $entityReflection;
    private FormFactoryInterface $formFactory;
    private ObjectRepository $entityRepository;
    private array $formCustomisation;

    public function __construct(
        ReflectionEntity $entityReflection,
        ObjectRepository $entityRepository,
        FormFactoryInterface $formFactory,
        array $formCustomisation,
    )
    {
        $this->entityReflection = $entityReflection;
        $this->entityRepository = $entityRepository;
        $this->formFactory = $formFactory;

        $this->formCustomisation = $formCustomisation;
    }

    public function handleRequest(Request $request, array $defaultFilter = []): FormInterface
    {
        $form = $this->createFilterForm($defaultFilter);
        $form->handleRequest($request);

        return $form;
    }

    protected function createFilterForm(array $data): FormInterface
    {
        return $this->formFactory->create(FilterType::class, $data, [
            'crud_action' => CrudAction::LIST,
            'entity_reflection' => $this->entityReflection,
            'entity_repository' => $this->entityRepository,
            'form_customisation' => $this->formCustomisation
        ]);
    }
}