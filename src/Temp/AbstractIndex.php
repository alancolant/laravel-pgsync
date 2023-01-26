<?php

namespace Alancolant\LaravelPgsync\Temp;

use Generator;

/** @phpstan-consistent-constructor */
abstract class AbstractIndex
{
    public string $table;

    public string $index;

    public ?array $fields;

    public ?array $relations;

    public function __construct(array $data)
    {
        $this->table = $data['table'];
        $this->index = $data['index'];
        $this->fields = $data['fields'];
        $this->relations = $data['relations'] ?? null;
    }

    abstract public function getAllDocuments(): Generator;

    abstract public function getDocumentsForRecords(array $records): array|Generator;

    public function toArray(): array
    {
        return [
            'table' => $this->table,
            'index' => $this->index,
            'fields' => $this->fields,
            'relations' => $this->relations,
        ];
    }
}
