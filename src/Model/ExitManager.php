<?php

/* creation of the ExitManager class to manage the connection to the database */
namespace App\Model;

class ExitManager extends AbstractManager
{
    public const TABLE = '`exit`';

    /**
     * Get all row exit with type_jump from database.
     */
    public function selectTypeJumpByExitId(int $id): array|false
    {
        // prepared request
        $statement = $this->pdo->prepare("SELECT tj.name FROM " . self::TABLE . " e 
        INNER JOIN exit_has_type_jump ehtj ON ehtj.id_exit = e.id
        INNER JOIN type_jump tj ON tj.id = ehtj.id_type_jump
        WHERE e.id=:id");
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Insert new exit in database
     */
    public function insert(array $exit): int
    {
        $statement = $this->pdo->prepare("INSERT INTO " . self::TABLE . " (`title`) VALUES (:title)");
        $statement->bindValue('title', $exit['title'], \PDO::PARAM_STR);

        $statement->execute();
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update exit in database
     */
    public function update(array $exit): bool
    {
        $statement = $this->pdo->prepare("UPDATE " . self::TABLE . " SET `title` = :title WHERE id=:id");
        $statement->bindValue('id', $exit['id'], \PDO::PARAM_INT);
        $statement->bindValue('title', $exit['title'], \PDO::PARAM_STR);

        return $statement->execute();
    }

        /**
     * Delete row form an ID
     */
    public function hide(int $id): void
    {
        $statement = $this->pdo->prepare("UPDATE " . static::TABLE . " SET `active` = 0 WHERE id=:id");
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        $statement->execute();
    }
}
