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

namespace App\ArchiveBuilder\Dto;

use Exception;

class Content
{
    public function __construct(private array $value)
    {
    }

    public function getValue(string $name): string|int|float|null
    {
        return $this->value[$name];
    }

    public function getValues(array $names = []): array
    {
        if ($names === []) {
            return $this->value;
        }
        $value = array_intersect_key($this->value, array_flip($names));
        if (\count($names) !== \count($value)) {
            throw new Exception(sprintf('Properties %s do not exist', implode(', ', array_diff_key($this->value, array_flip($names)))));
        }

        return $value;
    }

    public function addValue(string $name, string|int|float|null $value): self
    {
        if (isset($this->value[$name])) {
            throw new Exception(sprintf('Properties %s is already defined', $name));
        }

        return $this;
    }

    public function addValues(array $nameValues): self
    {
        if ($errors = array_intersect_key($this->value, $nameValues)) {
            throw new Exception(sprintf('Properties %s are already defined', implode(', ', array_keys($errors))));
        }
        $this->value = array_merge($this->value, $nameValues);

        return $this;
    }

    public function clone(array $nameValues = []): self
    {
        return clone ($this)->addValues($nameValues);
    }
}
