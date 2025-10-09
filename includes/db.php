<?php
/**
 * Database Connection (PDO)
 */

// Prevent direct access
if (!defined('APP_INIT')) {
    die('Direct access not permitted');
}

/**
 * Get PDO database connection
 * @return PDO|null
 */
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        } catch (PDOException $e) {
            // Log error without exposing details
            error_log('Database connection failed: ' . $e->getMessage());

            if (APP_ENV === 'development') {
                die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
            } else {
                die('Database connection failed. Please contact support.');
            }
        }
    }

    return $pdo;
}

/**
 * Execute a prepared statement
 * @param string $sql
 * @param array $params
 * @return PDOStatement|false
 */
function dbQuery($sql, $params = []) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log('Query failed: ' . $e->getMessage());
        error_log('SQL: ' . $sql);
        error_log('Params: ' . print_r($params, true));

        // In development, show the error
        if (defined('APP_ENV') && APP_ENV === 'development') {
            echo "<div style='background:#fee; color:#c33; padding:15px; margin:10px; border:1px solid #fcc;'>";
            echo "<strong>Database Query Error:</strong><br>";
            echo htmlspecialchars($e->getMessage()) . "<br><br>";
            echo "<strong>SQL:</strong> " . htmlspecialchars($sql) . "<br>";
            echo "<strong>Params:</strong> " . htmlspecialchars(print_r($params, true));
            echo "</div>";
        }

        return false;
    }
}

/**
 * Fetch single row
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function dbFetchOne($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

/**
 * Fetch all rows
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function dbFetchAll($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : false;
}

?>
