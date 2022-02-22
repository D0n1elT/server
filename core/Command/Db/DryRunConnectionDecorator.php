<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022, Louis Chmn <louis@chmn.me>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Core\Command\Db;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Console\Output\OutputInterface;

class DryRunConnectionDecorator {

	private \OC\DB\Connection $connection;
	private OutputInterface $output;

	public function __construct(\OC\DB\Connection $connection, OutputInterface $output) {
		$this->connection = $connection;
		$this->output = $output;
	}

	/**
	 * Output the SQL queries instead of running them.
	 *
	 * @param Schema $toSchema
	 *
	 * @throws Exception
	 */
	public function migrateToSchema(Schema $toSchema) {
		$sqlQueries = $this
			->getMigrator()
			->generateChangeScript($toSchema);

		$this->output->write($sqlQueries);
	}

	private function getMigrator() {
		// TODO properly inject those dependencies
		$random = \OC::$server->getSecureRandom();
		$platform = $this->connection->getDatabasePlatform();
		$config = \OC::$server->getConfig();
		$dispatcher = \OC::$server->getEventDispatcher();
		if ($platform instanceof SqlitePlatform) {
			return new \OC\DB\SQLiteMigrator($this->connection, $config, $dispatcher);
		} elseif ($platform instanceof OraclePlatform) {
			return new \OC\DB\OracleMigrator($this->connection, $config, $dispatcher);
		} elseif ($platform instanceof MySQLPlatform) {
			return new \OC\DB\MySQLMigrator($this->connection, $config, $dispatcher);
		} elseif ($platform instanceof PostgreSQL94Platform) {
			return new \OC\DB\PostgreSqlMigrator($this->connection, $config, $dispatcher);
		} else {
			return new \OC\DB\Migrator($this->connection, $config, $dispatcher);
		}
	}
}
