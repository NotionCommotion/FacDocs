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

namespace App\OpenApi;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\OpenApi;
use App\Entity\User\SystemUser;
use ArrayObject;
use Symfony\Bundle\SecurityBundle\Security;

/*
Originally had separate JwtDecorator class to add and this class to remove, but when I started removing schema's, the JWT prompt went away.

Hide routes which are not used plus hide a few routes to all but system users.
Require JWT inorder to view documentation and remove routes based on user's role.
Note that this currently will not work because API-Platform's JS doesn't send the user credentials when loading docs, but hopefully will in future 2.7.
Maybe combine JwtDecorator?
*/
class OpenApiFactory implements OpenApiFactoryInterface
{
    private bool $isSystemUser;

    public function __construct(private OpenApiFactoryInterface $openApiFactory, private array $removedRoutes, private array $restrictedRoutes, private array $removedSchemas, private array $restrictedSchemas, Security $security)
    {
        $this->isSystemUser = ($user = $security->getUser()) !== null && !$user->isSystemUser();
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->openApiFactory)($context);

        $o = $openApi->getPaths()->getPath('/authentication_token')->getPost()->getRequestBody()->getContent()->offsetGet('application/json')->getSchema();                
        $o->offsetSet('properties', array_merge($o->offsetGet('properties'), ['orgId'=>['type'=>'string', 'nullable'=>false]]));
        $o->offsetSet('required', array_merge($o->offsetGet('required'), ['orgId']));

        // Remove unwanted paths
        $paths = $this->filterPaths($openApi->getPaths());
        $components = $openApi->getComponents();

        // Remove unwanted components
        $schemas = $this->filterSchemas($components->getSchemas());

        $components = new Components(
            new ArrayObject($schemas),
            $components->getResponses(),
            $components->getParameters(),
            $components->getExamples(),
            $components->getRequestBodies(),
            $components->getHeaders()
        );

        return $openApi
        ->withPaths($paths)
        // Why does this remove the JWT prompt??????????????????????
        //->withComponents($components)
        ;
    }

    // Following are used to hide paths and schemas
    private function filterPaths(Paths $paths): Paths
    {
        $filteredPaths = new Paths();
        foreach ($paths->getPaths() as $name => $item) {
            // If a prefix is configured on API Platform's routes, it must appear here.
            if ($this->isRemovedPath($name) || ($this->isRestrictedPath($name) && !$this->isSystemUser)) {
                continue;
            }
            $filteredPaths->addPath($name, $item);
        }

        return $filteredPaths;
    }

    private function filterSchemas(ArrayObject $arrayObject): ArrayObject
    {
        // Is this the correct way of doing this?
        // Should I also remove any SecurityScheme?
        $filteredSchemas = [];
        foreach ($arrayObject as $name => $item) {
            if ($this->isRemovedSchema($name) || ($this->isRestrictedSchema($name) && !$this->isSystemUser)) {
                continue;
            }
            $filteredSchemas[$name] = $item;
        }

        return new ArrayObject($filteredSchemas);
    }

    private function isRemovedPath(string $name): bool
    {
        return \in_array($name, $this->removedRoutes, true);
    }

    private function isRestrictedPath(string $name): bool
    {
        // Change to regex?
        return \in_array('/'.explode('/', $name)[1], $this->restrictedRoutes, true);
    }

    private function isRemovedSchema(string $name): bool
    {
        return \in_array(explode('.', $name)[0], $this->removedSchemas, true);
    }

    private function isRestrictedSchema(string $name): bool
    {
        return \in_array(explode('.', $name)[0], $this->restrictedSchemas, true);
    }

    /*
    Future.  Consider more flexiblity to hide paths?  Probably change to regex.
    See https://stackoverflow.com/a/70069759/1032531

    Allow removing routes per method.
    Instead of hardcoding removed paths, remove all paths which $user does not have access to based on the route's security attributes and the user's credentials.
    This hack returns an array with the path as the key, and either "*" to remove all operations or an array to remove specific operations.
    [
    '/uuids'=>'*',                  // Remove all operations
    '/uuids/{uuid}'=>'*',           // Remove all operations
    '/tenants'=>['post', 'get'],   // Remove only post operation
    '/tenants/{uuid}'=>['delete'], // Remove only delete operation
    ]
    */
    /*
    private function futureMethod(array $context = []): Tbd
    {
        $openApi = $this->openApiFactory->__invoke($context);
        $removedPaths = $this->getRemovedPaths();
        $paths = new Model\Paths;
        $pathArray = $openApi->getPaths()->getPaths();
        foreach($openApi->getPaths()->getPaths() as $path=>$pathItem) {
            if(!isset($removedPaths[$path])) {
                // No restrictions
                $paths->addPath($path, $pathItem);
            }
            elseif($removedPaths[$path]!=='*') {
                // Remove one or more operation
                foreach($removedPaths[$path] as $operation) {
                    $method = 'with'.ucFirst($operation);
                    $pathItem = $pathItem->$method(null);
                }
                $paths->addPath($path, $pathItem);
            }
            // else don't add this route to the documentation
        }
        $openApiTest = $openApi->withPaths($paths);

        return $openApi->withPaths($paths);
    }
    */
}
