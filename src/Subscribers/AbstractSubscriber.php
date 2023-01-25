<?php

namespace Alancolant\LaravelPgsync\Subscribers;

use Alancolant\LaravelPgsync\Types\DeleteEvent;
use Alancolant\LaravelPgsync\Types\InsertEvent;
use Alancolant\LaravelPgsync\Types\UpdateEvent;

abstract class AbstractSubscriber
{
    public function __construct()
    {
    }

    abstract public function initialize(): bool;

    public function startListening(): void
    {
    }

    abstract public function getNextEvent(): DeleteEvent|InsertEvent|UpdateEvent;

    public function stopListening(): void
    {
    }

    protected function _getTables(): array
    {
        return array_unique(data_get(config('pgsync.indices'), '*.table'));

//        return collect(Schema::getAllTables())
//            ->map(fn($i) => $i->tablename)
//            ->filter(function ($table) {
//                foreach (config('pgsync.tables.includes', []) as $include) {
//                    if (!fnmatch($include, $table)) {
//                        return false;
//                    }
//                }
//                foreach (config('pgsync.tables.excludes', []) as $exclude) {
//                    if (fnmatch($exclude, $table)) {
//                        return false;
//                    }
//                }
//
//                return true;
//            })->values()->toArray();
    }
}
