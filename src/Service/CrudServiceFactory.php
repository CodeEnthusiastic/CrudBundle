<?php

namespace Coen\CrudBundle\Service;

use Coen\CrudBundle\Decorator\RouterDecorator;
use Coen\CrudBundle\DTO\CrudServiceCollectionDTO;
use Coen\CrudBundle\Generator\ButtonGenerator;
use Coen\CrudBundle\Generator\FilterGenerator\DefaultFilterGenerator;
use Coen\CrudBundle\Generator\FilterGenerator\NoFilterGenerator;
use Coen\CrudBundle\Generator\FilterGeneratorInterface;
use Coen\CrudBundle\Generator\FormGenerator;
use Coen\CrudBundle\Generator\TranslationKeyGenerator;
use Coen\CrudBundle\Helper\EntityContext;
use Coen\CrudBundle\Helper\TwigTemplateSelector;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Router;

class CrudServiceFactory
{

    public function __construct(
        protected readonly SymfonyServiceFacade $symfonyServiceFacade,
        protected readonly EntityContextFactory $entityContextFactory,
        protected readonly Router $router,
        protected readonly FormFactoryInterface $formFactory,
    )
    {}

    public function create(string $entityClass, string $baseRoute): CrudServiceCollectionDTO
    {
        $entityContext = $this->entityContextFactory->create($entityClass, $baseRoute);
        $formGenerator = $this->createFormGenerator($entityContext);
        $templateSelector = $this->createTwigTemplateSelector($entityContext);
        $router = $this->createRouter($entityContext);
        $translationKeyGenerator = $this->createTranslationKeyGenerator($entityContext);

        return new CrudServiceCollectionDTO(
            $entityContext,
            $this->createTwigTemplateSelector($entityContext),
            $this->createFilterGenerator($entityContext, $formGenerator),
            $this->createButtonHandler($entityContext, $translationKeyGenerator, $templateSelector, $router),
            $formGenerator,
            $templateSelector,
            $translationKeyGenerator,
            $router
        );
    }


    protected function createTwigTemplateSelector(EntityContext $entityContext): TwigTemplateSelector
    {
        return new TwigTemplateSelector($entityContext, $this->symfonyServiceFacade->getTwig()->getLoader());
    }

    protected function createTranslationKeyGenerator(EntityContext $entityContext): TranslationKeyGenerator
    {
        return new TranslationKeyGenerator($entityContext);
    }

    protected function createFilterGenerator(EntityContext $entityContext, FormGenerator $formGenerator): FilterGeneratorInterface
    {
        if($entityContext->getReflection()->isFilterable()) {
            return new DefaultFilterGenerator(
                $entityContext,
                $this->symfonyServiceFacade->getRequestStack()->getCurrentRequest(),
                $formGenerator
            );
        }

        return new NoFilterGenerator($entityContext);
    }

    protected function createRouter(EntityContext $entityContext): RouterDecorator
    {
        return new RouterDecorator($entityContext, $this->router);
    }

    protected function createButtonHandler(
        EntityContext $entityContext,
        TranslationKeyGenerator $translationKeyGenerator,
        TwigTemplateSelector $twigTemplateSelector,
        RouterDecorator $routerDecorator
    ): ButtonGenerator
    {
        return new ButtonGenerator(
            $entityContext,
            $translationKeyGenerator,
            $twigTemplateSelector,
            $routerDecorator,
            $this->symfonyServiceFacade->getTwig(),
            $this->symfonyServiceFacade->getAuthorizationChecker()
        );
    }

    protected function createFormGenerator(EntityContext $entityContext): FormGenerator
    {
        return new FormGenerator(
            $entityContext,
            $this->formFactory,
            $this->symfonyServiceFacade->getRequestStack()->getCurrentRequest(),
            $this->symfonyServiceFacade->getEntityManager()
        );
    }
}