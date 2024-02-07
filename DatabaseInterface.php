<?php

namespace FpDbTest;

use Exception;

interface DatabaseInterface
{
    /**
     * Builds a SQL query from the template and parameter values.
     *
     * @param string $query The SQL query template with placeholders.
     * @param array  $args  An array of parameter values to replace placeholders (default: empty array).
     *
     * @return string The constructed SQL query with replaced placeholders.
     *
     * @throws Exception If the template contains unclosed curly brackets.
     */
    public function buildQuery(string $query, array $args = []): string;

    /**
     * Returns a special value to be used for skipping conditional blocks in the SQL query construction.
     *
     * @return string The special value 'SKIP'
     */
    public function skip(): string;
}
