<?php

namespace App\Models;

use App\Core\Database;

class Comment extends BaseModel
{
    public function __construct(Database $db)
    {
        parent::__construct($db, 'comments');
    }

    public function findByPost(int $postId): ?array
    {
        return $this->db->fetchAll(
            "SELECT c.*, u.username
             FROM comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.post_id = :post_id
             ORDER BY c.created_at ASC, c.id ASC",
            ['post_id' => $postId]
        );
    }
}