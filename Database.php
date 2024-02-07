<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * @inheritDoc
     */
    public function buildQuery(string $query, array $args = []): string
    {
        $pos = 0;
        $params = [];

        while (($pos = strpos($query, '?', $pos)) !== false) {
            if (isset($query[$pos + 1]) && $query[$pos + 1] === '{') {
                $endPos = strpos($query, '}', $pos);
                if ($endPos === false) {
                    throw new Exception('Invalid template: unclosed curly bracket');
                }

                $skipContent = substr($query, $pos, $endPos - $pos + 1);

                if (strpos($skipContent, '?') !== false || strpos($skipContent, '#') !== false) {
                    return '';
                } else {
                    $query = str_replace($skipContent, '', $query);
                }
            } else {
                $params[] = $pos;
                $pos++;
            }
        }

        $argsCurrentIndex = 0;

        foreach ($params as $param) {
            $specifier = $this->getNextSpecifier($query, $param);
            $arg = $args[$argsCurrentIndex] ?? null;
            $replacement = $this->formatArgument($arg, $specifier);
            $query = substr_replace($query, $replacement, $param, 1);
            $argsCurrentIndex++;
        }

        return $query;
    }

    /**
     * Returns the specifier for the argument in the query.
     *
     * @param string $query    The original query string.
     * @param int    $position The position in the query string where the '?' sign is located.
     *
     * @return string|null The specifier for formatting the argument or null if the specifier is not found.
     */
    private function getNextSpecifier(string $query, int $position): ?string
    {
        $nextChar = $query[$position + 1] ?? null;
        $specifier = match ($nextChar) {
            'd', 'f', 'a', '#' => $nextChar,
            default => null,
        };

        return $specifier;
    }

    /**
     * Formats the given argument according to the specified specifier.
     *
     * @param mixed        $arg       The argument value requiring formatting.
     * @param string|null  $specifier The formatting specifier (optional).
     *
     * @return string The formatted argument value as a string.
     *
     * @throws Exception If the argument type is unsupported or the format is invalid.
     * @throws Exception If the argument is invalid: expected array.
     */
    private function formatArgument(mixed $arg, ?string $specifier): string
    {
        if ($specifier === 'd') {
            return (int)$arg;
        } elseif ($specifier === 'f') {
            return (float)$arg;
        } elseif ($specifier === 'a') {
            if (!is_array($arg)) {
                throw new Exception('Invalid argument: expected array');
            }
            return '(' . implode(', ', array_map(fn($val) => $this->mysqli->real_escape_string((string)$val), $arg)) . ')';
        } elseif ($specifier === '#') {
            if (is_array($arg)) {
                return '(' . implode(', ', array_map(fn($id) => $this->mysqli->real_escape_string((string)$id), $arg)) . ')';
            } else {
                return $this->mysqli->real_escape_string((string)$arg);
            }
        } else {
            if ($arg === null) {
                return 'NULL';
            } elseif (is_string($arg)) {
                return "'" . $this->mysqli->real_escape_string($arg) . "'";
            } elseif (is_int($arg) || is_float($arg) || is_bool($arg)) {
                return $arg;
            } else {
                throw new Exception('Unsupported argument type');
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function skip(): string
    {
        return '__SKIP__';
    }
}