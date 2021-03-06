<?php
/**
 * File containing the Test Setup Factory base class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace eZ\Publish\API\Repository\Tests\SetupFactory;

use eZ\Publish\Core\Base\ServiceContainer;
use eZ\Publish\Core\Base\Container\Compiler;
use PDO;

/**
 * A Test Factory is used to setup the infrastructure for a tests, based on a
 * specific repository implementation to test.
 */
class LegacySolr extends Legacy
{
    /**
     * Returns a configured repository for testing.
     *
     * @param bool $initializeFromScratch
     *
     * @return \eZ\Publish\API\Repository\Repository
     */
    public function getRepository( $initializeFromScratch = true )
    {
        // Load repository first so all initialization steps are done
        $repository = parent::getRepository( $initializeFromScratch );

        if ( $initializeFromScratch )
        {
            $this->indexAll();
        }

        return $repository;
    }

    public function getServiceContainer()
    {
        if ( !isset( self::$serviceContainer ) )
        {
            $config = include __DIR__ . "/../../../../../../config.php";
            $installDir = $config['install_dir'];

            /** @var \Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder */
            $containerBuilder = include $config['container_builder_path'];

            /** @var \Symfony\Component\DependencyInjection\Loader\YamlFileLoader $loader */
            $loader->load( $this->getTestConfigurationFile() );

            $containerBuilder->addCompilerPass( new Compiler\Search\Solr\AggregateCriterionVisitorPass() );
            $containerBuilder->addCompilerPass( new Compiler\Search\Solr\AggregateFacetBuilderVisitorPass() );
            $containerBuilder->addCompilerPass( new Compiler\Search\Solr\AggregateFieldValueMapperPass() );
            $containerBuilder->addCompilerPass( new Compiler\Search\Solr\AggregateSortClauseVisitorPass() );
            $containerBuilder->addCompilerPass( new Compiler\Search\Solr\EndpointRegistryPass() );
            $containerBuilder->addCompilerPass( new Compiler\Search\FieldRegistryPass() );
            $containerBuilder->addCompilerPass( new Compiler\Search\SignalSlotPass() );

            $containerBuilder->setParameter(
                "legacy_dsn",
                self::$dsn
            );

            $containerBuilder->setParameter(
                "io_root_dir",
                self::$ioRootDir . '/' . $containerBuilder->getParameter( 'storage_dir' )
            );

            self::$serviceContainer = new ServiceContainer(
                $containerBuilder,
                $installDir,
                $config['cache_dir'],
                true,
                true
            );
        }

        return self::$serviceContainer;
    }

    /**
     * Indexes all Content objects.
     */
    protected function indexAll()
    {
        // @todo: Is there a nicer way to get access to all content objects? We
        // require this to run a full index here.
        /** @var \eZ\Publish\SPI\Persistence\Handler $persistenceHandler */
        $persistenceHandler = $this->getServiceContainer()->get( 'ezpublish.spi.persistence.legacy' );
        /** @var \eZ\Publish\SPI\Search\Handler $searchHandler */
        $searchHandler = $this->getServiceContainer()->get( 'ezpublish.spi.search.solr' );
        /** @var \eZ\Publish\Core\Persistence\Database\DatabaseHandler $databaseHandler */
        $databaseHandler = $this->getServiceContainer()->get( 'ezpublish.api.storage_engine.legacy.dbhandler' );

        $query = $databaseHandler
            ->createSelectQuery()
            ->select( 'id', 'current_version' )
            ->from( 'ezcontentobject' );

        $stmt = $query->prepare();
        $stmt->execute();

        $contentObjects = array();
        $locations = array();
        while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) )
        {
            $contentObjects[] = $persistenceHandler->contentHandler()->load(
                $row['id'],
                $row['current_version']
            );
            $locations = array_merge(
                $locations,
                $persistenceHandler->locationHandler()->loadLocationsByContent( $row["id"] )
            );
        }

        /** @var \eZ\Publish\Core\Search\Solr\Content\Handler $contentSearchHandler */
        $contentSearchHandler = $searchHandler->contentSearchHandler();
        $contentSearchHandler->purgeIndex();
        $contentSearchHandler->setCommit( true );
        /** @var \eZ\Publish\Core\Search\Elasticsearch\Content\Location\Handler $locationSearchHandler */
        $locationSearchHandler = $searchHandler->locationSearchHandler();
        $locationSearchHandler->purgeIndex();
        $locationSearchHandler->setCommit( true );

        $contentSearchHandler->bulkIndexContent( $contentObjects );
        $locationSearchHandler->bulkIndexLocations( $locations );
    }

    protected function getTestConfigurationFile()
    {
        return getenv( "CONTAINER_TEST_CONFIG" );
    }
}
