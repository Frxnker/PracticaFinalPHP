<?php

namespace App\Models;

use App\Core\Database;

class User extends BaseModel
{
    public function __construct(Database $db)
    {
        parent::__construct($db, 'users');
    }
}