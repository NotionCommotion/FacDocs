<?php

namespace App\Entity\Acl;

use App\Exception\NonMutablePropertyException;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

trait HasResourceAclTrait
{        
    /*
    $resourceAcl is defined in concrete class and doctrine associations are considered too suspect to use.
    Currently, not visable via the entity and only via the subrequest /resource/123/acl/321.  If made visable, not sure if security is necessary since handled by AccessControlAttributeNormalizer (similar to TenantAccessControlContextBuilder)
    #[Groups(['acl_admin:read'])]#[ApiProperty(security: "is_granted('MANAGE_ACL')",securityPostDenormalize: "is_granted('MANAGE_ACL', object)")]
    */

    public function getResourceAcl(): ?ResourceAclInterface
    {
        return $this->resourceAcl;
    }

    // Only required to allow ACL and resource share the same ID.  Consider removing.
    public function setId(Ulid $id): self
    {
        if($this->id && $id !== $this->id) {
            throw new NonMutablePropertyException('Id may not be changed');
        }
        $this->id = $id;
        return $this;
    }

    public function setResourceAcl(ResourceAclInterface $resourceAcl): self
    {
        if($this->resourceAcl && ($resourceAcl !== $this->id || $resourceAcl !== $this->resourceAcl)) {
            throw new NonMutablePropertyException('ACL may not be changed');
        }
        $this->resourceAcl = $resourceAcl;

        return $this;
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['id'=>$this->id, 'id-rfc4122'=>$this->id?$this->id->toRfc4122():'NULL', 'class'=>get_class($this), 'name'=>method_exists($this, 'getName')?$this->getName():'N/A', 'resourceAcl'=>$this->resourceAcl?$this->resourceAcl->debug($follow, $verbose, $hide):null];
    }
}