<?php
namespace App\Models;

class Attribute
{
    public int $id;
    public string $name;
    public string $type;
    public array $items;

    public function __construct(int $id, string $name, string $type, array $items)
    {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->items = $items;
    }
}
