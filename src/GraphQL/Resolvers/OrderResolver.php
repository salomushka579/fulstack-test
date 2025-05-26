<?php
namespace App\GraphQL\Resolvers;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

class OrderResolver
{
    public static function getCreateOrderField(): array
    {
        return [
            'type' => Type::string(),
            'args' => [
                'input' => Type::nonNull(Type::string()),
            ],
            'resolve' => function ($_, $args) {
                return 'Order placed successfully with payload: ' . $args['input'];
            },
        ];
    }
}
