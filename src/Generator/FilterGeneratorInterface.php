<?php

namespace Coen\CrudBundle\Generator;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\FormInterface;

interface FilterGeneratorInterface
{
    public function getFilterForm(): ?FormInterface;

    public function getFilteredEntities(): Collection;
}