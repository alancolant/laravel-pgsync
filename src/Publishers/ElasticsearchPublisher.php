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
            $oldData = $this->_getRecordFieldsForIndex($event->old, $indice);
            $newData = $this->_getRecordFieldsForIndex($event->record, $indice);
            $data = array_diff($newData, $oldData);
            if (empty($data)) {
                continue;
            }
            $params[] = ['update' => ['_index' => $indice['index'], '_id' => $event->record['id']]];
            $params[] = ['doc' => $newData];
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

    private function _addBulk(array $body)
    {
        $this->bulkBodyLength++;
        echo $this->bulkBodyLength."\n";
        $this->bulkParams['body'] = array_merge($this->bulkParams['body'], $body);
        if ($this->bulkBodyLength >= 1) {
            $this->_sendBulk();
        }
    }

    private function _sendBulk()
    {
        $this->elasticClient->bulk($this->bulkParams);
        $this->bulkParams['body'] = [];
        $this->bulkBodyLength = 0;
    }
}
