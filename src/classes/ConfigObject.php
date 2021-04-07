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
        // always return lowercase parameters!
        return array_map('strtolower', $this->parameters);
    }

    /**
     * @return array
     */
    public function getHelpers(): array
    {
        return $this->helpers;
    }

    public function getHelperByName(string $name) // return value or false
    {
        if (isset($this->helpers[$name])) {
            return $this->helpers[$name];
        }

        return false;
    }


    /**
     * @param array $helpers
     */
    public function setHelpers(array $helpers): void
    {
        foreach ($helpers as $helper) {
            $this->helpers[$helper['name']] = $helper['value'];
        }
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
