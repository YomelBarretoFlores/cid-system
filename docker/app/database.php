<?php

declare(strict_types=1);

// Container start checks without booting Laravel: "wait" until PostgreSQL accepts connections, "empty" exits 0 when no user exists yet.
$conectar = fn (): PDO => new PDO(
    sprintf('pgsql:host=%s;port=%s;dbname=%s', getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_PORT') ?: '5432', getenv('DB_DATABASE') ?: 'ciln'),
    getenv('DB_USERNAME') ?: null,
    getenv('DB_PASSWORD') ?: null,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3],
);

if (($argv[1] ?? '') === 'empty') {
    exit((int) $conectar()->query('select count(*) from users')->fetchColumn() === 0 ? 0 : 1);
}

for ($intento = 1; $intento <= 30; $intento++) {
    try {
        $conectar();
        exit(0);
    } catch (PDOException) {
        fwrite(STDERR, "Waiting for PostgreSQL ({$intento}/30)\n");
        sleep(2);
    }
}

fwrite(STDERR, "PostgreSQL did not accept connections\n");
exit(1);
