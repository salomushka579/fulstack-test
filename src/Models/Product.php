<?php
namespace App\Models;

abstract class Product
{
    public string $id;
    public string $name;
    public bool $inStock;
    public string $description;
    public string $brand;
    public array $gallery;
    public array $attributes;
    public array $prices;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->inStock = $data['inStock'];
        $this->description = $data['description'];
        $this->brand = $data['brand'];
        $this->gallery = $data['gallery'] ?? [];
        $this->attributes = $data['attributes'] ?? [];
        $this->prices = $data['prices'] ?? [];
    }

    abstract public function getType(): string;
}
