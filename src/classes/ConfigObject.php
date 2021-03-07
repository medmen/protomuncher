<?php
declare(strict_types=1);

namespace protomuncher\classes;

class ConfigObject
{
    private $geraet, $parameters, $helpers;

    function __construct(int $geraet)
    {
        $this->geraet = $geraet;
    }

    public function equals(ConfigObject $config): bool
    {
        return $this === $config;
    }

    public function getGeraet()
    {
        return $this->geraet;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getHelpers(): array
    {
        return $this->helpers;
    }

    /**
     * @param array $helpers
     */
    public function setHelpers(array $helpers): void
    {
        foreach ($helpers as $key => $val) {
            $this->helpers[$key] = $val;
        }
        $this->helpers = $helpers;
    }

    /**
     * @param array $parameters
     *  we will only assign array values!
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = array_values($parameters);
    }

}