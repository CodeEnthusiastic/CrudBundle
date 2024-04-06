<?php

namespace Coen\CrudBundle\Helper;

use Coen\CrudBundle\Enum\CrudAction;
use Coen\CrudBundle\Reflection\ReflectionEntity;
use Coen\CrudBundle\Service\TwigTemplateSelector;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Twig\Environment;

class ButtonHandler
{
    private ReflectionEntity $entityReflection;
    private TranslationKeyFactory $translationKeyFactory;
    private TwigTemplateSelector $twigTemplateSelector;
    private RouterInterface $router;
    private Environment $twig;
    private AuthorizationChecker $authorizationChecker;

    public function __construct(
        ReflectionEntity $entityReflection,
        TranslationKeyFactory $translationKeyFactory,
        TwigTemplateSelector $twigTemplateSelector,
        RouterInterface $router,
        Environment $twig,
        AuthorizationChecker $authorizationChecker
    ) {
        $this->entityReflection = $entityReflection;
        $this->translationKeyFactory = $translationKeyFactory;
        $this->twigTemplateSelector = $twigTemplateSelector
        ;
        $this->router = $router;
        $this->twig = $twig;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function renderHeaderButtons(CrudAction $currentAction, object $entity = null, bool $whiteLabels = true) {
        $removedButtons = [
            CrudAction::READ,
            CrudAction::UPDATE,
        ];

        if($currentAction === CrudAction::LIST) {
            $removedButtons[] = CrudAction::LIST;
        }

        return $this->render($currentAction, $entity, $whiteLabels, $removedButtons);
    }

    public function renderRowButtons(object $entity) {
        return $this->render(CrudAction::LIST, $entity, false, [
            CrudAction::CREATE,
            CrudAction::MULTI_CREATE
        ]);
    }

    public function render(CrudAction $currentAction, object $entity = null, bool $whiteLabels = true, $removedButtons = []): string
    {
        $buttons = [];
        $useForm = false;

        foreach(CrudAction::cases() as $action) {
            $needEntity = match ($action) {
                CrudAction::LIST,
                CrudAction::CREATE,
                CrudAction::MULTI_CREATE => false,
                CrudAction::DELETE,
                CrudAction::UPDATE,
                CrudAction::READ => true
            };

            $accessRole = $this->entityReflection->getAccessRole($action);

            if(
                !$this->entityReflection->hasAction($action) ||
                $accessRole !== null && !$this->authorizationChecker->isGranted($this->entityReflection->getAccessRole($action)) ||
                (isset($buttons[CrudAction::UPDATE->name]) && $action === CrudAction::READ) ||
                in_array($action, $removedButtons) ||
                $needEntity && null === $entity ||
                $currentAction === $action ||
                $action === CrudAction::MULTI_CREATE
            ) {
                continue;
            }

            if($action === CrudAction::DELETE) {
                $useForm = true;
            }

            $buttons[$action->name] = [
                'action' => $action,
                'needEntity' => $needEntity,
                'icon' => match ($action) {
                    CrudAction::LIST, CrudAction::READ => 'fa-solid fa-list',
                    CrudAction::CREATE => 'fa-solid fa-plus',
                    CrudAction::UPDATE => 'fa-solid fa-pen',
                    CrudAction::DELETE => 'fa-solid fa-trash'
                },
                'buttonType' => match ($action) {
                    CrudAction::LIST => 'btn-secondary',
                    CrudAction::CREATE, CrudAction::MULTI_CREATE => 'btn-success',
                    CrudAction::READ => 'btn-info',
                    CrudAction::UPDATE => 'btn-primary',
                    CrudAction::DELETE => 'btn-danger'
                },
                'htmlTag' => match ($action) {
                    CrudAction::LIST,
                    CrudAction::CREATE,
                    CrudAction::MULTI_CREATE,
                    CrudAction::READ,
                    CrudAction::UPDATE => 'a',
                    CrudAction::DELETE => 'button'
                }
            ];
        }

        return $this->twig->render(
            $this->twigTemplateSelector->getButtonTemplate(),
            [
                'crudAction' => CrudAction::toTwigArray(),
                'crudRouter' => $this->router,

                'currentAction' => $currentAction,
                'entity' => $entity,
                'withLabel' => $whiteLabels,
                'useForm' => $useForm,
                'buttons' => $buttons
            ]
        );
    }
}