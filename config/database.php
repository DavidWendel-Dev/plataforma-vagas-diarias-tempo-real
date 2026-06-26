<?php
/**
 * Conexão com Banco de Dados usando PDO
 */

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            // Primeiro, conectar sem especificar o banco
            $dsn = sprintf(
                "mysql:host=%s;charset=%s",
                DB_HOST,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Verificar se o banco existe, se não, criar
            $this->pdo->exec(
                "CREATE DATABASE IF NOT EXISTS " . DB_NAME . 
                " CHARACTER SET " . DB_CHARSET . 
                " COLLATE " . DB_CHARSET . "_unicode_ci"
            );
            
            // Selecionar o banco
            $this->pdo->exec("USE " . DB_NAME);
            
            // Verificar se as tabelas existem
            $this->createTablesIfNotExists();
            
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                die("Erro de conexão: " . $e->getMessage());
            }
            die("Erro ao conectar ao banco de dados.");
        }
    }
    
    /**
     * Criar tabelas se não existirem
     */
    private function createTablesIfNotExists() {
        $sqlFile = dirname(__DIR__) . '/database/schema.sql';
        
        if (!file_exists($sqlFile)) {
            return;
        }
        
        // Verificar se a tabela usuarios existe
        $result = $this->pdo->query("SHOW TABLES LIKE 'usuarios'");
        
        if ($result->rowCount() == 0) {
            // Executar arquivo SQL
            $sql = file_get_contents($sqlFile);
            
            // Remover o CREATE DATABASE e USE do início para evitar erros
            $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
            $sql = preg_replace('/USE\s+\w+;/i', '', $sql);
            
            $this->pdo->exec($sql);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    /**
     * Executar query com prepared statements
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                throw $e;
            }
            return false;
        }
    }

    /**
     * Buscar único registro
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }

    /**
     * Buscar múltiplos registros
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Inserir registro
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);

        return $this->pdo->lastInsertId();
    }

    /**
     * Atualizar registro
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setClause);

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);

        return $this->query($sql, $params);
    }

    /**
     * Deletar registro
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params);
    }

    /**
     * Iniciar transação
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * Confirmar transação
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * Reverter transação
     */
    public function rollBack() {
        return $this->pdo->rollBack();
    }

    /**
     * Obter último ID inserido
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}

// Função helper para obter instância do banco
function db() {
    return Database::getInstance();
}
