<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

use ILIAS\DI\Container;
use ILIAS\Filesystem\Provider\Configuration\LocalConfig;
use ILIAS\Filesystem\Provider\FlySystem\FlySystemFilesystemFactory;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\ResourceStorage\Collection\CollectionBuilder;
use ILIAS\ResourceStorage\Collection\ResourceCollection;
use ILIAS\ResourceStorage\Identification\ResourceCollectionIdentification;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use ILIAS\ResourceStorage\Resource\InfoResolver\StreamInfoResolver;
use ILIAS\ResourceStorage\Resource\Repository\CollectionDBRepository;
use ILIAS\ResourceStorage\Resource\ResourceBuilder;
use ILIAS\ResourceStorage\Stakeholder\ResourceStakeholder;
use ILIAS\Setup\Environment;
use ILIAS\ResourceStorage\Services;
use ILIAS\ResourceStorage\Manager\Manager;
use ILIAS\ResourceStorage\Preloader\StandardRepositoryPreloader;
use ILIAS\ResourceStorage\Repositories;
use ILIAS\ResourceStorage\Flavour\FlavourBuilder;
use ILIAS\ResourceStorage\Events\Subject;
use ILIAS\Setup\Objective\DirectoryCreatedObjective;

/**
 * Class ilResourceStorageMigrationHelper
 * @author Fabian Schmid <fabian@sr.solutions.ch>
 */
class ilResourceStorageMigrationHelper
{
    protected string $client_data_dir;
    protected ilDBInterface $database;
    protected FlavourBuilder $flavour_builder;
    protected ResourceBuilder $resource_builder;
    protected CollectionBuilder $collection_builder;
    protected ResourceStakeholder $stakeholder;
    protected Repositories $repositories;
    protected Manager $manager;

    /**
     * ilResourceStorageMigrationHelper constructor.
     * @param string $client_data_dir
     * @param ilDBInterface $database
     */
    public function __construct(
        ResourceStakeholder $stakeholder,
        Environment $environment
    ) {
        $this->stakeholder = $stakeholder;
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $db = $environment->getResource(Environment::RESOURCE_DATABASE);
        $ilias_ini = $environment->getResource(Environment::RESOURCE_ILIAS_INI);
        $client_id = $environment->getResource(Environment::RESOURCE_CLIENT_ID);
        $data_dir = $ilias_ini->readVariable('clients', 'datadir');
        $client_data_dir = "{$data_dir}/{$client_id}";
        if (!defined("CLIENT_WEB_DIR")) {
            define("CLIENT_WEB_DIR", dirname(__DIR__, 4) . "/data/" . $client_id);
        }
        if (!defined("ILIAS_WEB_DIR")) {
            define("ILIAS_WEB_DIR", dirname(__DIR__, 4));
        }
        if (!defined("CLIENT_ID")) {
            define("CLIENT_ID", $client_id);
        }
        if (!defined("ILIAS_DATA_DIR")) {
            define("ILIAS_DATA_DIR", $data_dir);
        }
        $this->client_data_dir = $client_data_dir;
        $this->database = $db;

        if (!is_writable("{$data_dir}/{$client_id}/storage/fsv2")) {
            throw new Exception('storage directory is not writable, abort...');
        }

        // Build Container
        $init = new InitResourceStorage();
        $container = new Container();
        $container['ilDB'] = $db;
        $storageConfiguration = new LocalConfig($client_data_dir);
        $f = new FlySystemFilesystemFactory();
        $container['filesystem.storage'] = $f->getLocal($storageConfiguration);

        $this->resource_builder = $init->getResourceBuilder($container);
        $this->flavour_builder = $init->getFlavourBuilder($container);
        $this->collection_builder = new CollectionBuilder(
            new CollectionDBRepository($db),
            new Subject()
        );

        $this->repositories = $container[InitResourceStorage::D_REPOSITORIES];

        $this->manager = new Manager(
            $this->resource_builder,
            $this->collection_builder,
            $container[InitResourceStorage::D_REPOSITORY_PRELOADER]
        );
    }

    /**
     * @return \ilDatabaseInitializedObjective[]|\ilDatabaseUpdatedObjective[]|\ilIniFilesLoadedObjective[]
     */
    public static function getPreconditions(): array
    {
        return [
            new ilIniFilesLoadedObjective(),
            new ilDatabaseInitializedObjective(),
            new ilDatabaseUpdatedObjective(),
            new ilDatabaseUpdateStepsExecutedObjective(new ilResourceStorageDB90()),
            new ilStorageContainersExistingObjective()
        ];
    }

    public function getClientDataDir(): string
    {
        return $this->client_data_dir;
    }

    public function getDatabase(): ilDBInterface
    {
        return $this->database;
    }

    public function getStakeholder(): ResourceStakeholder
    {
        return $this->stakeholder;
    }

    public function getResourceBuilder(): ResourceBuilder
    {
        return $this->resource_builder;
    }
    public function getFlavourBuilder(): FlavourBuilder
    {
        return $this->flavour_builder;
    }

    public function getCollectionBuilder(): CollectionBuilder
    {
        return $this->collection_builder;
    }

    public function getManager(): Manager
    {
        return $this->manager;
    }

