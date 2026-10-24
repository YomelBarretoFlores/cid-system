<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\QueryException;
use PDOException;

final class ErroresDePostgres
{
    public static function conCodigo(string $sqlstate, string $mensaje): QueryException
    {
        // PDOException carries PostgreSQL's SQLSTATE as a string code, which its constructor cannot set.
        $pdo = new class("SQLSTATE[{$sqlstate}]: {$mensaje}", $sqlstate) extends PDOException
        {
            public function __construct(string $mensaje, string $sqlstate)
            {
                parent::__construct($mensaje);
                $this->code = $sqlstate;
            }
        };

        return new QueryException('pgsql', 'select 1', [], $pdo);
    }
}
