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

namespace App\Test\Model\Api;
use ApiPlatform\Symfony\Bundle\Test\Response;

interface ResponseInterface
{
    public function assert(?int $anticipatedStatusCode=null, AssertEnum ...$assertEnum):self;
    public function getExpectedContentType():string;
    public function log(string $message=null, ?string $notes=null, array $extra=[]):self;
    public function echo():self;
    public function getResponse():Response;
    public function getAnticipatedStatusCode():?int;
    public function getInfo():array;
    public function getStatusCode():int;
    public function isSuccessful():bool;
    public function debug(bool $verbous=false):array;
    public function getPath():string;
    public function getMethod():string;
    public function getExtra():array;
    public function getRequestHeaders():array;
    public function getHeaders():array;
    public function setAnticipatedStatusCode(?int ...$anticipatedStatusCode):self;
}