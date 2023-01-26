<?php

namespace Alancolant\LaravelPgsync\Temp;

use Error;

class PostgresqlRelation
{
    use PostgresqlUtils;

    public string $table;

    public ?string $es_name;

    public string $type; //one-to-one or many-to-many

    public ?array $foreign_key;

    public array $fields;

    public string $alias;

    public string $reference;

    /** @var array<PostgresqlRelation|array>|null */
    public ?array $relations;

    public function __construct(array $datas)
    {
        $this->table = $datas['table'];
        $this->alias = $datas['alias'] ?? "{$datas['reference']}__{$datas['table']}";
        $this->reference = $datas['reference'];
        $this->es_name = $datas['es_name'] ?? null;
        $this->type = $datas['type'];
        $this->foreign_key = $datas['foreign_key'] ?? null;
        $this->fields = $datas['fields'];
        $this->relations = $datas['relations'] ?? null;

        if ($this->relations) {
            $this->relations = array_map(
                fn ($relationIndex) => new self([
                    ...$this->relations[$relationIndex],
                    'reference' => $this->table,
                    'alias' => $this->alias.'__'.$this->relations[$relationIndex]['table']."__{$relationIndex}",
                ]),
                array_keys($this->relations)
            );
        }
    }

    public function toArray(): array
    {
        return [
            'table' => $this->table,
            'es_name' => $this->es_name,
            'type' => $this->type,
            'foreign_key' => $this->foreign_key,
            'fields' => $this->fields,
        ];
    }

    public function selectField(): string
    {
        return "\"{$this->alias}\".\"result\"";
    }

    protected function _getSelectSql(): string
    {
        $sql = "SELECT {$this->_getFieldsSql()} FROM \"{$this->table}\"";

        $sql .= $this->_getJoinsSql() ? " {$this->_getJoinsSql()}" : '';

        if ($this->type === 'many_to_many') {
            $sql .= " GROUP BY \"{$this->table}\".\"{$this->foreign_key['local']}\"";
        }

        return $sql;
    }

    protected function _getJoinsSql(): string
    {
        return implode(' ', array_map(fn ($relation) => $relation->_getJoinSql(), $this->relations ?? []));
    }

    protected function _getFieldsSql(): string
    {
        $additionalColumns = [];
        foreach ($this->relations ?? [] as $relation) {
            $additionalColumns[$relation->es_name] = $relation->selectField();
        }
        $rawJsonObject = $this->_getRawJsonbObjectForFields($this->fields, $this->table, $additionalColumns);
        $sql = match ($this->type) {
            'one_to_one' => "{$rawJsonObject} AS \"result\"",
            'many_to_many' => "JSON_AGG({$rawJsonObject}) AS \"result\"",
            default => throw new Error("Unsupported relation type {$this->type}")
        };
        $sql .= ", \"{$this->table}\".\"{$this->foreign_key['local']}\" AS \"pgsync__fk\"";

        return $sql;
    }

    public function _getJoinSql(): string
    {
        return "LEFT OUTER JOIN ({$this->_getSelectSql()}) AS \"{$this->alias}\" ON \"{$this->reference}\".\"{$this->foreign_key['parent']}\" = \"{$this->alias}\".\"pgsync__fk\"";
    }
}
