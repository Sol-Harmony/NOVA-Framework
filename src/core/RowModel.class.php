<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class RowModel extends dbConnect
{
    protected $table;
    protected $primaryKey;
    protected $attributes = [];
    protected $exists;      //true when the row is in the db: save() updates it, otherwise save() inserts it

    public function __construct($table, $data = [], $primaryKey = 'id', $exists = false)
    {
        $this->table = $table;
        $this->attributes = $data;
        $this->primaryKey = $primaryKey;
        $this->exists = $exists;
    }

    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function save()
    {
        $connector = $this->connect();
        $table = $this->quoteName($this->table);
        $primaryKey = $this->quoteName($this->primaryKey);

        if ($this->exists) {
            // UPDATE
            if (!isset($this->attributes[$this->primaryKey])) {
                throw new Exception("Cannot update row without primary key.");
            }
            $id = $this->attributes[$this->primaryKey];
            $setParts = [];
            $values = [];

            foreach ($this->attributes as $key => $val) {
                if ($key !== $this->primaryKey) {
                    $setParts[] = $this->quoteName($key) . " = ?";
                    $values[] = $val;
                }
            }

            if ($setParts) {
                $sql = "UPDATE $table SET " . implode(', ', $setParts) . " WHERE $primaryKey = ?";
                $stmt = $connector->prepare($sql);
                $values[] = $id;
                $stmt->execute($values);
            }
        } else {
            // INSERT
            $columns = implode(', ', array_map(fn($key) => $this->quoteName($key), array_keys($this->attributes)));
            $placeholders = implode(', ', array_fill(0, count($this->attributes), '?'));

            $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            $stmt = $connector->prepare($sql);
            $stmt->execute(array_values($this->attributes));

            if (!isset($this->attributes[$this->primaryKey])) {
                $this->attributes[$this->primaryKey] = $connector->lastInsertId();
            }
            $this->exists = true;
        }

        // Return fresh row from DB
        $sql = "SELECT * FROM $table WHERE $primaryKey = ?";
        $stmt = $connector->prepare($sql);
        $stmt->execute([$this->attributes[$this->primaryKey]]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data === false) {
            throw new Exception("Row {$this->attributes[$this->primaryKey]} not found in {$this->table}.");
        }
        $this->attributes = $data;
        return $this;
    }

    public function delete()
    {
        if (!isset($this->attributes[$this->primaryKey])) {
            throw new Exception("Cannot delete row without primary key.");
        }

        $connector = $this->connect();
        $sql = "DELETE FROM " . $this->quoteName($this->table) . " WHERE " . $this->quoteName($this->primaryKey) . " = ?";
        $stmt = $connector->prepare($sql);
        $stmt->execute([$this->attributes[$this->primaryKey]]);
        $this->exists = false;
    }

    public function toArray()
    {
        return $this->attributes;
    }
}
