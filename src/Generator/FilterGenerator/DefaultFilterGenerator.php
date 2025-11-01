<?php

namespace Coen\CrudBundle\Generator\FilterGenerator;

use Coen\CrudBundle\Form\Filter\AbstractFilterType;
use Coen\CrudBundle\Form\Filter\BooleanFilterType;
use Coen\CrudBundle\Form\Filter\CollectionFilterType;
use Coen\CrudBundle\Form\Filter\DefaultFilterType;
use Coen\CrudBundle\Form\Filter\RangeFilterType;
use Coen\CrudBundle\Generator\FilterGeneratorInterface;
use Coen\CrudBundle\Generator\FormGenerator;
use Coen\CrudBundle\Helper\EntityContext;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class DefaultFilterGenerator implements FilterGeneratorInterface {
    private ?FormInterface $form = null;

    public function __construct(
        private readonly EntityContext $entityContext,
        private readonly Request       $request,
        private readonly FormGenerator $formGenerator,
    )
    {}

    protected function createForm(): FormInterface
    {
        if(null === $this->form) {
            $form = $this->formGenerator->createFilterForm([]);

            $form->handleRequest($this->request);
            $this->form = $form;
        }

        return $this->form;
    }

    public function getFilterForm(): ?FormInterface
    {
        return $this->createForm();
    }

    public function getFilteredEntities(): Collection
    {
        $qb = $this->entityContext->getQueryBuilder();

        $form = $this->createForm();
        if($form->isSubmitted() && $form->isValid()) {
            //TODO Find solution for defaultFilter versus qb configuration
            $qb->resetDQLPart('orderBy');

            foreach($form->all() as $form) {
                $propertyName = $form->getName();
                $filterType = $form->getConfig()->getType()->getInnerType();

                if($filterType instanceof AbstractFilterType) {
                    $filterType->appendToQueryBuilder(
                        $qb,
                        $this->entityContext->getReflection()->getPropertyByName($propertyName),
                        $form->getData()
                    );
                }
            }
        }

        return new ArrayCollection($qb->getQuery()->getResult());
    }
}