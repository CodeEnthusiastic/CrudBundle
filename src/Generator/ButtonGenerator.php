<?php

namespace Coen\CrudBundle\Generator;

use Coen\CrudBundle\Decorator\RouterDecorator;
use Coen\CrudBundle\Helper\EntityContext;
use Coen\CrudBundle\Helper\TwigTemplateSelector;
use Coen\CrudBundle\Enum\CrudAction;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Twig\Environment;

class ButtonGenerator
{
    private CrudAction $currentAction;

    public function __construct(
        private readonly EntityContext           $entityContext,
        private readonly TranslationKeyGenerator $translationKeyGenerator,
        private readonly TwigTemplateSelector    $twigTemplateSelector,
        private readonly RouterDecorator         $router,
        private readonly Environment             $twig,
        private readonly AuthorizationChecker    $authorizationChecker
    ) {}

    public function render($currentAction, ?object $entity = null, bool $whiteLabels = true, $removedButtons = []): string
    {
        // TODO Remove currentAction when all is Updated.

        $buttons = [];
        $useForm = false;
        foreach(CrudAction::cases() as $action) {
            $isRemovedAction = in_array($action, $removedButtons);
            $needsEntityButHasNot = $action->needEntity() && null === $entity;
            if(
                !$this->entityContext->getReflection()->hasAction($action) ||
                $this->hasAccessToAction($action) ||
                (isset($buttons[CrudAction::UPDATE->name]) && $action === CrudAction::READ) ||
                $isRemovedAction ||
                $needsEntityButHasNot ||
                $this->currentAction === $action
            ) {
                continue;
            }

            if($action === CrudAction::DELETE) {
                $useForm = true;
            }

            $buttons[$action->name] = [
                'action' => $action,
                'needEntity' => $action->needEntity(),
                'icon' => match ($action) {
                    CrudAction::LIST, CrudAction::READ => 'fa-solid fa-list',
                    CrudAction::CREATE => 'fa-solid fa-plus',
                    CrudAction::UPDATE => 'fa-solid fa-pen',
                    CrudAction::DELETE => 'fa-solid fa-trash'
                },
                'buttonType' => match ($action) {
                    CrudAction::LIST => 'btn-secondary',
                    CrudAction::CREATE => 'btn-success',
                    CrudAction::READ => 'btn-info',
                    CrudAction::UPDATE => 'btn-primary',
                    CrudAction::DELETE => 'btn-danger'
                },
                'htmlTag' => match ($action) {
                    CrudAction::LIST,
                    CrudAction::CREATE,
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

                'currentAction' => $this->currentAction,
                'entity' => $entity,
                'withLabel' => $whiteLabels,
                'useForm' => $useForm,
                'buttons' => $buttons
            ]
        );
    }

    private function hasAccessToAction(CrudAction $action): bool
    {
        $accessRole = $this->entityContext->getReflection()->getAccessRole($action);
        return $accessRole !== null && !$this->authorizationChecker->isGranted($this->entityContext->getReflection()->getAccessRole($action));
    }

    public function setCurrentAction(CrudAction $currentAction): void
    {
        $this->currentAction = $currentAction;
    }
}