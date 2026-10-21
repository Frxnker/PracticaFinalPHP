<?php

namespace App\Models;

use App\Core\Database;

class Post extends BaseModel
{
    public function __construct(Database $db)
    {
        parent::__construct($db, 'posts');
    }

    public function search(string $term = ''): ?array
    {
        $sql = "SELECT p.*, u.username
                FROM posts p
                JOIN users u ON u.id = p.user_id";
        $params = [];

        if ($term !== '') {
            $sql .= " WHERE p.title LIKE :title OR p.category LIKE :category";
            $params = [
                'title'    => "%$term%",
                'category' => "%$term%",
            ];
        }

        $sql .= " ORDER BY p.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function findWithAuthor(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT p.*, u.username
             FROM posts p
             JOIN users u ON u.id = p.user_id
             WHERE p.id = :id",
            ['id' => $id]
        );
    }
}