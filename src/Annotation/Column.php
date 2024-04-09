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
    private bool $listable;
    private bool $creatable;
    private bool $readable;
    private bool $updatable;
    private bool $required;
    private bool $disabled;
    private ?string $getter;
    private ?string $setter;
    private ?string $filterType;

    public function __construct(
        bool $listable = true,
        bool $creatable = true,
        bool $readable = true,
        bool $updatable = true,
        bool $required = false,
        bool $disabled = false,
        string $getter = null,
        string $setter = null,
        string $filterType = null
    ) {
        $this->listable  = $listable;
        $this->creatable = $creatable;
        $this->readable  = $readable;
        $this->updatable = $updatable;
        $this->required = $required;
        $this->disabled = $disabled;
        $this->getter  = $getter;
        $this->setter  = $setter;
        $this->filterType = $filterType;
    }

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
