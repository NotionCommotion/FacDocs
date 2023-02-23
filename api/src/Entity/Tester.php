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

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Provider\TesterProvider;

#[ApiResource(
    uriTemplate: '/test/custom',
    provider: TesterProvider::class,
    operations: [new GetCollection],
    openapiContext: ['summary' => 'Just used for testing']
)]

#[ApiResource(
    uriTemplate: '/test/test',
    provider: TesterProvider::class,
    operations: [new Get],
    openapiContext: ['summary' => 'Unit Testing (not working)']
)]

#[ApiResource(
    uriTemplate: '/test/permissions',
    provider: TesterProvider::class,
    operations: [new Get],
    openapiContext: ['summary' => 'Get all permission values.']
)]

#[ApiResource(
    uriTemplate: '/test/resource_acl_classes',
    provider: TesterProvider::class,
    operations: [new GetCollection],
    openapiContext: ['summary' => 'Get all resource ACL classes']
)]

#[ApiResource(
    uriTemplate: '/test/sql-json',
    provider: TesterProvider::class,
    operations: [new POST],
    formats: ['dirtyjson'],
    openapiContext: ['summary' => 'Get SQL in dirty JSON']
)]

#[ApiResource(
    uriTemplate: '/test/beautify-attributes',
    provider: TesterProvider::class,
    operations: [new GetCollection],
    openapiContext: [
        'summary' => 'Format PHP attributes', 
        'parameters' => [
            [
                'name' => 'class',
                'in' => 'query',
                'description' => 'A FQCN',
                'type' => 'string',
                'required' => true,
                'example'=> 'App\Entity\FooBar',
            ]
        ]
    ]
)]

#[ApiResource(
    uriTemplate: '/test/uid',
    provider: TesterProvider::class,
    operations: [new GET],
    openapiContext: [
        'summary' => 'Convert UIDs to various formats',
        'parameters' => [
            ['name'=>'uid', 'type'=>'string', 'in'=>'query', 'required'=>true]
        ],
    ]
)]

#[ApiResource(
    uriTemplate: '/test/id_getter',
    provider: TesterProvider::class,
    operations: [new GET],
    openapiContext: [
        'summary' => 'Get all IDs for a FQCN entity',
        'parameters' => [
            ['name'=>'tenantId', 'type'=>'string', 'in'=>'query', 'required'=>true],
            ['name'=>'fqcn', 'type'=>'string', 'in'=>'query', 'required'=>true],
        ],
    ]
)]

#[ApiResource(
    uriTemplate: '/test/schema/request_collection',
    provider: TesterProvider::class,
    operations: [new Get],
    openapiContext: [
        'summary' => 'Visualize schema generator - RequestCollection', 
        'parameters' => [
            [
                'name' => 'filter',
                'in' => 'query',
                'description' => 'Something to search for',
                'type' => 'string',
                'required' => false,
                'example'=> 'specification',
            ]
        ]
    ]
)]

#[ApiResource(
    uriTemplate: '/test/schema/open_api',
    provider: TesterProvider::class,
    operations: [new Get],
    openapiContext: [
        'summary' => 'Visualize schema generator - OpenApi', 
        'parameters' => [
            [
                'name' => 'filter',
                'in' => 'query',
                'description' => 'Something to search for',
                'type' => 'string',
                'required' => false,
                'example'=> 'specification',
            ]
        ]
    ]
)]

#[ApiResource(
    uriTemplate: '/test/schema/all',
    provider: TesterProvider::class,
    operations: [new Get],
    openapiContext: [
        'summary' => 'Visualize schema generator - All Schema', 
        'parameters' => [
            [
                'name' => 'filter',
                'in' => 'query',
                'description' => 'Something to search for',
                'type' => 'string',
                'required' => false,
                'example'=> 'specification',
            ]
        ]
    ]
)]

#[ApiResource(
    uriTemplate: '/test/accounts',
    provider: TesterProvider::class,
    operations: [new GetCollection],
    openapiContext: ['summary' => 'Get test user credentials']
)]

#[ApiResource(
    uriTemplate: '/test/clear-cache',
    provider: TesterProvider::class,
    operations: [new GetCollection],
    openapiContext: ['summary' => 'Clear cache']
)]

#[ApiResource(
    uriTemplate: '/test/get-class-file',
    provider: TesterProvider::class,
    operations: [new GetCollection],
    openapiContext: [
        'summary' => 'Get class file', 
        'parameters' => [
            [
                'name' => 'class',
                'in' => 'query',
                'description' => 'A FQCN',
                'type' => 'string',
                'required' => true,
                'example'=> 'Symfony\Component\HttpFoundation\Request',
            ]
        ]
    ]
)]
#[ApiResource(
    uriTemplate: '/test/get-class-file-location',
    provider: TesterProvider::class,
    operations: [new GetCollection],
    openapiContext: [
        'summary' => 'Get class file location', 
        'parameters' => [
            [
                'name' => 'class',
                'in' => 'query',
                'description' => 'A FQCN',
                'type' => 'string',
                'required' => true,
                'example'=> 'Symfony\Component\HttpFoundation\Request',
            ]
        ]
    ]
)]
class Tester
{
    #[ApiProperty(identifier: true)]
    private string $id;

    /*
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'format' => 'date-time'
        ]
    )]
    #[Groups(['uuid'])]
    private string $uuid;
    */
}
