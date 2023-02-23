<?php

declare(strict_types=1);

namespace App\Test\Model\Api\LogFormatter;
use App\Test\Service\TestLoggerService;
use App\Test\Model\Api\TestLogItem;
use App\Test\Model\Api\EntityResponse;
use App\Test\Model\Api\ApiRequest;

class MinimumLogFormatter implements LogFormatterInterface
{
    public function process(TestLoggerService $testLoggerService):void
    {
        echo(PHP_EOL);
		foreach($testLoggerService as $logItem) {
            $entityResponse = $logItem->getEntityResponse();
            $apiRequest = $entityResponse->getApiRequest();
            printf('%-8s %-40s %-8s %s %s'.PHP_EOL,
				$apiRequest->getMethod(),
				$apiRequest->getPath(),
				$entityResponse->isSuccessful()?'SUCCESS':'ERROR',
				$entityResponse->getStatusCode(),
				$logItem->getMessage()??$this->getDefaultMessage($apiRequest)
			);
        }
    }

    private function getDefaultMessage(ApiRequest $apiRequest):?string
    {
        return 'Standard request';
    }
}
