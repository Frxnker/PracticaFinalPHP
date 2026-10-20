<?php

namespace App\Models;

use App\Core\Database;

abstract class BaseModel
{
    protected Database $db;
    protected string $table;

    public function __construct(Database $db, string $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    public function getAll(): ?array
    {
        return $this->db->fetchAll("SELECT * FROM {$this->table}");
    }

    public function getById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE id = :id",
            ['id' => $id]
        );
    }

    public function delete(int $id): void
    {
        $this->db->query(
            "DELETE FROM {$this->table} WHERE id = :id",
            ['id' => $id]
        );
    }

    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $this->db->query(
            "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)",
            $data
        );

        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "$column = :$column";
        }
        $set = implode(', ', $set);

        $data['id'] = $id;

        $this->db->query(
            "UPDATE {$this->table} SET $set WHERE id = :id",
            $data
        );
    }
}