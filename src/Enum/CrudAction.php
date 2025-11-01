<?php

namespace Coen\CrudBundle\Enum;



use Coen\CrudBundle\Helper\TwigTemplateSelector;

enum CrudAction: string
{
    case LIST = 'list';
    case CREATE = 'create';
    case UPDATE = 'update';
    case READ = 'read';
    case DELETE = 'delete';

    public static function toTwigArray(): array
    {
        return array_combine(array_map(fn($case) => $case->name, CrudAction::cases()), CrudAction::cases());
    }

    public function toRoute(string $baseRoute = ''): string
    {
        return $baseRoute . '_' . $this->value;
    }

    public function needEntity(): bool
    {
        return match ($this) {
            CrudAction::LIST, CrudAction::CREATE => false,
            CrudAction::READ, CrudAction::UPDATE, CrudAction::DELETE => true
        };
    }
}