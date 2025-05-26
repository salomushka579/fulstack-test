<?php
namespace App\GraphQL\Resolvers;

use App\Database\Connection;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

class ProductResolver
{
    public static function getProductsField(): array
    {
        return [
            'type' => Type::listOf(self::getProductType()),
            'resolve' => function () {
                $pdo = Connection::getInstance()->getPdo();

                $stmt = $pdo->query("
                    SELECT p.id, p.name, p.description, p.in_stock, p.brand,
                           pr.amount as price
                    FROM products p
                    LEFT JOIN prices pr ON pr.product_id = p.id
                    GROUP BY p.id
                ");

                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                return array_map(static function ($row) {
                    return [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'description' => $row['description'],
                        'inStock' => (bool) $row['in_stock'],
                        'brand' => $row['brand'],
                        'price' => (float) $row['price'],
                    ];
                }, $rows);
            },
        ];
    }

    private static function getProductType(): ObjectType
    {
        return new ObjectType([
            'name' => 'Product',
            'fields' => [
                'id' => Type::nonNull(Type::string()),
                'name' => Type::nonNull(Type::string()),
                'description' => Type::string(),
                'inStock' => Type::nonNull(Type::boolean()),
                'brand' => Type::string(),
                'price' => Type::float(),

            ],
        ]);
    }
}
