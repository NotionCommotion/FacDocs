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

interface LogItemInterface
{
    public function isSuccessful():bool;
    public function getResponse():Response;
    public function getMessage():?string;
    public function getStatusCode():int;
    public function getAnticipatedStatusCode():int;
    public function getDebugMessage():string;
    public function getData():array;
    //public function getAuthorizationStatus():?AuthorizationStatusInterface;
}
