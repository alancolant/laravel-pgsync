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
        $params = [];

        foreach ($this->_getIndicesForTable($event->table) as $indice) {
            $params[] = ['delete' => ['_index' => $indice['index'], '_id' => $event->record['id']]];
        }

        if (! empty($params)) {
            $this->_addBulk($params);
        }

        return true;
    }

    public function handleUpdate(UpdateEvent &$event): bool
    {
        $params = [];

        foreach ($this->_getIndicesForTable($event->table) as $indice) {
            $data = array_diff($event->record, $event->old);
            if (empty($data)) {
                continue;
            }
            $newData = $this->_getRecordFieldsForIndex($event->record, $indice);
            $params[] = ['index' => ['_index' => $indice['index'], '_id' => $event->record['id']]];
            $params[] = $newData;
        }
        if (! empty($params)) {
            $this->_addBulk($params);
        }

        return true;
    }

    public function handleInsert(InsertEvent &$event): bool
    {
        $params = [];

        foreach ($this->_getIndicesForTable($event->table) as $indice) {
            $data = $this->_getRecordFieldsForIndex($event->record, $indice);
            $params[] = ['index' => ['_index' => $indice['index'], '_id' => $data['id']]];
            $params[] = $data;
        }

        if (! empty($params)) {
//            $this->elasticClient->bulk($params);
            $this->_addBulk($params);
        }

        return true;
    }

    private array $bulkParams = ['body' => []];

    private int $bulkBodyLength = 0;

    public function _addBulk(array $body): void
    {
        $this->bulkParams['body'] = array_merge($this->bulkParams['body'], $body);
        $this->bulkBodyLength++;
        if ($this->bulkBodyLength >= 500) {
            $this->_sendBulk();
        }
    }

    public function _sendBulk(): void
    {
        if (empty($this->bulkParams['body'])) {
            return;
        }
        $this->elasticClient->bulk($this->bulkParams);
        $this->bulkParams['body'] = [];
        $this->bulkBodyLength = 0;
    }
}
