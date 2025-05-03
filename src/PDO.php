<?php

namespace App;

/**
 * This class exists solely to fix a namespace resolution issue
 * where the container is looking for App\PDO instead of global PDO.
 * 
 * PDO is a final class in PHP, so we can't actually extend it.
 * Instead, we'll implement the same interface and proxy all calls to a real PDO instance.
 */
class PDO
{
    private \PDO $pdo;
    
    /**
     * Constructor accepts the same parameters as PDO
     */
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        $this->pdo = new \PDO($dsn, $username, $password, $options);
    }
    
    /**
     * Allow an existing PDO instance to be used
     */
    public static function fromPdo(\PDO $pdo): self
    {
        $instance = new self('sqlite::memory:');
        $instance->pdo = $pdo;
        return $instance;
    }
    
    /**
     * Forward all method calls to the underlying PDO instance
     */
    public function __call(string $name, array $arguments)
    {
        return $this->pdo->$name(...$arguments);
    }
    
    // Include key PDO methods explicitly for better IDE support
    
    public function prepare($statement, $options = [])
    {
        return $this->pdo->prepare($statement, $options);
    }
    
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }
    
    public function commit()
    {
        return $this->pdo->commit();
    }
    
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }
    
    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }
    
    public function getAttribute($attribute)
    {
        return $this->pdo->getAttribute($attribute);
    }
    
    public function setAttribute($attribute, $value)
    {
        return $this->pdo->setAttribute($attribute, $value);
    }
    
    public function exec($statement)
    {
        return $this->pdo->exec($statement);
    }
    
    public function query($statement, $mode = \PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = null, array $ctorargs = [])
    {
        return $this->pdo->query($statement, $mode, $arg3, $ctorargs);
    }
    
    public function lastInsertId($name = null)
    {
        return $this->pdo->lastInsertId($name);
    }
    
    public function errorCode()
    {
        return $this->pdo->errorCode();
    }
    
    public function errorInfo()
    {
        return $this->pdo->errorInfo();
    }
    
    public function quote($string, $parameter_type = \PDO::PARAM_STR)
    {
        return $this->pdo->quote($string, $parameter_type);
    }
} 