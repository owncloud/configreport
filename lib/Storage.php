<?php

namespace OCA\ConfigReport;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Result;
use OCP\IDBConnection;

class Storage {
	/** @var IDBConnection */
	private $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	public function getUsedTotalSpace(): int {
		$statement = null;
		try {
			$qb = $this->connection->getQueryBuilder();
			if ($this->connection->getDatabasePlatform() instanceof OraclePlatform) {
				// `size` is a reserved word in oracle db. need to escape it oracle-style.
				$qb->selectAlias($qb->createFunction('SUM(f."size")'), 'totalSize');
			} else {
				$qb->selectAlias($qb->createFunction('SUM(f.size)'), 'totalSize');
			}

			// base query
			$qb->from('filecache', 'f')
				->innerJoin('f', 'storages', 'st', $qb->expr()->eq('f.storage', 'st.numeric_id'))
				->innerJoin('st', 'mounts', 'mt', $qb->expr()->eq('mt.storage_id', 'st.numeric_id'));

			// check only root folders
			$qb->where($qb->expr()->eq('f.parent', $qb->expr()->literal(-1)))
				->andWhere($qb->expr()->gt('f.size', $qb->expr()->literal(0)));

			// only storages of users (exclude external storage)
			// we cannot just check for "files" path in filecache as external storages
			// can have such folder mounted
			if ($this->connection->getDatabasePlatform() instanceof MySQLPlatform) {
				$qb->andWhere("(`st`.`id` LIKE CONCAT('%::', `mt`.`user_id`) or `st`.`id` LIKE CONCAT('object::user:', `mt`.`user_id`))");
			} else {
				$qb->andWhere("(`st`.`id` LIKE '%::' || `mt`.`user_id` or `st`.`id` LIKE 'object::user:' || `mt`.`user_id`)");
			}

			$result = $qb->execute();
			/* @phan-suppress-next-line PhanDeprecatedFunction */
			return (int)$result->fetch()['totalSize'];
		} finally {
			if (isset($result) && ($result instanceof Result)) {
				/* @phan-suppress-next-line PhanDeprecatedFunction */
				$result->free();
			}
		}
	}
}
