<?php
namespace App\Controller;

use App\GraphQL\Schema as CustomSchema;
use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use JsonException;
use RuntimeException;
use Throwable;

class GraphQL {
    /**
     * @throws JsonException
     */
    public static function handle() {
        try {
            $schema = new Schema(
                (new SchemaConfig())
                    ->setQuery(CustomSchema::queryType())
                    ->setMutation(CustomSchema::mutationType())
            );

            $rawInput = file_get_contents('php://input');
            if ($rawInput === false) {
                throw new RuntimeException('Failed to get php://input');
            }

            $input = json_decode($rawInput, true, 512, JSON_THROW_ON_ERROR);
            $query = $input['query'] ?? null;
            $variableValues = $input['variables'] ?? null;

            if (!$query) {
                throw new RuntimeException('No GraphQL query provided');
            }

            $result = GraphQLBase::executeQuery($schema, $query, null, null, $variableValues);
            $output = $result->toArray();
        } catch (Throwable $e) {
            $output = [
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ];
        }

        header('Content-Type: application/json; charset=UTF-8');
        return json_encode($output, JSON_THROW_ON_ERROR);
    }
}
