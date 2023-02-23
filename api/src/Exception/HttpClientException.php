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

namespace App\Exception;

use Exception;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class HttpClientException extends Exception
{
    public function __construct(ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();
        parent::__construct($statusCode < 500 ? $response->getContent(false) : 'Server error', $statusCode);
    }
}
