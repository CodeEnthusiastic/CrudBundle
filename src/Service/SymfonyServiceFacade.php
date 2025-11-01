<?php

namespace Coen\CrudBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as Twig;

class SymfonyServiceFacade
{
    public function __construct(
        #[Autowire(service: 'security.authorization_checker')] private readonly AuthorizationChecker   $authorizationChecker,
        #[Autowire(service: 'security.token_storage')] private readonly TokenStorageInterface          $tokenStorage,
        #[Autowire(service: 'twig')] private readonly Twig                                             $twig,
        #[Autowire(service: 'request_stack')] private readonly RequestStack                            $requestStack,
        #[Autowire(service: 'security.csrf.token_manager')] private readonly CsrfTokenManagerInterface $tokenManager,
        private readonly EntityManagerInterface                                                        $entityManager,
        #[Autowire(service: 'translator')] private readonly TranslatorInterface                        $translator,
    ) { }

    public function getAuthorizationChecker(): AuthorizationChecker
    {
        return $this->authorizationChecker;
    }

    public function getTokenStorage(): TokenStorageInterface
    {
        return $this->tokenStorage;
    }

    public function getTwig(): Twig
    {
        return $this->twig;
    }

    public function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }

    public function getTokenManager(): CsrfTokenManagerInterface
    {
        return $this->tokenManager;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }
}