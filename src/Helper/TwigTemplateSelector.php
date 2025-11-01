<?php

namespace Coen\CrudBundle\Helper;

use Coen\CrudBundle\Enum\CrudAction;
use Twig\Loader\LoaderInterface;

class TwigTemplateSelector
{
    private const templateSuffix = '.html.twig';
    private const D_S = DIRECTORY_SEPARATOR;

    protected string $baseTemplateDir = 'crud' . self::D_S;
    protected string $defaultTemplateDir = '@Crud' . self::D_S .  'bootstrap' . self::D_S;
    protected string $defaultBaseTemplatePath = '@Crud' . self::D_S . 'base' . self::templateSuffix;

    public function __construct(
        private readonly EntityContext   $entityContext,
        private readonly LoaderInterface $twigLoader,
    )
    {}

    private function getEntityTemplateDir(): string
    {
        return $this->entityContext->getReflection()->getIdentifier() . self::D_S;
    }

    private function getBaseEntityTemplate(): string
    {
        return $this->entityContext->getReflection()->getIdentifier() . self::templateSuffix;
    }

    public function getButtonTemplate(): string
    {
        $template = $this->baseTemplateDir . $this->getEntityTemplateDir() . 'buttons.html.twig';

        if(!$this->twigLoader->exists($template)) {
            return  $this->defaultTemplateDir . 'buttons.html.twig';
        }

        return $template;
    }

    public function getBaseTemplate(): string
    {
        $template = $this->baseTemplateDir . $this->getBaseEntityTemplate();

        if(!$this->twigLoader->exists($template)) {
            $template = $this->defaultBaseTemplatePath;
        }

        return $template;
    }

    public function getAppTemplate(): string
    {
        return $this->entityContext->getAppTemplate();
    }

    public function getActionTemplate(CrudAction $action): string
    {
        return $this->getTemplate($action->value . self::templateSuffix);
    }

    public function getTemplate(string $fileName): string
    {
        $template = $this->baseTemplateDir . $this->getEntityTemplateDir() . $fileName;

        if(!$this->twigLoader->exists($template)) {
            return  $this->defaultTemplateDir . $fileName;
        }

        return $template;
    }
}