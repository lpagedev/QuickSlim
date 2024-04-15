<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace QuickSlim\Services;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

class QuickDatabaseService
{
    private Connection $_connection;
    private string $_idKey;

    public function __construct(Connection $connection, string $idKey = 'id')
    {
        $this->_connection = $connection;
        $this->_idKey = $idKey;
    }

    public function GetConnection(): Connection
    {
        return $this->_connection;
    }

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        if ($this->_connection->isConnected()) {
            for ($t = 1; $t < $this->_connection->getTransactionNestingLevel(); $t++) $this->_connection->rollBack();
            $this->_connection->close();
        }
    }

    /**
     * @throws Exception
     */
    public function DeleteRecord(string $table, string $id): bool
    {
        $queryBuilder = $this->_connection->createQueryBuilder();
        $queryBuilder = $queryBuilder->delete("[$table]");
        $queryBuilder = $queryBuilder->where("[$this->_idKey] = " . $queryBuilder->createNamedParameter($id));

        $this->_connection->beginTransaction();
        $queryBuilder->executeStatement();
        $this->_connection->commit();

        return true;
    }

    /**
     * @throws Exception
     */
    public function ExecuteStatement(string $statement, ?array $parameters = [], $transactional = true): bool
    {
        if ($transactional) $this->_connection->beginTransaction();
        $this->_connection->executeStatement($statement, $parameters);
        if ($transactional) $this->_connection->commit();
        return true;
    }

    /**
     * @throws Exception
     */
    public function InsertRecord(string $table, array $record): array
    {
        $queryBuilder = $this->_connection->createQueryBuilder();
        $queryBuilder = $queryBuilder->insert("[$table]");

        $validColumns = $this->GetColumnNames($table);
        $record[$this->_idKey] = (is_string($record[$this->_idKey]) && trim($record[$this->_idKey]) !== '') ? $record[$this->_idKey] : $this->GetUniqueId();
        foreach ($record as $key => $value) {
            if (in_array($key, $validColumns)) {
                if ((is_string($value) && trim($value) == '') || $value == null) continue;
                $queryBuilder->setValue("[$key]", $queryBuilder->createNamedParameter($value));
            }
        }

        $this->_connection->beginTransaction();
        $queryBuilder->executeStatement();
        $this->_connection->commit();

        return $this->SelectRecord($table, $record[$this->_idKey]);
    }

    /**
     * @throws Exception
     */
    public function GetColumnNames($table): array
    {
        $results = [];
        $columns = $this->GetColumns($table);
        foreach ($columns as $column) $results[] = $column->getName();
        return $results;
    }

    /**
     * @return Column[]
     * @throws Exception
     */
    public function GetColumns($table): array
    {
        $schemaManager = $this->_connection->createSchemaManager();
        return $schemaManager->listTableColumns($table);
    }

    /**
     * @throws Exception
     */
    public function GetUniqueId(): string
    {
        return $this->SelectSingleValue(/** @lang SQL */ 'SELECT NEWID() As [ID]');
    }

    /**
     * @throws Exception
     */
    public function SelectSingleValue(string $statement, ?array $parameters = []): string|null
    {
        $resultSet = $this->_connection->executeQuery($statement, $parameters);
        $results = $resultSet->fetchOne();
        if ($results) return $results;
        return null;
    }

    /**
     * @throws Exception
     */
    public function ExecuteQuery(string $statement, ?array $parameters = []): array
    {
        $resultSet = $this->_connection->executeQuery($statement, $parameters);
        return $resultSet->fetchAllAssociative();
    }

    /**
     * @throws Exception
     */
    public function SelectRecord(string $table, string $id): array|null
    {
        $queryBuilder = $this->_connection->createQueryBuilder();
        $queryBuilder = $queryBuilder->select('*');
        $queryBuilder = $queryBuilder->from("[$table]");
        $queryBuilder = $queryBuilder->where("[$this->_idKey] = " . $queryBuilder->createNamedParameter($id));
        $resultSet = $queryBuilder->executeQuery();
        $record = $resultSet->fetchAssociative();
        return ($record) ?: null;
    }

    /**
     * @return String[]
     * @throws Exception
     */
    public function GetTableNames(): array
    {
        $results = [];
        $tables = $this->GetTables();
        foreach ($tables as $table) $results[] = $table->getName();
        return $results;
    }

    /**
     * @return Table[]
     * @throws Exception
     */
    public function GetTables(): array
    {
        $schemaManager = $this->_connection->createSchemaManager();
        return $schemaManager->listTables();
    }

    /**
     * @throws Exception
     */
    public function UpdateRecord($table, $id, $record): bool
    {
        $existingRecord = $this->SelectRecord($table, $id);
        if ($existingRecord === null) return false;
        foreach ($record as $key => $value) if (is_string($value) && trim($value) === '') $record[$key] = null;
        $record = array_diff_assoc($record, $existingRecord);

        if (count($record) === 0) return false;

        $queryBuilder = $this->_connection->createQueryBuilder();
        $queryBuilder = $queryBuilder->update("[$table]");

        foreach ($record as $key => $value) {
            $queryBuilder = $queryBuilder->set("[$key]", $queryBuilder->createNamedParameter($value));
        }
        $queryBuilder = $queryBuilder->where("[$this->_idKey] = " . $queryBuilder->createNamedParameter($id));

        $this->_connection->beginTransaction();
        $queryBuilder->executeStatement();
        $this->_connection->commit();

        return true;
    }

    /**
     * @throws Exception
     */
    public function GetPermissionsForObject($name): array
    {
        return $this->ExecuteQuery(/** @lang SQL */ 'SELECT [permission_name] FROM fn_my_permissions (:name, \'OBJECT\') WHERE subentity_name = \'\';', ['name' => $name]);
    }
}
