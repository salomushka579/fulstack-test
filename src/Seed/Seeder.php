<?php
namespace App\Seed;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../bootstrap.php';

use App\Database\Connection;
use JsonException;
use PDO;

class Seeder
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance()->getPdo();
    }

    /**
     * Create tables if they do not exist
     */
    private function createTablesIfNotExists(): void
    {
        $sql = [
            "CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS products (
                id VARCHAR(36) PRIMARY KEY,
                name VARCHAR(255),
                in_stock BOOLEAN,
                description TEXT,
                category_id INT,
                brand VARCHAR(255),
                FOREIGN KEY (category_id) REFERENCES categories(id)
            )",
            "CREATE TABLE IF NOT EXISTS product_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id VARCHAR(36),
                url TEXT,
                FOREIGN KEY (product_id) REFERENCES products(id)
            )",
            "CREATE TABLE IF NOT EXISTS prices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id VARCHAR(36),
                amount DECIMAL(10,2),
                currency_label VARCHAR(10),
                currency_symbol VARCHAR(5),
                FOREIGN KEY (product_id) REFERENCES products(id)
            )",
            "CREATE TABLE IF NOT EXISTS attributes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255),
                type VARCHAR(50)
            )",
            "CREATE TABLE IF NOT EXISTS attribute_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                attribute_id INT,
                value VARCHAR(255),
                display_value VARCHAR(255),
                FOREIGN KEY (attribute_id) REFERENCES attributes(id)
            )",
            "CREATE TABLE IF NOT EXISTS product_attribute_sets (
                product_id VARCHAR(36),
                attribute_id INT,
                PRIMARY KEY (product_id, attribute_id),
                FOREIGN KEY (product_id) REFERENCES products(id),
                FOREIGN KEY (attribute_id) REFERENCES attributes(id)
            )"
        ];

        foreach ($sql as $stmt) {
            $this->pdo->exec($stmt);
        }
    }

    /**
     * @throws JsonException
     */
    public function run(string $jsonPath): void
    {
        $this->createTablesIfNotExists(); // Ensure tables exist

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
            $inStock = isset($product['inStock']) ? (int)$product['inStock'] : 0;
            $stmt->execute([
                'id' => $product['id'],
                'name' => $product['name'],
                'in_stock' => $inStock,
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

// Run the seeder
$seeder = new Seeder();
try {
    $seeder->run(__DIR__ . '/../../data.json');
} catch (JsonException $e) {
    echo "❌ JSON Error: " . $e->getMessage() . "\n";
}
