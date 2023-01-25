<?php

namespace Alancolant\LaravelPgsync\Publishers;

use Alancolant\LaravelPgsync\Types\DeleteEvent;
use Alancolant\LaravelPgsync\Types\InsertEvent;
use Alancolant\LaravelPgsync\Types\UpdateEvent;

abstract class AbstractPublisher
{
    public function __construct()
    {
    }

    public function handleUpdateWithSoftDelete(UpdateEvent &$event): bool
    {
        if (! array_key_exists('deleted_at', $event->record)) {
            return $this->handleUpdate($event);
        }
        if ($event->record['deleted_at'] !== null && $event->old['deleted_at'] === null) {
            $deleteEvent = new DeleteEvent($event->table, $event->old);

            return $this->handleDelete($deleteEvent);
        }
        if ($event->record['deleted_at'] === null && $event->old['deleted_at'] !== null) {
            $insertEvent = new InsertEvent($event->table, $event->record);

            return $this->handleInsert($insertEvent);
        }
        if ($event->record['deleted_at'] !== null && $event->old['deleted_at'] !== null) {
            return true;
        }

        return $this->handleUpdate($event);
    }

    abstract public function handleDelete(DeleteEvent &$event): bool;

    abstract public function handleInsert(InsertEvent &$event): bool;

    abstract public function handleUpdate(UpdateEvent &$event): bool;

    protected function _getIndicesForTable(string &$table): array
    {
        return collect(config('pgsync.indices'))->where('table', $table)->toArray();
    }

    protected function _getRecordFieldsForIndex(array $record, array $indice): array
    {
        return array_filter($record,
            function ($_, $field) use (&$indice) {
                foreach ([...$indice['fields'], 'id', 'deleted_at'] as $expectedField) {
                    if (fnmatch($expectedField, $field)) {
                        return true;
                    }
                }

                return false;
            },
            ARRAY_FILTER_USE_BOTH
        );
    }
}
