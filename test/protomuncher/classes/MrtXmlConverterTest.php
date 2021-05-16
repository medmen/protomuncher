<?php

declare(strict_types=1);

namespace protomuncher\classes;

use ErrorException;
use Medoo\Medoo;
use Monolog\Handler\NoopHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class MrtXmlConverterTest extends TestCase
{
    private ConfigObject $config;
    private Logger $logger;

    function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->logger = new Logger('test');
        $this->logger->pushHandler(new NoopHandler());

        $geraet = 1; // 1=MRT, 2=CT
        $this->config = new ConfigObject($geraet);

        $db = dirname(__DIR__) . '/../../conf/config.sqlite';
        $database = new Medoo([
            'database_type' => 'sqlite',
            'database_file' => $db
        ]);

        try {
            $configurationmanager = new ConfigurationManager($database, $this->config);
            $configurationmanager->populateConf();
        } catch (ErrorException $e) {
        }
    }

    public function testMriConvert()
    {
        $this->converter = new MrtXmlConverter($this->logger, $this->config);
        $this->converter->setinput(dirname(__DIR__) . '/fixtures/MRT.xml');
        $result_array = $this->converter->convert();

        $this->assertIsArray($result_array);
        $this->assertArrayHasKey('region', array_values($result_array)[0], "Array doesn't contain 'region'");
    }
}
