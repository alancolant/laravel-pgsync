<?php

namespace Alancolant\LaravelPgsync\Publishers;

use Alancolant\LaravelPgsync\Types\DeleteEvent;
use Alancolant\LaravelPgsync\Types\InsertEvent;
use Alancolant\LaravelPgsync\Types\UpdateEvent;
use Error;
use Illuminate\Support\Facades\DB;

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
        return collect(config('pgsync.indices'))->where('table', $table)->values()->toArray();
    }

    protected function _getRecordFieldsForIndex(array $record, array $indice): array
    {
        $fieldsBuildObjectString = implode(',',
            array_map(function ($field) use (&$indice) {
                if (is_array($field)) {
                    if (array_key_exists('db_field', $field)) {
                        return "'{$field['es_field']}',\"{$indice['table']}\".\"{$field['db_field']}\"";
                    } elseif (array_key_exists('db_query', $field)) {
                        $fieldScript = str_replace('{{prefix}}', "{$indice['table']}.", $field['db_query']);

                        return "'{$field['es_field']}',{$fieldScript}";
                    } else {
                        throw new Error('Cannot transform this field: '.json_encode($field));
                    }
                }

                return "'{$field}',\"{$indice['table']}\".\"{$field}\"";
            }, [...$indice['fields'], 'id']));

        $query = "SELECT to_jsonb(JSONB_BUILD_OBJECT({$fieldsBuildObjectString})) AS res FROM {$indice['table']} WHERE \"{$indice['table']}\".\"id\" = {$record['id']}";
        $query = "SELECT pgsync_res.* FROM ({$query}) AS pgsync_res";
        $rec = json_decode(DB::getPdo()->query($query)->fetch()['res'], true);
//        dump($rec);
        //return $record;
        return $rec;

//        return array_filter($record,
//            function ($_, $field) use (&$indice) {
//                foreach ([...$indice['fields'], 'id', 'deleted_at'] as $expectedField) {
//                    if (fnmatch($expectedField, $field)) {
//                        return true;
//                    }
//                }
//
//                return false;
//            },
//            ARRAY_FILTER_USE_BOTH
//        );
    }
}
