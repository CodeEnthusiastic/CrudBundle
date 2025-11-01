<?php

namespace Coen\CrudBundle\Generator\FilterGenerator;

use Coen\CrudBundle\Generator\FilterGeneratorInterface;
use Coen\CrudBundle\Helper\EntityContext;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\FormInterface;

class NoFilterGenerator implements FilterGeneratorInterface {
    public function __construct(
        private readonly EntityContext $entityContext,
    )
    {}

    public function getFilterForm(): ?FormInterface
    {
        return null;
    }

    public function getFilteredEntities(): Collection
    {
        return new ArrayCollection($this->entityContext->getQueryBuilder()->getQuery()->getResult());
    }
}