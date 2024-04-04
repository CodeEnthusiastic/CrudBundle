<?php

namespace Coen\CrudBundle\Service;

use Coen\CrudBundle\Enum\CrudAction;
use Coen\CrudBundle\Reflection\ReflectionEntity;
use Twig\Environment as Twig;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Loader\LoaderInterface;

class TwigTemplateSelector
{
    public const templateSuffix = '.html.twig';
    private const D_S = DIRECTORY_SEPARATOR;
    private LoaderInterface $twigLoader;
    protected ?string $appBaseTemplate = null;
    protected string $crudBaseTemplate = 'base' . self::templateSuffix;
    protected string $baseEntityTemplate;

    protected string $baseTemplateDir = 'crud' . self::D_S;
    protected string $defaultTemplateDestination = '@Crud' . self::D_S .  'default' . self::D_S;
    protected string $entityTemplateDir;

    public function __construct(
        #[Autowire(service: 'twig')] Twig $twig
    )
    {
        $this->twigLoader = $twig->getLoader();
    }

    public function setAppBaseTemplate(string $baseAppTemplate)
    {
        $this->appBaseTemplate = $baseAppTemplate;
    }

    public function getAppBaseTemplate(): ?string
    {
        return $this->appBaseTemplate;
    }

    public function setEntityReflection(ReflectionEntity $entityReflection)
    {
        $this->entityTemplateDir = $entityReflection->getIdentifier() . self::D_S;
        $this->baseEntityTemplate = $entityReflection->getIdentifier() . self::templateSuffix;
    }

    public function getActionTemplate(CrudAction $action): string
    {
        $template = $this->baseTemplateDir . $this->entityTemplateDir . $action->toTemplate();

        if(!$this->twigLoader->exists($template)) {
            return  $this->defaultTemplateDestination . $action->toTemplate();
        }

        return $template;
    }

    public function getButtonTemplate()
    {
        $template = $this->baseTemplateDir . $this->entityTemplateDir . 'buttons.html.twig';

        if(!$this->twigLoader->exists($template)) {
            return  $this->defaultTemplateDestination . 'buttons.html.twig';
        }

        return $template;
    }

    public function getCrudBaseTemplate(): string
    {
        $template = $this->baseTemplateDir . $this->baseEntityTemplate;

        if(!$this->twigLoader->exists($template)) {
            $template = $this->baseTemplateDir . $this->crudBaseTemplate;
        }

        return $template;
    }
}