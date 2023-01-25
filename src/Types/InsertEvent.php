<?php

namespace Alancolant\LaravelPgsync\Types;

class InsertEvent
{
    public function __construct(
        public string $table,
        public array $record
    ) {
    }
}
