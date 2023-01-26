<?php

namespace Alancolant\LaravelPgsync\Commands;

use Alancolant\LaravelPgsync\Publishers\ElasticsearchPublisher;
use Alancolant\LaravelPgsync\Utils\Pgsync;
use Illuminate\Console\Command;

class Index extends Command
{
    protected $signature = 'pgsync:index';

    protected $description = 'Create and populate every index';

    public function handle(): int
    {
        $pgsync = new Pgsync(config('pgsync'));
        $publisher = new ElasticsearchPublisher();
        $stats = [];

        foreach ($pgsync->getIndices() as $index) {
            $i = 0;
            $this->comment("Start indexing {$index->index}");

            $time_start = microtime(true);

            foreach ($index->getAllDocuments() as $document) {
                $i++;
                $publisher->_addBulk([
                    ['index' => ['_index' => $index->index, '_id' => $document['id']]],
                    $document,
                ]);
            }
            $publisher->_sendBulk();

            $time_end = microtime(true);
            $execution_time = ($time_end - $time_start);
            $execMean = $execution_time * 1000 / $i;
            $stats[] = [$index->index, $execution_time, $execMean];
        }
        $this->table(['Index', 'Duration (sec)', 'Mean (ms/doc)'], $stats, 'box-double');

        return self::SUCCESS;
    }
}
