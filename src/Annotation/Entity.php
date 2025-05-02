<?php

declare(strict_types=1);

namespace Coen\CrudBundle\Annotation;

use Coen\CrudBundle\Enum\CrudAction;
use Attribute;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target('CLASS')
 * @template T of object
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Entity
{
    public function __construct(
        private readonly ?bool $listable = true,
        private readonly ?bool $creatable = true,
        private readonly ?bool $readable = true,
        private readonly ?bool $updatable = true,
        private readonly ?bool $deletable = true,
        private readonly ?bool $multiCreatable = false,
    ) {}

    public function hasAction(CrudAction $action): bool
    {
        return match ($action) {
            CrudAction::LIST => $this->listable,
            CrudAction::CREATE => $this->creatable,
            CrudAction::READ => $this->readable,
            CrudAction::UPDATE => $this->updatable,
            CrudAction::DELETE => $this->deletable
        };
    }

    public function getAccessRole(CrudAction $action): ?string
    {
        return null;
    }
}
