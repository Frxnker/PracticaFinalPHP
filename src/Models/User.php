<?php

namespace App\Models;

use App\Core\Database;

class User extends BaseModel
{
    public function __construct(Database $db)
    {
        parent::__construct($db, 'users');
    }

    public function findByEmail(string $email, string $password): ?int
    {
        $user = $this->db->fetchOne(
            "SELECT id, password FROM {$this->table} WHERE email = :email",
            ['email' => $email]
        );

        if ($user && password_verify($password, $user['password'])) {
            return (int) $user['id'];
        }

        return null;
    }
}