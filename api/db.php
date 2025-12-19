<?php
// api/db.php

class DB {
    private static $pdo = null;

    public static function connect() {
        if (self::$pdo == null) {
            try {
                // Ruta absoluta a la base de datos
                $db_path = __DIR__ . '/../database/database.sqlite';
                self::$pdo = new PDO("sqlite:" . $db_path);
                
                // Configurar errores y modo de fetch
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                // Habilitar claves foráneas
                self::$pdo->exec("PRAGMA foreign_keys = ON;");
            } catch (PDOException $e) {
                die("Error de conexión a la base de datos: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }

    public static function query($sql, $params = []) {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
