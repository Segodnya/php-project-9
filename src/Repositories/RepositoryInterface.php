<?php

namespace App\Repositories;

interface RepositoryInterface
{
    public function findById(int $id);
    public function findAll();
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
} 