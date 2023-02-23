VOTER                SUPPORTS                ACTION
ResourceAclVoter    HasResourceAclInterface    ResourceAclService::canPerformCrud()
DocumentAclVoter    DocumentInterface        DocumentAclService::canPerformCrud()
AclVoter            AclInterface            ResourceAclService::canManageAcl()
AclMemberVoter        AclMemberInterface        ResourceAclService::canManageAcl()

FOLLOWING MIGHT NOT LONGER BE VALID:
ResourceMemberDocumentAclVoter just for CRUD on documents.
ResourceMemberRuleVoter allows user to view containers (i.e. projects) which they are allowed to view documents in.
ResourceMemberRuleVoter extends normal Symfony ROLE's but also allows if they belong to a particular group (future - not implemented).
