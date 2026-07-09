<?php

declare(strict_types=1);

namespace App\Tests\App\Mock;

class MockContentValue implements \Strata\Frontend\Content\Field\ContentFieldInterface, \IteratorAggregate, \ArrayAccess, \Countable
{
    private $val;

    public function __construct($v)
    {
        $this->val = $v;
    }

    public function __get($prop)
    {
        if ($prop === 'value') {
            return $this->val;
        }

        if (is_array($this->val)) {
            return $this->wrapValue($this->val[$prop] ?? null);
        }

        return null;
    }

    public function __call($method, $args)
    {
        if ($method === 'byName' && is_array($this->val)) {
            return $this->val['sizes'][$args[0]] ?? null;
        }

        return $this->val;
    }

    public function getName(): string
    {
        return '';
    }

    public function getType(): string
    {
        return '';
    }

    public function getValue()
    {
        return $this->val;
    }

    public function hasHtml(): bool
    {
        return false;
    }

    public function setName(string $name): \Strata\Frontend\Content\Field\ContentFieldInterface
    {
        return $this;
    }

    public function __toString(): string
    {
        return is_scalar($this->val) ? (string) $this->val : '';
    }

    public function getIterator(): \Traversable
    {
        if (!is_array($this->val)) {
            return new \ArrayIterator([]);
        }

        return new \ArrayIterator(array_map(function ($item) {
            return $this->wrapValue($item);
        }, $this->val));
    }

    public function offsetExists($offset): bool
    {
        return is_array($this->val) && array_key_exists($offset, $this->val);
    }

    public function offsetGet($offset): mixed
    {
        if (!is_array($this->val)) {
            return null;
        }

        return $this->wrapValue($this->val[$offset] ?? null);
    }

    public function offsetSet($offset, $value): void
    {
        if (!is_array($this->val)) {
            $this->val = [];
        }

        $this->val[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        if (is_array($this->val)) {
            unset($this->val[$offset]);
        }
    }

    public function count(): int
    {
        if (is_array($this->val)) {
            return count($this->val);
        }

        return ($this->val === null || $this->val === false || $this->val === '') ? 0 : 1;
    }

    private function wrapValue($rawValue)
    {
        if ($rawValue === false || $rawValue === null || $rawValue === '') {
            return $rawValue;
        }

        if ($rawValue instanceof \Strata\Frontend\Content\Field\ContentFieldInterface) {
            return $rawValue;
        }

        return new self($rawValue);
    }
}