    public function moveResourceToNewStakeholderAndOwner(
        ResourceIdentification $resource_identification,
        ResourceStakeholder $old_stakeholder,
        ResourceStakeholder $new_stakeholder,
        ?int $new_owner_id = null
    ): void {
        $resource = $this->manager->getResource($resource_identification);
        $resource->removeStakeholder($old_stakeholder);
        $this->repositories->getStakeholderRepository()->deregister($resource_identification, $old_stakeholder);
        $resource->addStakeholder($new_stakeholder);
        $this->repositories->getStakeholderRepository()->register($resource_identification, $new_stakeholder);

        if ($new_owner_id !== null) {
            foreach ($resource->getAllRevisionsIncludingDraft() as $revision) {
                $revision->setOwnerId($new_owner_id);
            }
        }

        $this->resource_builder->store($resource);
    }


    public function moveFilesOfPathToCollection(
        string $absolute_path,
        int $resource_owner_id,
        int $collection_owner_user_id = ResourceCollection::NO_SPECIFIC_OWNER,
        ?Closure $file_name_callback = null,
        ?Closure $revision_name_callback = null
    ): ?ResourceCollectionIdentification {
        $collection = $this->getCollectionBuilder()->new($collection_owner_user_id);
        /** @var SplFileInfo $file_info */
        foreach (new DirectoryIterator($absolute_path) as $file_info) {
            if (!$file_info->isFile()) {
                continue;
            }
            $resource_id = $this->movePathToStorage(
                $file_info->getRealPath(),
                $resource_owner_id,
                $file_name_callback,
                $revision_name_callback
            );
            if ($resource_id !== null) {
                $collection->add($resource_id);
            }
        }

        if ($this->getCollectionBuilder()->store($collection)) {
            return $collection->getIdentification();
        }
        return null;
    }

    public function moveFilesOfPatternToCollection(
        string $absolute_base_path,
        string $pattern,
        int $resource_owner_id,
        int $collection_owner_user_id = ResourceCollection::NO_SPECIFIC_OWNER,
        ?Closure $file_name_callback = null,
        ?Closure $revision_name_callback = null
    ): ?ResourceCollectionIdentification {
        $collection = $this->getCollectionBuilder()->new($collection_owner_user_id);

        $regex_iterator = $this->buildRecursivePatternIterator($absolute_base_path, $pattern);

        foreach ($regex_iterator as $file_info) {
            if (!$file_info->isFile()) {
                continue;
            }
            $resource_id = $this->movePathToStorage(
                $file_info->getRealPath(),
                $resource_owner_id,
                $file_name_callback,
                $revision_name_callback
            );
            if ($resource_id !== null) {
                $collection->add($resource_id);
            }
        }
        if ($collection->count() === 0) {
            return null;
        }

        if ($this->getCollectionBuilder()->store($collection)) {
            return $collection->getIdentification();
        }
        return null;
    }

    public function moveFirstFileOfPatternToStorage(
        string $absolute_base_path,
        string $pattern,
        int $resource_owner_id,
        ?Closure $file_name_callback = null,
        ?Closure $revision_name_callback = null
    ): ?ResourceIdentification {
        $regex_iterator = $this->buildRecursivePatternIterator($absolute_base_path, $pattern);

        foreach ($regex_iterator as $file_info) {
            if (!$file_info->isFile()) {
                continue;
            }
            $resource_id = $this->movePathToStorage(
                $file_info->getRealPath(),
                $resource_owner_id,
                $file_name_callback,
                $revision_name_callback
            );
            if ($resource_id !== null) {
                return $resource_id; // stop after first file
            }
        }

        return null;
    }

    public function movePathToStorage(
        string $absolute_path,
        int $owner_user_id,
        ?Closure $file_name_callback = null,
        ?Closure $revision_name_callback = null,
        ?bool $copy_instead_of_move = false
    ): ?ResourceIdentification {
        try {
            // in some cases fopen throws a warning instead of returning false
            $open_path = fopen($absolute_path, 'rb');
        } catch (Throwable $e) {
            return null;
        }

        if ($open_path === false) {
            return null;
        }
        $stream = Streams::ofResource($open_path);

        // create new resource from legacy files stream
        $revision_title = $revision_name_callback !== null
            ? $revision_name_callback(basename($absolute_path))
            : basename($absolute_path);

        $file_name = $file_name_callback !== null
            ? $file_name_callback(basename($absolute_path))
            : null;

        $resource = $this->resource_builder->newFromStream(
            $stream,
            new StreamInfoResolver(
                $stream,
                1,
                $owner_user_id,
                $revision_title,
                $file_name
            ),
            $copy_instead_of_move ?? false
        );

        // add bibliographic stakeholder and store resource
        $resource->addStakeholder($this->stakeholder);
        $this->resource_builder->store($resource);

        return $resource->getIdentification();
    }

    protected function buildRecursivePatternIterator(
        string $absolute_base_path,
        string $pattern = '.*'
    ): RecursiveRegexIterator {
        return new RecursiveRegexIterator(
            new RecursiveDirectoryIterator($absolute_base_path),
            $pattern,
            RecursiveRegexIterator::MATCH
        );
    }
}
