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
    private string $_defaultNamespace;

    public function __construct(Connection $connection, string $idKey = 'id', string $defaultNamespace = 'dbo')
    {
        $this->_connection = $connection;
        $this->_idKey = $idKey;
        $this->_defaultNamespace = $defaultNamespace;
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
    public function DeleteRecord(string $id, string $table, string|null $namespace = null): bool
    {
        $namespace = $namespace ?? $this->_defaultNamespace;
        $queryBuilder = $this->_connection->createQueryBuilder();
        $queryBuilder = $queryBuilder->delete("[$namespace].[$table]");
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
    public function InsertRecord(array $record, string $table, string|null $namespace = null): array
    {
        $namespace = $namespace ?? $this->_defaultNamespace;
        $queryBuilder = $this->_connection->createQueryBuilder();
        $queryBuilder = $queryBuilder->insert("[$namespace].[$table]");

        $validColumns = $this->GetColumnNames("[$namespace].[$table]");
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

        return $this->SelectRecord($record[$this->_idKey], $table, $namespace);
    }

    /**
     * @throws Exception
     */
    public function GetColumnNames(string $table): array
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
    public function GetColumns(string $table): array
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
    public function SelectRecord(string $id, string $table, string|null $namespace = null): array|null
    {
        $namespace = $namespace ?? $this->_defaultNamespace;
        $queryBuilder = $this->_connection->createQueryBuilder();
        $queryBuilder = $queryBuilder->select('*');
        $queryBuilder = $queryBuilder->from("[$namespace].[$table]");
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
    public function UpdateRecord(string $id, mixed $record, string $table, string|null $namespace = null): bool
    {
        $existingRecord = $this->SelectRecord($id, $table, $namespace);
        if ($existingRecord === null) return false;
        foreach ($record as $key => $value) if (is_string($value) && trim($value) === '') $record[$key] = null;
        $record = array_diff_assoc($record, $existingRecord);

        if (count($record) === 0) return false;

        $queryBuilder = $this->_connection->createQueryBuilder();
        $queryBuilder = $queryBuilder->update("[$namespace].[$table]");

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
