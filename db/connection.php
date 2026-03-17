<?php

/**
 * Get a PDO instance connected to the SQLite database.
 *
 * The database file lives in the `db/` folder (db/database.sqlite).
 * If it doesn't exist, it will be created automatically.
 *
 * @return PDO
 */
function getDb(): PDO
{
    static $db;
    if ($db) {
        return $db;
    }

    $dbFile = __DIR__ . '/database.sqlite';
    $dsn = 'sqlite:' . $dbFile;

    $db = new PDO($dsn);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $db;
}
