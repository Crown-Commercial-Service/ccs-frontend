<?php

declare(strict_types=1);

namespace App\Tests\App\Mock;

class CMSContentMockFactory
{
    /**
     * The dynamic content field collection wrapper
     */
    public static function createMockContent(array $acfData): \Strata\Frontend\Content\Field\ContentFieldCollection
    {
        return new #[\AllowDynamicProperties] class ($acfData) extends \Strata\Frontend\Content\Field\ContentFieldCollection {
            private array $data;

            public function __construct(array $data)
            {
                foreach ($data as $key => $value) {
                    $this->$key = $value;
                }
                $this->data = $data;
            }

            public function get($name): ?\Strata\Frontend\Content\Field\ContentFieldInterface
            {
                return $this->offsetGet($name);
            }

            public function __get(string $key)
            {
                if (!array_key_exists($key, $this->data)) {
                    return null;
                }

                return $this->wrapValue($this->data[$key]);
            }

            public function offsetExists($index): bool
            {
                return array_key_exists($index, $this->data);
            }

            public function offsetGet($index): \Strata\Frontend\Content\Field\ContentFieldInterface
            {
                return $this->wrapValue($this->data[$index] ?? null);
            }

            private function wrapValue($rawValue): \Strata\Frontend\Content\Field\ContentFieldInterface
            {
                if ($rawValue === false || $rawValue === null || $rawValue === '') {
                    return new MockContentValue($rawValue);
                }

                if ($rawValue instanceof \Strata\Frontend\Content\Field\ContentFieldInterface) {
                    return $rawValue;
                }

                return new MockContentValue($rawValue);
            }

            public function offsetSet($offset, $value): void
            {
            }
            public function offsetUnset($offset): void
            {
            }
        };
    }
}
