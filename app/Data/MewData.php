<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class MewData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public int $capacity,
        public bool $is_available,
    ) {}
}
