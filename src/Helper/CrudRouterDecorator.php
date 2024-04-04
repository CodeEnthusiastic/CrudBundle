<?php

namespace Coen\CrudBundle\Helper;

use Coen\CrudBundle\Controller\ExtendedSymfonyController;
use Coen\CrudBundle\Enum\CrudAction;
use ReflectionClass;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class CrudRouterDecorator implements RouterInterface
{
    private RouterInterface $router;
    private string $baseRoute;

    public function __construct(RouterInterface $router, ExtendedSymfonyController $controller)
    {
        $this->router = $router;
        $this->baseRoute = self::generateBaseRoute($controller);
    }

    public static function generateBaseRoute(ExtendedSymfonyController $class)
    {
        $reflector = new ReflectionClass($class);
        foreach($reflector->getAttributes() as $attribute) {
            if($attribute->getName() == Route::class) {
                return $attribute->getArguments()['name'] ?? '';
            }
        }

        return '';
    }

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
        return $this->baseRoute;
    }

    public function generateForAction(CrudAction $action, array $parameters = [])
    {
        return $this->router->generate($this->getRouteForAction($action), $parameters);
    }

    public function getRouteForAction(CrudAction $action): string
    {
        return $action->toRoute($this->baseRoute);
    }

    public function redirectToAction(CrudAction $action, array $parameters = []): RedirectResponse
    {
        return new RedirectResponse($this->generateForAction($action, $parameters));
    }
}