<?php

declare(strict_types=1);

namespace App\DataFixtures\Loader;

use Fidry\AliceDataFixtures\LoaderInterface;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Nelmio\Alice\IsAServiceTrait;
use Psr\Log\LoggerInterface;

/**
 * @final
 */
/*final*/ class CustomOrderLoader implements LoaderInterface
{
    use IsAServiceTrait;

    public function __construct(private LoaderInterface $decoratedLoader, private array $fixturePersistOrder, private LoggerInterface $logger)
    {
    }
    /**
     * Pre process, persist and post process each object loaded.
     *
     * {@inheritdoc}
     */
    public function load(array $fixturesFiles, array $parameters = [], array $objects = [], PurgeMode $purgeMode = null): array
    {
        $listOrder = array_flip($this->fixturePersistOrder);

        $objects = $this->decoratedLoader->load($fixturesFiles, $parameters, $objects, $purgeMode);

        $ordered = [];
        foreach($objects as $object) {
            $ordered[($listOrder[get_class($object)]??-1)+1][] = $object;
        }
        $ordered[] = array_shift($ordered);

        // Testing only.
        /*
        $test=[];
        foreach(array_merge(...array_values($ordered)) as $o) {
            $test[get_class($o)] = 1+$test[get_class($o)]??0;
        }
        print_r($test);
        exit;
        */
        return array_merge(...array_values($ordered));
    }
}