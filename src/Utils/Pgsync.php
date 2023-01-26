<?php

namespace Alancolant\LaravelPgsync\Utils;

use Alancolant\LaravelPgsync\Temp\AbstractIndex;
use Alancolant\LaravelPgsync\Temp\PostgresqlIndex;

class Pgsync
{
    /**
     * @var AbstractIndex[]
     */
    private array $indices;

    public function __construct(array $config)
    {
        $this->_loadConfig($config);
    }

    protected function _loadConfig(array $config): void
    {
        $this->indices = array_map(fn ($index) => new PostgresqlIndex($index), $config['indices'] ?? []);
    }

    public function getIndices(): array
    {
        return $this->indices;
    }
}
