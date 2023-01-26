<?php

namespace Alancolant\LaravelPgsync\Temp;

use Error;

trait PostgresqlUtils
{
    protected function _getRawJsonbObjectForFields(
        array $fields,
        string $table,
        ?array $additional_columns = []
    ): string {
        $query = 'JSON_BUILD_OBJECT(';

        $query .= implode(',', array_merge(
            //Base fields
            array_map(function ($field) use ($table) {
                if (is_string($field)) {
                    return "'{$field}',\"{$table}\".\"{$field}\"";
                }
                if (is_array($field) && array_key_exists('db_field', $field)) {
                    return "'{$field['es_field']}',\"{$table}\".\"{$field['db_field']}\"";
                }
                if (is_array($field) && array_key_exists('db_query', $field)) {
                    $fieldScript = str_replace('{{prefix}}', "\"{$table}\".", $field['db_query']);

                    return "'{$field['es_field']}',{$fieldScript}";
                }
                throw new Error('Cannot transform this field: '.json_encode($field));
            }, $fields),
            //Additional fields
            array_map(function ($name) use (&$additional_columns) {
                return "'{$name}',{$additional_columns[$name]}";
            }, array_keys($additional_columns))
        ));

        $query .= ')';

        return $query;
    }
}
