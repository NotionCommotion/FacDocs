<?php

/*
 * This file is part of the FacDocs project.
 *
 * (c) Michael Reed villascape@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Model\Config;

use App\Entity\Acl\AclPermissionSet;
use App\Entity\User\TenantUserInterface;

class TenantConfig extends AbstractConfig implements ConfigInterface
{
    public function __construct(TenantUserInterface $tenantUser)
    {
        parent::__construct($tenantUser);
    }

    public function getId()
    {
        return $this->getUser()->getTenant()->getId();
    }

    public function getResourceAclPermissionSetPrototype(): AclPermissionSet
    {
        return $this->getUser()->getTenant()->getResourceAclPermissionSetPrototype();
    }

    public function getDocumentAclPermissionSetPrototype(): AclPermissionSet
    {
        return $this->getUser()->getTenant()->getDocumentAclPermissionSetPrototype();
    }
}
