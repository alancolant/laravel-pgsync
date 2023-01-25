<?php

namespace Alancolant\LaravelPgsync\Types;

class UpdateEvent
{
    public function __construct(
        public string $table,
        public array $record,
        public array $old
    ) {
    }
}
