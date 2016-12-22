<?php

/**
 * This file is part of the eZ Platform Solr Search Engine package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 *
 * @version //autogentag//
 */
namespace EzSystems\EzPlatformSolrSearchEngineBundle\Tests\DependencyInjection;

use EzSystems\EzPlatformSolrSearchEngineBundle\DependencyInjection\EzSystemsEzPlatformSolrSearchEngineExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\Yaml\Yaml;

class EzPublishEzPlatformSolrSearchEngineExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @var \EzSystems\EzPlatformSolrSearchEngineBundle\DependencyInjection\EzSystemsEzPlatformSolrSearchEngineExtension
     */
    private $extension;

    protected function setUp()
    {
        $this->extension = new EzSystemsEzPlatformSolrSearchEngineExtension();

        parent::setUp();
    }

    protected function getContainerExtensions()
    {
        return array($this->extension);
    }

    protected function getMinimalConfiguration()
    {
        return Yaml::parse(
            file_get_contents(__DIR__ . '/Fixtures/minimal.yml')
        );
    }

    public function testEmpty()
    {
        $this->load();
    }

    public function dataProviderForTestEndpoint()
    {
        return array(
            array(
                'endpoint_dsn',
                array(
                    'dsn' => 'https://jura:pura@10.10.10.10:5434/jolr',
                    'core' => 'core0',
                ),
                array(
                    'scheme' => 'https',
                    'host' => '10.10.10.10',
                    'port' => 5434,
                    'user' => 'jura',
                    'pass' => 'pura',
                    'path' => '/jolr',
                    'core' => 'core0',
                ),
            ),
            array(
                'endpoint_standalone',
                array(
                    'scheme' => 'https',
                    'host' => '22.22.22.22',
                    'port' => 1232,
                    'user' => 'jura',
                    'pass' => 'pura',
                    'path' => '/holr',
                    'core' => 'core1',
                ),
                array(
                    'scheme' => 'https',
                    'host' => '22.22.22.22',
                    'port' => 1232,
                    'user' => 'jura',
                    'pass' => 'pura',
                    'path' => '/holr',
                    'core' => 'core1',
                ),
            ),
            array(
                'endpoint_override',
                array(
                    'dsn' => 'https://miles:teg@257.258.259.400:5555/noship',
                    'scheme' => 'http',
                    'host' => 'farm.com',
                    'port' => 1234,
                    'core' => 'core2',
                    'user' => 'darwi',
                    'pass' => 'odrade',
                    'path' => '/dunr',
                ),
                array(
                    'scheme' => 'https',
                    'host' => '257.258.259.400',
                    'port' => 5555,
                    'user' => 'miles',
                    'pass' => 'teg',
                    'path' => '/noship',
                    'core' => 'core2',
                ),
            ),
            array(
                'endpoint_defaults',
                array(
                    'core' => 'core3',
                ),
                array(
                    'scheme' => 'http',
                    'host' => '127.0.0.1',
                    'port' => 8983,
                    'user' => null,
                    'pass' => null,
                    'path' => '/solr',
                    'core' => 'core3',
                ),
            ),
        );
    }

    /**
     * @dataProvider dataProviderForTestEndpoint
     *
     * @param string $endpointName
     * @param array $endpointValues
     * @param array $expectedArgument
     */
    public function testEndpoint($endpointName, $endpointValues, $expectedArgument)
    {
        $this->load(array('endpoints' => array($endpointName => $endpointValues)));

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            "ez_search_engine_solr.endpoints.{$endpointName}",
            'ezpublish.search.solr.endpoint',
            array('alias' => $endpointName)
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            "ez_search_engine_solr.endpoints.{$endpointName}",
            0,
            $expectedArgument
        );
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testEndpointCoreRequired()
    {
        $this->load(
            array(
                'endpoints' => array(
                    'endpoint0' => array(
                        'dsn' => 'https://12.13.14.15:4444/solr',
                    ),
                ),
            )
        );
    }

    public function dataProviderForTestConnection()
    {
        return array(
            array(
                array(
                    'connections' => array(),
                ),
            ),
            array(
                array(
                    'connections' => array(
                        'connection1' => array(),
                    ),
                ),
            ),
            array(
                array(
                    'connections' => array(
                        'connection1' => array(
                            'entry_endpoints' => array(),
                            'mapping' => array(),
                        ),
                    ),
                ),
            ),
            array(
                array(
                    'connections' => array(
                        'connection1' => array(
                            'entry_endpoints' => array(),
                            'mapping' => array(),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * @param array $configurationValues
     *
     * @dataProvider dataProviderForTestConnection
     */
    public function testConnectionLoad($configurationValues)
    {
        $this->load($configurationValues);
    }

    public function testConnection()
    {
        $configurationValues = array(
            'connections' => array(
                'connection1' => array(
                    'entry_endpoints' => array(
                        'endpoint1',
                        'endpoint2',
                    ),
                    'mapping' => array(
                        'translations' => array(
                            'cro-HR' => 'endpoint1',
                            'eng-GB' => 'endpoint2',
                            'gal-MW' => 'endpoint3',
                        ),
                        'default' => 'endpoint4',
                        'main_translations' => 'endpoint5',
                    ),
                ),
            ),
        );

        $this->load($configurationValues);

        $this->assertContainerBuilderHasParameter(
            'ez_search_engine_solr.default_connection',
            'connection1'
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'ez_search_engine_solr.connection.connection1.endpoint_resolver_id',
            0,
            array(
                'endpoint1',
                'endpoint2',
            )
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'ez_search_engine_solr.connection.connection1.endpoint_resolver_id',
            1,
            array(
                'cro-HR' => 'endpoint1',
                'eng-GB' => 'endpoint2',
                'gal-MW' => 'endpoint3',
            )
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'ez_search_engine_solr.connection.connection1.endpoint_resolver_id',
            2,
            'endpoint4'
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'ez_search_engine_solr.connection.connection1.endpoint_resolver_id',
            3,
            'endpoint5'
        );
        $this->assertContainerBuilderHasService(
            'ez_search_engine_solr.connection.connection1.core_filter_id'
        );
        $this->assertContainerBuilderHasService(
            'ez_search_engine_solr.connection.connection1.gateway_id'
        );
    }
}
