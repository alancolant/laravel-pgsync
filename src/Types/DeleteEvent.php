<?php

namespace Alancolant\LaravelPgsync\Types;

class DeleteEvent
{
    public function __construct(
        public string $table,
        public array $record,
    ) {
    }
}
