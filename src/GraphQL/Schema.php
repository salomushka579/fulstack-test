<?php
namespace App\GraphQL;

use App\GraphQL\Resolvers\ProductResolver;
use App\GraphQL\Resolvers\CategoryResolver;
use App\GraphQL\Resolvers\OrderResolver;
use GraphQL\Type\Definition\ObjectType;

class Schema
{
    public static function queryType(): ObjectType
    {
        return new ObjectType([
            'name' => 'Query',
            'fields' => [
                'products' => ProductResolver::getProductsField(),
                'product' => ProductResolver::getProductField(),
                'categories' => CategoryResolver::getCategoriesField(),
            ],
        ]);
    }

    public static function mutationType(): ObjectType
    {
        return new ObjectType([
            'name' => 'Mutation',
            'fields' => [
                'createOrder' => OrderResolver::getCreateOrderField(),
            ],
        ]);
    }
}
