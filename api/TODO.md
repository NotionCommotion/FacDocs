MediaType identifiers should be something like /mediatype/text/plain.
    When attempting, get error. Unable to generate an IRI for the item of type App\Entity\Document\MediaType.
    See App\Entity\Document\MediaType and App\Provider\MediaTypeProvider.
xxx

Create doctrine filter or extension to filter Media by owner.
Make sure the voter limits media to owner as well.


After https://github.com/api-platform/core/issues/4965 is fixed, restore https://github.com/api-platform/core/blob/main/src/Symfony/Bundle/DependencyInjection/ApiPlatformExtension.php#L309.

@rvanlaak

Security for adding a document to an asset.


Remove comment from Doctrine code!!!    https://github.com/doctrine/orm/issues/10066#issuecomment-1258507664

If a POST is made to a many-to-many junction table with data (i.e. ProjectTeamMember), update instead of duplicating and causing unique constraint error (similar to standard many-to-many).

Consider making RbacOverride not have a surrogate key and making it user_id/resource_id.  Tried but couldn't get it working.


Read the following:
    https://timobakx.dev/php/api-platform/2021/06/06/altering-api-documentation-generated-by-apip.html

Soft deletes?  For media?


When removing (not deleting) from a collection (i.e. media from document, child asset from parent asset, etc):
    What should be returned?
    What method (i.e. DELETE or PUT for removing, POST or PUT for adding)?