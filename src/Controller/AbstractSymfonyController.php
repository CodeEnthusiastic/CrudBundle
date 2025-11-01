<?php

namespace Coen\CrudBundle\Controller;

use Coen\CrudBundle\Service\SymfonyServiceFacade;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

#[AsController]
abstract class AbstractSymfonyController
{
    protected EntityManagerInterface $entityManager;

    public function __construct(
        protected readonly SymfonyServiceFacade $symfonyServiceFacade,
    )
    {
        $this->entityManager = $this->symfonyServiceFacade->getEntityManager();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->symfonyServiceFacade->getEntityManager();
    }

    protected function isGranted(mixed $attribute, mixed $subject = null): bool
    {
        return $this->symfonyServiceFacade->getAuthorizationChecker()->isGranted($attribute, $subject);
    }

    protected function getUser(): ?UserInterface
    {
        if (null === $token = $this->symfonyServiceFacade->getTokenStorage()->getToken()) {
            return null;
        }

        return $token->getUser();
    }

    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        return $this->doRender($view, null, $parameters, $response, __FUNCTION__);
    }

    private function doRenderView(string $view, ?string $block, array $parameters, string $method): string
    {
        foreach ($parameters as $k => $v) {
            if ($v instanceof FormInterface) {
                $parameters[$k] = $v->createView();
            }
        }

        if (null !== $block) {
            return $this->symfonyServiceFacade->getTwig()->load($view)->renderBlock($block, $parameters);
        }

        return $this->symfonyServiceFacade->getTwig()->render($view, $parameters);
    }

    private function doRender(string $view, ?string $block, array $parameters, ?Response $response, string $method): Response
    {
        $content = $this->doRenderView($view, $block, $parameters, $method);
        $response ??= new Response();

        if (200 === $response->getStatusCode()) {
            foreach ($parameters as $v) {
                if ($v instanceof FormInterface && $v->isSubmitted() && !$v->isValid()) {
                    $response->setStatusCode(422);
                    break;
                }
            }
        }

        $response->setContent($content);

        return $response;
    }

    protected function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->symfonyServiceFacade->getTranslator()->trans($id, $parameters, $domain, $locale);
    }

    protected function isCsrfTokenValid(string $id, #[\SensitiveParameter] ?string $token): bool
    {
        return $this->symfonyServiceFacade->getTokenManager()->isTokenValid(new CsrfToken($id, $token));
    }
    protected function addFlash(string $type, mixed $message): void
    {
        try {
            $session = $this->symfonyServiceFacade->getRequestStack()->getSession();
        } catch (SessionNotFoundException $e) {
            throw new \LogicException('You cannot use the addFlash method if sessions are disabled. Enable them in "config/packages/framework.yaml".', 0, $e);
        }

        $session->getFlashBag()->add($type, $message);
    }
}