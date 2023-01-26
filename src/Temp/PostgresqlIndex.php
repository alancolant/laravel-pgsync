<?php

namespace Alancolant\LaravelPgsync\Temp;

use Generator;
use Illuminate\Support\Facades\DB;

class PostgresqlIndex extends AbstractIndex
{
    use PostgresqlUtils;

    /** @var array<PostgresqlRelation|array>|null */
    public ?array $relations;

    public function __construct(array $data)
    {
        parent::__construct($data);
        if ($this->relations) {
            $this->relations = array_map(
                fn ($relationIndex) => new PostgresqlRelation([
                    ...$this->relations[$relationIndex],
                    'reference' => $data['table'],
                    'alias' => $this->relations[$relationIndex]['table']."__{$relationIndex}",
                ]),
                array_keys($this->relations)
            );
        }
    }

    public function getAllDocuments(): Generator
    {
        $prevId = 0;
        $chunkSize = 500;
        while (true) {
            $query = $this->_getSelectSql()."WHERE \"{$this->table}\".\"id\" > {$prevId} ORDER BY id ASC LIMIT {$chunkSize}";
            $rows = DB::getPdo()->query($query)->fetchAll();
            $rowsCount = 0;
            while ($row = array_shift($rows)) {
                $rowsCount++;
                $prevId = $row['id'];
                yield json_decode($row['result'], true);
            }
            if ($rowsCount < $chunkSize) {
                break;
            }
        }
    }

    public function getDocumentsForRecords(array $records): array
    {
        $recordsInSql = '('
            .implode(',', array_unique(array_map(fn ($record) => $record['id'], $records)))
            .')';
        $query = "{$this->_getSelectSql()} WHERE \"{$this->table}\".\"id\" IN {$recordsInSql}";

        return $this->_executeAndGetDocuments($query);
    }

    private function _executeAndGetDocuments(string $query): array
    {
        $results = DB::getPdo()->query($query)->fetchAll();

        return array_map(fn ($result) => json_decode($result['result'], true), $results);
    }

    protected function _getSelectSql(): string
    {
        $selectSql = "SELECT {$this->_getFieldsSql()} AS \"result\", \"{$this->table}\".\"id\" FROM \"{$this->table}\"";
        $selectSql .= $this->_getJoinsSql() ? " {$this->_getJoinsSql()}" : '';

        return $selectSql;
    }

    protected function _getFieldsSql(): string
    {
        $additionalColumns = [];
        foreach ($this->relations ?? [] as $relation) {
            $additionalColumns[$relation->es_name] = $relation->selectField();
        }

        return $this->_getRawJsonbObjectForFields($this->fields, $this->table, $additionalColumns);
    }

    public function _getJoinsSql(): string
    {
        return implode(' ', array_map(fn ($relation) => $relation->_getJoinSql(), $this->relations ?? []));
    }

    public function toArray(): array
    {
        $this->relations = array_map(
            fn (PostgresqlRelation $relation) => $relation->toArray(),
            $this->relations
        );

        return parent::toArray();
    }
}
