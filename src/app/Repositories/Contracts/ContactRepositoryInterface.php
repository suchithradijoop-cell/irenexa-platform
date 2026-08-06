<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Contact;
use Illuminate\Support\Collection;

interface ContactRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Contact;

    public function create(array $data): Contact;
}
