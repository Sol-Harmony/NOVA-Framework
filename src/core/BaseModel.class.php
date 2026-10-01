<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/
class BaseModel extends dbConnect
{
    protected $table;
    protected $primaryKey = 'id';

    public function find($id)
    {
        $connector = $this->connect();
        $sql = "SELECT * FROM " . $this->quoteName($this->table) . " WHERE " . $this->quoteName($this->primaryKey) . " = ?";
        $stmt = $connector->prepare($sql);
        $stmt->execute([$id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            return $this->row($data, true);
        }

        return null;
    }

    public function all()
    {
        $connector = $this->connect();
        $sql = "SELECT * FROM " . $this->quoteName($this->table);
        $stmt = $connector->prepare($sql);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($data) => $this->row($data, true), $rows);
    }

    public function where($conditions)
    {
        if (empty($conditions)) {
            return $this->all();
        }

        $connector = $this->connect();

        $sqlParts = [];
        $values = [];

        foreach ($conditions as $key => $val) {
            $sqlParts[] = $this->quoteName($key) . " = ?";
            $values[] = $val;
        }

        $sql = "SELECT * FROM " . $this->quoteName($this->table) . " WHERE " . implode(" AND ", $sqlParts);
        $stmt = $connector->prepare($sql);
        $stmt->execute($values);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($data) => $this->row($data, true), $rows);
    }

    public function create($data = [])
    {
        return $this->row($data, false);
    }

    //rows loaded from the db ($exists = true) are updated on save(), new rows are inserted
    protected function row($data, $exists)
    {
        return new RowModel($this->table, $data, $this->primaryKey, $exists);
    }
}
