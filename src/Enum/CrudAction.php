<?php

namespace Coen\CrudBundle\Enum;
use Coen\CrudBundle\Service\TwigTemplateSelector;

enum CrudAction: string
{
    case LIST = 'list';
    case CREATE = 'create';
    case UPDATE = 'update';
    case READ = 'read';
    case DELETE = 'delete';

    case MULTI_CREATE = 'multi_create';

    public static function toTwigArray(): array
    {
        return array_combine(array_map(fn($case) => $case->name, CrudAction::cases()), CrudAction::cases());
    }

    public function toTemplate(): ?string
    {
        return $this->value . TwigTemplateSelector::templateSuffix;
    }

    public function toRoute(string $baseRoute = ''): string
    {
        return $baseRoute . '_' . $this->value;
    }

    public function needEntity(): bool
    {
        return match ($this) {
            CrudAction::LIST, CrudAction::CREATE, CrudAction::MULTI_CREATE => false,
            CrudAction::READ, CrudAction::UPDATE, CrudAction::DELETE => true
        };
    }

    public function getIcon()
    {
        return match ($this) {
            CrudAction::LIST => 'bi-book',
            CrudAction::CREATE, CrudAction::MULTI_CREATE => 'bi-plus-square',
            CrudAction::READ => 'bi-book',
            CrudAction::UPDATE => 'bi-pencil-square',
            CrudAction::DELETE => 'bi-trash'
        };
    }

    public function getBtnType()
    {
        return match ($this) {
            CrudAction::LIST => 'btn-secondary',
            CrudAction::CREATE, CrudAction::MULTI_CREATE => 'btn-success',
            CrudAction::READ => 'btn-info',
            CrudAction::UPDATE => 'btn-primary',
            CrudAction::DELETE => 'btn-danger'
        };
    }
}