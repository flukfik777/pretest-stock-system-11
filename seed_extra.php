<?php
require 'db.php';

$products = [
    ['Intel Core i9-14900K', 'CPU', 24900.00, 10, 'https://placehold.co/300x300/1a1a1a/00ff00?text=CPU'],
    ['AMD Ryzen 9 7950X', 'CPU', 22500.00, 8, 'https://placehold.co/300x300/1a1a1a/00eeff?text=CPU'],
    ['Intel Core i7-14700K', 'CPU', 16500.00, 15, 'https://placehold.co/300x300/1a1a1a/00ff00?text=CPU'],
    ['RTX 4090 ROG Strix', 'GPU', 75000.00, 5, 'https://placehold.co/300x300/1a1a1a/00ff00?text=GPU'],
    ['RTX 4080 Super TUF', 'GPU', 42000.00, 12, 'https://placehold.co/300x300/1a1a1a/00ff00?text=GPU'],
    ['RTX 4070 Ti Super', 'GPU', 32500.00, 10, 'https://placehold.co/300x300/1a1a1a/00ff00?text=GPU'],
    ['RX 7900 XTX Nitro+', 'GPU', 38500.00, 7, 'https://placehold.co/300x300/1a1a1a/ff3300?text=GPU'],
    ['Corsair Dominator 32GB', 'RAM', 6500.00, 20, 'https://placehold.co/300x300/1a1a1a/00ff00?text=RAM'],
    ['Kingston FURY Beast 16GB', 'RAM', 2400.00, 35, 'https://placehold.co/300x300/1a1a1a/00ff00?text=RAM'],
    ['G.Skill Trident Z5 64GB', 'RAM', 9500.00, 5, 'https://placehold.co/300x300/1a1a1a/00ff00?text=RAM'],
    ['Samsung 990 Pro 1TB', 'Storage', 4500.00, 15, 'https://placehold.co/300x300/1a1a1a/00ff00?text=SSD'],
    ['Samsung 990 Pro 2TB', 'Storage', 7800.00, 10, 'https://placehold.co/300x300/1a1a1a/00ff00?text=SSD'],
    ['WD Black SN850X 2TB', 'Storage', 6200.00, 10, 'https://placehold.co/300x300/1a1a1a/00ff00?text=SSD'],
    ['Crucial P5 Plus 1TB', 'Storage', 3200.00, 25, 'https://placehold.co/300x300/1a1a1a/00ff00?text=SSD'],
    ['NZXT H7 Flow Black', 'Case', 4200.00, 15, 'https://placehold.co/300x300/1a1a1a/00ff00?text=Case'],
    ['Lian Li O11 Dynamic', 'Case', 5500.00, 8, 'https://placehold.co/300x300/1a1a1a/00ff00?text=Case'],
    ['Fractal Design North', 'Case', 5900.00, 12, 'https://placehold.co/300x300/1a1a1a/00ff00?text=Case'],
    ['Corsair RM850e 850W', 'Power Supply', 4800.00, 20, 'https://placehold.co/300x300/1a1a1a/00ff00?text=PSU'],
    ['ASUS ROG Thor 1000W', 'Power Supply', 12500.00, 5, 'https://placehold.co/300x300/1a1a1a/00ff00?text=PSU'],
    ['Be Quiet! Straight Power 12', 'Power Supply', 6500.00, 10, 'https://placehold.co/300x300/1a1a1a/00ff00?text=PSU'],
    ['ROG Ryujin III 360', 'Cooling', 13900.00, 6, 'https://placehold.co/300x300/1a1a1a/00ff00?text=Cooler'],
    ['Noctua NH-D15 chromax', 'Cooling', 4200.00, 10, 'https://placehold.co/300x300/1a1a1a/00ff00?text=Cooler'],
    ['Arctic Liquid Freezer II 360', 'Cooling', 3800.00, 18, 'https://placehold.co/300x300/1a1a1a/00ff00?text=Cooler'],
    ['ASUS ROG Maximus Z790', 'Mainboard', 24500.00, 4, 'https://placehold.co/300x300/1a1a1a/00ff00?text=Board'],
    ['MSI MPG Z790 Carbon', 'Mainboard', 14200.00, 8, 'https://placehold.co/300x300/1a1a1a/00ff00?text=Board'],
];

$added = 0;
foreach ($products as $p) {
    // Check if product exists by name
    $stmt = $pdo->prepare("SELECT id FROM products WHERE name = ?");
    $stmt->execute([$p[0]]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock_quantity, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($p);
        $added++;
    }
}

echo "Seeded $added new products!";
?>
