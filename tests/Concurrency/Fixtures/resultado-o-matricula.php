<?php

declare(strict_types=1);

[$self, $inicio, $tipo] = $argv;
$argv = [$self, $inicio, ...array_slice($argv, 3)];
require __DIR__.($tipo === 'r' ? '/registrar-resultado.php' : '/matricular.php');
