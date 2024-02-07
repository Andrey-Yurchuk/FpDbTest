<?php

namespace FpDbTest;

use Exception;

class DatabaseTest
{
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function testBuildQuery(): void
    {
        $results = [];

        $results[] = $this->db->buildQuery('SELECT name FROM users WHERE user_id = 1');
        $results[] = $this->db->buildQuery(
            'SELECT * FROM users WHERE name = ? AND block = 0',
            ['Jack']
        );

        $correct = [
            'SELECT name FROM users WHERE user_id = 1',
            'SELECT * FROM users WHERE name = \'Jack\' AND block = 0',
        ];

        foreach ($results as $key => $result) {
            if ($result !== $correct[$key]) {
                throw new Exception('Failure.');
            }
        }
    }
}