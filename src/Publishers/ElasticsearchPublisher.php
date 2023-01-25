<?php

namespace Alancolant\LaravelPgsync\Publishers;

use Alancolant\LaravelPgsync\Types\DeleteEvent;
use Alancolant\LaravelPgsync\Types\InsertEvent;
use Alancolant\LaravelPgsync\Types\UpdateEvent;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchPublisher extends AbstractPublisher
{
    private Client $elasticClient;

    public function __construct()
    {
        parent::__construct();
        $this->elasticClient = ClientBuilder::create()
            ->setHosts(config('pgsync.output.elasticsearch.hosts'))
            ->build();
    }

    public function handleDelete(DeleteEvent &$event): bool
    {
        $params = ['body' => []];

        foreach ($this->_getIndicesForTable($event->table) as $indiceName => $indice) {
            $data = $this->_getRecordFieldsForIndex($event->record, $indice);
            $params['body'][] = ['delete' => ['_index' => $indiceName, '_id' => $data['id']]];
        }

        if (! empty($params['body'])) {
            $this->elasticClient->bulk($params);
        }

        return true;
    }

    public function handleUpdate(UpdateEvent &$event): bool
    {
        $params = ['body' => []];

        foreach ($this->_getIndicesForTable($event->table) as $indiceName => $indice) {
            $oldData = $this->_getRecordFieldsForIndex($event->old, $indice);
            $newData = $this->_getRecordFieldsForIndex($event->record, $indice);
            $data = array_diff($newData, $oldData);
            if (empty($data)) {
                continue;
            }
            $params['body'][] = ['update' => ['_index' => $indiceName, '_id' => $event->record['id']]];
            $params['body'][] = ['doc' => $newData];
        }
        if (! empty($params['body'])) {
            $this->elasticClient->bulk($params);
        }

        return true;
    }

    public function handleInsert(InsertEvent &$event): bool
    {
        $params = ['body' => []];

        foreach ($this->_getIndicesForTable($event->table) as $indiceName => $indice) {
            $data = $this->_getRecordFieldsForIndex($event->record, $indice);
            $params['body'][] = ['index' => ['_index' => $indiceName, '_id' => $data['id']]];
            $params['body'][] = $data;
        }

        if (! empty($params['body'])) {
            $this->elasticClient->bulk($params);
        }

        return true;
    }
}
