<?php

namespace Coen\CrudBundle\Decorator;

use Coen\CrudBundle\Helper\EntityContext;
use Coen\CrudBundle\Controller\ExtendedSymfonyController;
use Coen\CrudBundle\Enum\CrudAction;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RouterDecorator implements RouterInterface
{
    public function __construct(
        private readonly EntityContext   $entityContext,
        private readonly RouterInterface $router,
    )
    {}

    public function setContext(RequestContext $context): void
    {
        $this->router->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->router->getContext();
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->router->getRouteCollection();
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        return $this->router->generate($name, $parameters, $referenceType);
    }

    public function match(string $pathinfo): array
    {
        return $this->match($pathinfo);
    }

    public function getBaseRoute(): string
    {
        return $this->entityContext->getBaseRoute();
    }

    public function generateForAction(CrudAction $action, array $parameters = []): string
    {
        return $this->router->generate($this->getRouteForAction($action), $parameters);
    }

    public function generateRoute(string $name, array $parameters = []): string
    {
        return $this->router->generate($name, $parameters);
    }

    public function redirectToRoute(string $name, array $parameters = []): RedirectResponse
    {
        return new RedirectResponse($this->generateRoute($name, $parameters));
    }

    public function getRouteForAction(CrudAction $action): string
    {
        return $action->toRoute($this->getBaseRoute());
    }

    public function redirectToAction(CrudAction $action, array $parameters = []): RedirectResponse
    {
        return new RedirectResponse($this->generateForAction($action, $parameters));
    }
}