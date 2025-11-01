<?php

declare(strict_types=1);

namespace Coen\CrudBundle\Annotation;

use Coen\CrudBundle\Enum\CrudAction;
use Attribute;

/**
 * @Annotation
 * @Target({'PROPERTY','ANNOTATION'})
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Column
{
    public function __construct(
        private readonly bool $listable = true,
        private readonly bool $creatable = true,
        private readonly bool $readable = true,
        private readonly bool $updatable = true,
        private readonly bool $required = false,
        private readonly bool $disabled = false,
        private readonly ?string $getter = null,
        private readonly ?string $setter = null,
        private readonly ?string $filterType = null
    ) { }

    public function isUsableForAction(CrudAction $action): bool
    {
        return match ($action) {
            CrudAction::LIST => $this->listable,
            CrudAction::CREATE => $this->creatable,
            CrudAction::READ => $this->readable,
            CrudAction::UPDATE,
            CrudAction::DELETE => $this->updatable,
        };
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function hasGetter(): bool
    {
        return null !== $this->getter;
    }

    public function getGetter(): string
    {
        return $this->getter;
    }

    public function hasSetter(): bool
    {
        return null !== $this->setter;
    }

    public function getSetter(): string
    {
        return $this->setter;
    }

    public function setListable(bool $listable)
    {
        $this->listable = $listable;
    }

    public function getFilterType(): ?string
    {
        return $this->filterType;
    }
}
