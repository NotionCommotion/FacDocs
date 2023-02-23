 only pertains to document entities.  Maybe in the future make it generic.



Updating default permission values.
    ContainerUpdateProcessor is used by PUT operations on Project, Asset, and DocumentGroup to apply previous values should the request have undefined values.
    ContainerSubscriber listens for ContainerInterface PrePersist and PreUpdate, and then updates any empty values using the tenant's default values.

MemberSubscriber listens for MemberInterface PrePersist and PreUpdate, and then updates any empty values using the container's default values.

AclPermission unused Processors and Providers since AclPermission currently is not an ApiResource 
    PermissionProcessor
    DefaultPermissionProcessor
    DefaultResourceMemberProvider
    ResourceMemberProvider


DoctrineTypes
    AclPermissionType
        Convers wrx to an integer.
    
Extensions
    ContainerExtension
    FolderMemberAclExtension

Voters
    ContainerVoter
    ResourceMemberDocumentAclVoter
    
Repositories
    MediaRepository
    UserRepository

Entities
    Project
    Asset
    Document
    Tenant
    Vendor
    DocumentGroup
    TenantUser
    OrganizationInterface
    AclPermission
    AclPermissionEnum
    AclPermission
    AclPermissionSet
    Trait
    AbstractMember
    ContainerInterface

Models
    TenantConfig
    
Other
    TenantCreatorService
    TestHelperService
    
    
ContainerInterface (Project, Asset, DocumentGroup) each contain a single Interface and since 1-to-1, exposed.
TBD whether should get rid of 1-to-1 and include in main table.
Since 1-to-1, don't give Interface an identifier and just use its ContainerInterface identifier.
Unlike other user provided entitites, Interface is not an TenantEntity (i.e. doesn't contain the Tenant).

Document collections are located in ContainerInterface, and not Interface (is this best?)
    Many Documents have One Project
    Many Documents have Many DocumentGroups
    Many Documents have Many Assets

AbstractMember are users who have been added to the container within Interface and are identified by the composite user and Container.

Persisters
    ContainerResourceDataPersister acts on ContainerInterface and creats its 1-to-1 Interface
    by calling ContainerInterface::create() and using the default permissions and associates it with the object.
    
    MemberDataPersister acts on MemberInterface and sets the permissions and specifications for all.
    Also sets default asset for Project (bit of a kludge).

    Instead of adding an listener or decorated persister to Document, just override setCreateBy() to also set the owner.
    Member


### INTERFACES:START ###
interface AccessControlAwareInterface
    interface AclEntityInterface extends AccessControlAwareInterface
        interface AclInterface extends AclEntityInterface
            interface DocumentAclInterface extends AclInterface
            interface ResourceAclInterface extends AclInterface
        interface AclMemberInterface extends AclEntityInterface
    interface AclUserInterface extends AccessControlAwareInterface
    interface HasRolesInterface extends AccessControlAwareInterface
    interface HasAclInterface extends AccessControlAwareInterface
    interface ManagedByAclInterface extends AccessControlAwareInterface
        interface HasResourceAclInterface extends ManagedByAclInterface
            interface HasContainerAclInterface extends HasResourceAclInterface
            interface HasDocumentAclInterface extends HasResourceAclInterface


Interface                   Keep?   Description
HasResourceAclInterface     Yes     Any entity (i.e. Asset, TenantUser) which uses an ACL to manage itself.
HasDocumentAclInterface     Yes     Any entity  (i.e. Project and later Asset and DocumentGroup) which uses an ACL to manage documents it contains.
HasAclInterface             Yes?    All HasResourceAclInterface and since HasDocumentAclInterface extends HasAclInterface, all HasDocumentAclInterface as well.
HasContainerAclInterface    Yes     Not yet implemented.  Used to check for child/parent ACLs such as VendorUser uses Vendor ACL.
AclEntityInterface          Yes     Either a AclInterface or a AclMemberInterface
ResourceAclInterface        Yes
DocumentAclInterface        Yes
AclMemberInterface          Yes
AccessControlAwareInterface Yes?    Anything related to AccessControl.  Doesn't really do anything.
AclUserInterface            No      All users.  What is the point?
HasRolesInterface           No      All users.  What is the point?
ManagedByAclInterface       No?     Anything that can be managed

Questions:
    AccessControlAwareInterface, ManagedByAclInterface, AccessControlAwareInterface
### INTERFACES:END ###
AclDefaultRole

AclPermissionEnum
AclPermission
AclPermissionSet

Role

HasDocumentAclInterface
HasResourceAclInterface

HasDocumentAclTrait
HasResourceAclTrait

AclInterface
    AbstractDocumentAcl extends AbstractAcl
    AbstractResourceAcl extends AbstractAcl

AclMemberInterface
    DocumentAclMember extends AbstractAclMember
    ResourceAclMember extends AbstractAclMember

HasContainerAclInterface


AclUserInterface
HasRolesInterface

ManagedByAclInterface

Not used:
    PermissionEnumInterface
    AclPermissionInterface
    AclPermissionSetInterface
    'project' => ProjectResourceAcl::class,
    'asset' => AssetResourceAcl::class,
    'doc_group' => DocumentGroupResourceAcl::class,
    'tenant_user' => TenantUserResourceAcl::class,
    'vendor' => VendorResourceAcl::class,
    'vendor_user' => VendorUserResourceAcl::class,
    'cust_spec' => CustomSpecificationResourceAcl::class,
    'template' => TemplateResourceAcl::class,
    'archive' => ArchiveResourceAcl::class,
