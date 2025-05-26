<?php
namespace App\Seed;
require_once __DIR__ . '/../../bootstrap.php';

use App\Database\Connection;
use JsonException;

class Seeder
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance()->getPdo();
    }

    /**
     * @throws JsonException
     */
    public function run(string $jsonPath): void
    {
        $data = json_decode(file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR)['data'];

        // Categories
        $categoryMap = [];
        foreach ($data['categories'] as $cat) {
            $stmt = $this->pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
            $stmt->execute(['name' => $cat['name']]);
            $categoryMap[$cat['name']] = $this->pdo->lastInsertId();
        }

        // Products
        foreach ($data['products'] as $product) {
            $stmt = $this->pdo->prepare("INSERT INTO products (id, name, in_stock, description, category_id, brand)
                VALUES (:id, :name, :in_stock, :description, :category_id, :brand)");
            $stmt->execute([
                'id' => $product['id'],
                'name' => $product['name'],
                'in_stock' => $product['inStock'],
                'description' => $product['description'],
                'category_id' => $categoryMap[$product['category']],
                'brand' => $product['brand']
            ]);

            // Images
            foreach ($product['gallery'] as $img) {
                $this->pdo->prepare("INSERT INTO product_images (product_id, url) VALUES (?, ?)")
                    ->execute([$product['id'], $img]);
            }

            // Prices
            foreach ($product['prices'] as $price) {
                $this->pdo->prepare("INSERT INTO prices (product_id, amount, currency_label, currency_symbol)
                    VALUES (?, ?, ?, ?)")->execute([
                    $product['id'],
                    $price['amount'],
                    $price['currency']['label'],
                    $price['currency']['symbol']
                ]);
            }

            // Attributes
            foreach ($product['attributes'] as $attrSet) {
                $this->pdo->prepare("INSERT INTO attributes (name, type) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)")
                    ->execute([$attrSet['name'], $attrSet['type']]);
                $attributeId = $this->pdo->lastInsertId();

                $this->pdo->prepare("INSERT INTO product_attribute_sets (product_id, attribute_id)
                    VALUES (?, ?)")->execute([$product['id'], $attributeId]);

                foreach ($attrSet['items'] as $item) {
                    $this->pdo->prepare("INSERT INTO attribute_items (attribute_id, value, display_value)
                        VALUES (?, ?, ?)")->execute([
                        $attributeId,
                        $item['value'],
                        $item['displayValue']
                    ]);
                }
            }
        }

        echo "✅ Seeding completed.\n";
    }
}

// Run it
$seeder = new Seeder();
try {
    $seeder->run(__DIR__ . '/../../data.json');
} catch (JsonException $e) {
}
