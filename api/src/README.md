Login request POST to authentication_token with tenantId, email, and password.
    LogonUserProvider provides the user.  If a system user using anothers tenantId, sets the system user's tenant to that other tenant.
    Token is returns that includes the user ID and tenant ID.
Request adding a tenant entity.
    Client sends request with token.
    Server receive token and populates tokenUser and validated.
    BelongsToTenantSubscriber calls UserRetreiverService to get real tenant.
    

BelongsToTenantSubscriber
    BelongsToTenantInterface
        prePersist
            Sets tenant for new entities

App\EventListener\PersistTenantListener
    Saves default permissions

BlameListener
    BelongsToTenantInterface
        prePersist, preUpdate
            Sets BlameableListener::setUserValue using security context


AclMemberSubscriber
    HasResourceAclInterface
        prePersist, preUpdate
            Sets default permissions

ResourceMemberSubscriber
    ResourceMemberInterface
        prePersist, preUpdate
            Sets default permissions

App\EventListener\AuthenticationSuccessListener
    Adds public data to token
App\EventListener\JWTCreatedListener
    Adds private data to token
    

Not really used
    App\EventListener\DoctrineSchemaListener
    App\EventListener\HashUserPasswordListener
    App\EventListener\JWTAuthenticatedListener
    App\EventListener\RequiresAdditionalValidationSubscriber
    App\EventSubscriber\TestingActivitySubscriber

QUESTION: Why is FolderMemberAclExtension and FolderMemberAclExtension listening to the identical event?  Combine?
FolderMemberAclExtension
    HasResourceAclInterface
        Get, maybe GetCollection?
            Allows user to view.
FolderMemberAclExtension
    HasResourceAclInterface
        Get, not GetCollection?
            Allows user to view.

TenantEntityExtension
    BelongsToTenantInterface
        Get, GetCollection.  How is PUT, PATCH, DELETE enforced?
            Restricting viewing to tenant members.

ResourceMemberFilter
    ???
        ???
            Restricts vendors.

App\Security\LogonUserProvider
    Get's user from DB when logging on, and will then use tokens.
App\Security\TokenUser
    Used as User unless Doctrine user is needed.  TokenUser::createFromPayload() called by Jwt.

App\Processor\HttpMediaProcessor and App\Processor\AbstractMediaProcessor
App\Processor\DefaultPermissionProcessor
App\Processor\ArchiveCreaterDecoratedProcessor

App\Provider\ConfigProvider
App\Provider\DefaultResourceMemberProvider
App\Provider\DuplicatedMediaProvider



App\Service\UserRetreiverService
    Converts database token to Doctrine user.
App\Service\UserListRankingService
    TBD


#No longer used
BlameFixtureListener
    BelongsToTenantInterface
        prePersist, preUpdate
            Sets BlameableListener::setUserValue using fake values for creating fixtures.
