<?php
namespace App\Models;

class AttributeItem
{
    public int $id;
    public string $value;
    public string $displayValue;

    public function __construct(int $id, string $value, string $displayValue)
    {
        $this->id = $id;
        $this->value = $value;
        $this->displayValue = $displayValue;
    }
}
