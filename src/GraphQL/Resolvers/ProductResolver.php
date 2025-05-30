<?php

namespace App\GraphQL\Resolvers;

use App\Database\Connection;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ProductResolver {
    private static ?ObjectType $productType = null;
    private static ?ObjectType $priceType = null;
    private static ?ObjectType $categoryType = null;
    private static ?ObjectType $attributeType = null;
    private static ?ObjectType $attributeItemType = null;
    public static function getProductsField(): array {
        return [
            'type' => Type::listOf(self::getProductType()),
            'resolve' => fn () => self::fetchAllProducts(),
        ];
    }

    public static function getProductField(): array {
        return [
            'type' => self::getProductType(),
            'args' => [
                'id' => Type::nonNull(Type::string()),
            ],
            'resolve' => fn ($root, $args) => self::fetchProductById($args['id']),
        ];
    }

    private static function fetchAllProducts(): array {
        $pdo = Connection::getInstance()->getPdo();
        $stmt = $pdo->query("SELECT * FROM products");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map([self::class, 'buildProduct'], $rows);
    }

    private static function fetchProductById(string $id): ?array {
        $pdo = Connection::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? self::buildProduct($row) : null;
    }

    private static function buildProduct(array $row): array {
        $pdo = Connection::getInstance()->getPdo();

        // Prices
        $stmt = $pdo->prepare("SELECT amount, currency_label, currency_symbol FROM prices WHERE product_id = :id");
        $stmt->execute(['id' => $row['id']]);
        $prices = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Gallery
        $stmt = $pdo->prepare("SELECT url FROM product_images WHERE product_id = :id");
        $stmt->execute(['id' => $row['id']]);
        $gallery = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'url');

        // Category
        $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = :id");
        $stmt->execute(['id' => $row['category_id']]);
        $category = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Attributes
        $stmt = $pdo->prepare("
            SELECT a.id, a.name, a.type
            FROM attributes a
            JOIN product_attribute_sets pas ON pas.attribute_id = a.id
            WHERE pas.product_id = :product_id
        ");
        $stmt->execute(['product_id' => $row['id']]);
        $attributes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($attributes as &$attr) {
            $stmt = $pdo->prepare("SELECT value, display_value FROM attribute_items WHERE attribute_id = :attr_id");
            $stmt->execute(['attr_id' => $attr['id']]);
            $attr['items'] = array_map(fn($i) => [
                'value' => $i['value'],
                'displayValue' => $i['display_value'],
            ], $stmt->fetchAll(\PDO::FETCH_ASSOC));
        }

        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'inStock' => (bool)$row['in_stock'],
            'brand' => $row['brand'],
            'gallery' => $gallery,
            'prices' => $prices,
            'category' => $category ? ['name' => $category['name']] : null,
            'attributes' => $attributes,
        ];
    }

    private static function getProductType(): ObjectType {
        if (self::$productType === null) {
            self::$productType = new ObjectType([
                'name' => 'Product',
                'fields' => function () {
                    return [
                        'id' => Type::nonNull(Type::string()),
                        'name' => Type::nonNull(Type::string()),
                        'description' => Type::string(),
                        'inStock' => Type::nonNull(Type::boolean()),
                        'brand' => Type::string(),
                        'gallery' => Type::listOf(Type::string()),
                        'prices' => Type::listOf(self::getPriceType()),
                        'category' => self::getCategorySubType(),
                        'attributes' => Type::listOf(self::getAttributeType()),
                    ];
                }
            ]);
        }
        return self::$productType;
    }

    private static function getPriceType(): ObjectType {
         if (self::$priceType === null) {
             self::$priceType = new ObjectType([
                 'name' => 'Price',
                 'fields' => function () {
                     return [
                             'amount' => Type::float(),
                             'currency_label' => Type::string(),
                             'currency_symbol' => Type::string(),
                     ];
                 }
             ]);
         }
        return self::$priceType;
    }

    private static function getCategorySubType(): ObjectType {
        if (self::$categoryType === null) {
            self::$categoryType = new ObjectType([
                'name' => 'ProductCategory',
                'fields' => function () {
                    return [
                            'name' => Type::string(),
                    ];
                }
            ]);
        }
        return self::$categoryType;
    }

    private static function getAttributeType(): ObjectType {
        if (self::$attributeType === null) {
            self::$attributeType = new ObjectType([
                'name' => 'AttributeSet',
                'fields' => function () {
                    return [
                        'name' => Type::string(),
                        'type' => Type::string(),
                        'items' => Type::listOf(self::getAttributeItemType()),
                    ];
                }
            ]);
        }
        return self::$attributeType;
    }

    private static function getAttributeItemType(): ObjectType {
        if (self::$attributeItemType === null) {
            self::$attributeItemType = new ObjectType([
                'name' => 'AttributeItem',
                'fields' => function () {
                    return [
                        'value' => Type::string(),
                        'displayValue' => Type::string(),
                    ];
                }
            ]);
        }
        return self::$attributeItemType;
    }
}

