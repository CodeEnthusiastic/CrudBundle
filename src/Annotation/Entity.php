<?php

declare(strict_types=1);

namespace Coen\CrudBundle\Annotation;
use Coen\CrudBundle\Enum\CrudAction;
use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target('CLASS')
 * @template T of object
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Entity
{
    private bool $listable;
    private bool $creatable;
    private bool $readable;
    private bool $updatable;
    private bool $deletable;
    private bool $multiCreatable;

    private ?string $accessRoleList= null;
    private ?string $accessRoleCreate= null;
    private ?string $accessRoleRead= null;
    private ?string $accessRoleUpdate= null;
    private ?string $accessRoleDelete= null;

    public function __construct(
        ?bool $listable = true,
        ?bool $creatable = true,
        ?bool $readable = true,
        ?bool $updatable = true,
        ?bool $deletable = true,
        ?bool $multiCreatable = false,

        /*
        @TODO Implement Role Checks
        ?string $accessRoleList = null,
        ?string $accessRoleCreate = null,
        ?string $accessRoleRead = null,
        ?string $accessRoleUpdate = null,
        ?string $accessRoleDelete = null
        */
    ) {
        $this->listable  = $listable;
        $this->creatable = $creatable;
        $this->readable  = $readable;
        $this->updatable  = $updatable;
        $this->deletable  = $deletable;
        $this->multiCreatable = $multiCreatable;

        /*
        $this->accessRoleList = $accessRoleList;
        $this->accessRoleCreate = $accessRoleCreate;
        $this->accessRoleRead = $accessRoleRead;
        $this->accessRoleUpdate = $accessRoleUpdate;
        $this->accessRoleDelete = $accessRoleDelete;
        */
    }

    public function hasAction(CrudAction $action): bool
    {
        return match ($action) {
            CrudAction::LIST => $this->listable,
            CrudAction::CREATE => $this->creatable,
            CrudAction::MULTI_CREATE => $this->multiCreatable,
            CrudAction::READ => $this->readable,
            CrudAction::UPDATE => $this->updatable,
            CrudAction::DELETE => $this->deletable
        };
    }

    public function getAccessRole(CrudAction $action): ?string
    {
        return match ($action) {
            CrudAction::LIST => $this->accessRoleList,
            CrudAction::CREATE,
            CrudAction::MULTI_CREATE => $this->accessRoleCreate,
            CrudAction::READ => $this->accessRoleRead,
            CrudAction::UPDATE => $this->accessRoleUpdate,
            CrudAction::DELETE => $this->accessRoleDelete
        };
    }
}
