<?php

namespace Coen\CrudBundle\DTO;

use Coen\CrudBundle\Decorator\RouterDecorator;
use Coen\CrudBundle\Generator\ButtonGenerator;
use Coen\CrudBundle\Generator\FilterGeneratorInterface;
use Coen\CrudBundle\Generator\FormGenerator;
use Coen\CrudBundle\Generator\TranslationKeyGenerator;
use Coen\CrudBundle\Helper\EntityContext;
use Coen\CrudBundle\Helper\TwigTemplateSelector;

class CrudServiceCollectionDTO
{
    public function __construct(
        private readonly EntityContext            $entityContext,
        private readonly TwigTemplateSelector     $twigTemplateSelector,
        private readonly FilterGeneratorInterface $filterGenerator,
        private readonly ButtonGenerator          $buttonGenerator,
        private readonly FormGenerator            $formGenerator,
        private readonly TwigTemplateSelector     $templateSelector,
        private readonly TranslationKeyGenerator  $translationKeyGenerator,
        private readonly RouterDecorator          $router)
    { }

    public function getEntityContext(): EntityContext
    {
        return $this->entityContext;
    }

    public function getTwigTemplateSelector(): TwigTemplateSelector
    {
        return $this->twigTemplateSelector;
    }

    public function getFilterGenerator(): FilterGeneratorInterface
    {
        return $this->filterGenerator;
    }

    public function getButtonGenerator(): ButtonGenerator
    {
        return $this->buttonGenerator;
    }

    public function getFormGenerator(): FormGenerator
    {
        return $this->formGenerator;
    }

    public function getTemplateSelector(): TwigTemplateSelector
    {
        return $this->templateSelector;
    }

    public function getTranslationKeyGenerator(): TranslationKeyGenerator
    {
        return $this->translationKeyGenerator;
    }

    public function getRouter(): RouterDecorator
    {
        return $this->router;
    }
}