<?php

namespace App\GraphQL\Resolvers;

use App\Database\Connection;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

class CategoryResolver {
    public static function getCategoriesField(): array {
        return [
            'type' => Type::listOf(self::getCategoryType()),
            'resolve' => function () {
                $pdo = Connection::getInstance()->getPdo();
                $stmt = $pdo->query("SELECT id, name FROM categories");
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                return array_map(fn($row) => ['id' => (int)$row['id'], 'name' => $row['name']], $rows);
            },
        ];
    }

    private static function getCategoryType(): ObjectType {
        return new ObjectType([
            'name' => 'Category',
            'fields' => [
                'id' => Type::nonNull(Type::int()),
                'name' => Type::nonNull(Type::string()),
            ],
        ]);
    }
}
