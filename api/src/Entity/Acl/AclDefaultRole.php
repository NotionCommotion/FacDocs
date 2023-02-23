<?php

namespace App\Entity\Acl;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class AclDefaultRole
{
    public function __construct(private string $create='ROLE_TENANT_ADMIN', private string $read='ROLE_TENANT_ADMIN', private string $update='ROLE_TENANT_ADMIN', private string $delete='ROLE_TENANT_ADMIN', private string $manageAcl='ROLE_TENANT_ADMIN')
    {
    }
    public function create():string
    {
        return strtoupper($this->create);
    }
    public function read():string
    {
        return strtoupper($this->read);
    }
    public function update():string
    {
        return strtoupper($this->update);
    }
    public function delete():string
    {
        return strtoupper($this->delete);
    }
    public function manageAcl():string
    {
        return strtoupper($this->manageAcl);
    }
}