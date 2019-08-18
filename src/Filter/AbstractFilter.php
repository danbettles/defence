<?php declare(strict_types=1);

namespace ThreeStreams\Defence\Filter;

abstract class AbstractFilter implements FilterInterface
{
    /** @var array */
    private $options;

    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    private function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
