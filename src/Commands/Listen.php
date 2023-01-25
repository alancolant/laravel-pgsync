<?php

namespace Alancolant\LaravelPgsync\Commands;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Listen extends Command
{
    protected $signature = 'pgsync:listen';

    protected $description = 'Listen Postgresql trigger to handle change';

    private bool $running = true;

    public function handle(): int
    {
        set_time_limit(0);
        $this->trap([SIGTERM, SIGQUIT, SIGINT], function () {
            $this->running = false;
        });

        $conn = DB::connection(config('pgsync.connection', config('database.default')));
        if ($conn->getDriverName() !== 'pgsql') {
            throw new \Error("Driver {$conn->getDriverName()} not supported!");
        }
        $pdo = $conn->getPdo();
        $pdo->exec('LISTEN pgsync_event');
        while ($this->running) {
            while ($result = $pdo->pgsqlGetNotify(\PDO::FETCH_ASSOC, 1000 * 10)) {
                $payload = json_decode($result['payload'], true);
                switch ($payload['action']) {
                    case 'insert':
                        $this->handleInsert($payload['identity'], $payload['record']);
                        break;
                    case 'update':
                        $this->handleUpdate($payload['identity'], $payload['record'], $payload['old']);
                        break;
                    case 'delete':
                        $this->handleDelete($payload['identity'], $payload['record']);
                        break;
                }
            }
        }

        return Command::SUCCESS;
    }

    protected function handleDelete(string $table, array $record)
    {
        $params = ['body' => []];

        foreach ($this->_getIndicesForTable($table) as $indiceName => $indice) {
            $data = $this->_getRecordFieldsForIndex($record, $indice);
            $params['body'][] = ['delete' => ['_index' => $indiceName, '_id' => $data['id']]];
        }

        if (! empty($params['body'])) {
            $this->_getElasticClient()->bulk($params);
        }
    }

    protected function handleUpdate(string $table, array $record, array $old)
    {
        $params = ['body' => []];

        foreach ($this->_getIndicesForTable($table) as $indiceName => $indice) {
            //Custom action if soft delete enabled
            if (
                config('pgsync.action_on_soft_delete') === 'delete'
                && array_key_exists('deleted_at', $record)
            ) {
                if ($record['deleted_at'] !== null && $old['deleted_at'] === null) {
                    $this->handleDelete($table, $old);

                    continue;
                }
                if ($record['deleted_at'] === null && $old['deleted_at'] !== null) {
                    $this->handleInsert($table, $record);

                    continue;
                }
                if ($record['deleted_at'] !== null && $old['deleted_at'] !== null) {
                    continue;
                }
            }
            $oldData = $this->_getRecordFieldsForIndex($old, $indice);
            $newData = $this->_getRecordFieldsForIndex($record, $indice);
            $data = array_diff($newData, $oldData);
            if (empty($data)) {
                continue;
            }
            $params['body'][] = ['update' => ['_index' => $indiceName, '_id' => $record['id']]];
            $params['body'][] = ['doc' => $newData];
        }
        if (! empty($params['body'])) {
            $this->_getElasticClient()->bulk($params);
        }
    }

    protected function handleInsert(string $table, array $record)
    {
        $params = ['body' => []];

        foreach ($this->_getIndicesForTable($table) as $indiceName => $indice) {
            $data = $this->_getRecordFieldsForIndex($record, $indice);
            $params['body'][] = ['index' => ['_index' => $indiceName, '_id' => $data['id']]];
            $params['body'][] = $data;
        }

        if (! empty($params['body'])) {
            $this->_getElasticClient()->bulk($params);
        }
    }

    private function _getElasticClient(): Client
    {
        return ClientBuilder::create()
            ->setHosts(config('pgsync.output.elasticsearch.hosts'))
            ->build();
    }

    private function _getIndicesForTable(string &$table): array
    {
        return collect(config('pgsync.indices'))->where('table', $table)->toArray();
    }

    private function _getRecordFieldsForIndex(array $record, array $indice): array
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
