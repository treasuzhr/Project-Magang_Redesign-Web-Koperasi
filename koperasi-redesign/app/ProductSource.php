<?php

namespace App;

use Illuminate\Support\Collection;

interface ProductSource
{
    /** @return Collection<int, array{id: string, name: string, price: int, category: ?string, image: ?string}> */
    public function all(): Collection;

    public function isExample(): bool;
}
