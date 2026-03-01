<?php

namespace App\Command;

use App\Service\ProductService;
use App\Service\WarehouseService;
use App\Service\StockService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed',
    description: 'Seed the database with default M&S food products, warehouses, locations, and stock',
)]
class SeedDataCommand extends Command
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly WarehouseService $warehouseService,
        private readonly StockService $stockService,
        private readonly DocumentManager $dm,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('fresh', null, InputOption::VALUE_NONE, 'Drop existing data before seeding');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('M&S Warehouse Seed Data');

        if ($input->getOption('fresh')) {
            $io->warning('Dropping existing collections...');
            $this->dm->getDocumentCollection(\App\Document\Product::class)->drop();
            $this->dm->getDocumentCollection(\App\Document\Warehouse::class)->drop();
            $this->dm->getDocumentCollection(\App\Document\Stock::class)->drop();
            $this->dm->getDocumentCollection(\App\Document\StockMovement::class)->drop();
            $this->dm->clear();
            $io->text('Collections dropped.');
        }

        $products = $this->seedProducts($io);
        $warehouses = $this->seedWarehouses($io);
        $this->seedStock($io, $products, $warehouses);

        $io->success('Database seeded successfully!');
        return Command::SUCCESS;
    }

    private function seedProducts(SymfonyStyle $io): array
    {
        $io->section('Seeding Products');

        $products = [
            // Fruit & Vegetables
            ['upc' => '500020100010', 'name' => 'Flat White Mushrooms', 'brand' => 'M&S', 'category' => 'fruit-vegetables', 'weight' => 250, 'weightUnit' => 'g', 'description' => 'Fresh flat white mushrooms, perfect for frying or grilling', 'ingredients' => ['Mushrooms'], 'allergens' => [], 'shelfLifeDays' => 5, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated. Use within 2 days of opening.'],
            ['upc' => '500020100020', 'name' => 'Midi Cucumber', 'brand' => 'M&S', 'category' => 'fruit-vegetables', 'weight' => 180, 'weightUnit' => 'g', 'description' => 'Crisp and refreshing midi cucumber', 'ingredients' => ['Cucumber'], 'allergens' => [], 'shelfLifeDays' => 7, 'countryOfOrigin' => 'Spain', 'storageInstructions' => 'Keep refrigerated.'],
            ['upc' => '500020100030', 'name' => 'Organic Bananas', 'brand' => 'M&S Organic', 'category' => 'fruit-vegetables', 'weight' => 500, 'weightUnit' => 'g', 'description' => 'Organic Fairtrade bananas', 'ingredients' => ['Bananas'], 'allergens' => [], 'shelfLifeDays' => 5, 'countryOfOrigin' => 'Dominican Republic', 'storageInstructions' => 'Store at room temperature.'],
            ['upc' => '500020100040', 'name' => 'Braeburn Apples', 'brand' => 'M&S', 'category' => 'fruit-vegetables', 'weight' => 600, 'weightUnit' => 'g', 'description' => 'Sweet and crisp Braeburn apples, pack of 6', 'ingredients' => ['Apples'], 'allergens' => [], 'shelfLifeDays' => 14, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated for freshness.'],

            // Meat & Poultry
            ['upc' => '500020200010', 'name' => 'British Chicken Breast Fillets', 'brand' => 'M&S', 'category' => 'meat-poultry', 'weight' => 360, 'weightUnit' => 'g', 'description' => 'Outdoor bred British chicken breast fillets, hand-trimmed', 'ingredients' => ['Chicken Breast'], 'allergens' => [], 'shelfLifeDays' => 4, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated 0-4°C. Use by date on pack.'],
            ['upc' => '500020200020', 'name' => 'Slow Cooked Pulled Pork', 'brand' => 'M&S Slow Cooked', 'category' => 'meat-poultry', 'weight' => 400, 'weightUnit' => 'g', 'description' => 'British pork shoulder slow cooked in a smoky BBQ sauce', 'ingredients' => ['Pork Shoulder (70%)', 'BBQ Sauce', 'Smoked Paprika', 'Brown Sugar', 'Vinegar'], 'allergens' => ['Mustard'], 'shelfLifeDays' => 7, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated. Once opened use within 2 days.'],
            ['upc' => '500020200030', 'name' => 'Aberdeen Angus Beef Burgers', 'brand' => 'M&S', 'category' => 'meat-poultry', 'weight' => 340, 'weightUnit' => 'g', 'description' => '4 Aberdeen Angus quarter pounder beef burgers', 'ingredients' => ['Beef (95%)', 'Salt', 'Black Pepper'], 'allergens' => [], 'shelfLifeDays' => 5, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated. Cook from chilled.'],

            // Fish & Seafood
            ['upc' => '500020300010', 'name' => 'Extra Large Honduran King Prawns', 'brand' => 'M&S', 'category' => 'fish-seafood', 'weight' => 200, 'weightUnit' => 'g', 'description' => 'Extra large sustainably sourced king prawns', 'ingredients' => ['King Prawns'], 'allergens' => ['Crustaceans'], 'shelfLifeDays' => 3, 'countryOfOrigin' => 'Honduras', 'storageInstructions' => 'Keep refrigerated 0-4°C.'],
            ['upc' => '500020300020', 'name' => 'Scottish Salmon Fillets', 'brand' => 'M&S', 'category' => 'fish-seafood', 'weight' => 240, 'weightUnit' => 'g', 'description' => 'Two boneless Scottish salmon fillets, responsibly sourced', 'ingredients' => ['Salmon Fillets'], 'allergens' => ['Fish'], 'shelfLifeDays' => 4, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated 0-4°C.'],

            // Ready Meals
            ['upc' => '500020400010', 'name' => 'Chicken Saag', 'brand' => 'M&S', 'category' => 'ready-meals', 'weight' => 400, 'weightUnit' => 'g', 'description' => 'Tender chicken in a fragrant spinach, tomato and cream sauce', 'ingredients' => ['Chicken (32%)', 'Spinach', 'Tomatoes', 'Cream', 'Onion', 'Spices', 'Garlic', 'Ginger'], 'allergens' => ['Milk'], 'shelfLifeDays' => 5, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated. Suitable for microwave.'],
            ['upc' => '500020400020', 'name' => 'Gastropub Lasagne', 'brand' => 'M&S Gastropub', 'category' => 'ready-meals', 'weight' => 450, 'weightUnit' => 'g', 'description' => 'Layers of egg pasta with a slow-cooked beef ragu and creamy béchamel', 'ingredients' => ['Beef (22%)', 'Pasta', 'Béchamel Sauce', 'Tomatoes', 'Cheese', 'Onion'], 'allergens' => ['Milk', 'Gluten', 'Eggs'], 'shelfLifeDays' => 5, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated. Oven cook for best results.'],
            ['upc' => '500020400030', 'name' => 'Plant Kitchen Mushroom Stroganoff', 'brand' => 'Plant Kitchen', 'category' => 'ready-meals', 'weight' => 350, 'weightUnit' => 'g', 'description' => 'A rich and creamy vegan mushroom stroganoff with rice', 'ingredients' => ['Mushrooms (30%)', 'Rice', 'Coconut Cream', 'Onion', 'Smoked Paprika'], 'allergens' => [], 'shelfLifeDays' => 5, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated.'],
            ['upc' => '500020400040', 'name' => 'Count On Us Thai Green Curry', 'brand' => 'Count On Us', 'category' => 'ready-meals', 'weight' => 350, 'weightUnit' => 'g', 'description' => 'A lighter Thai green curry with chicken, vegetables and jasmine rice', 'ingredients' => ['Chicken (20%)', 'Jasmine Rice', 'Coconut Milk', 'Green Beans', 'Thai Green Curry Paste'], 'allergens' => ['Fish', 'Milk'], 'shelfLifeDays' => 5, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated.'],

            // Ready to Cook
            ['upc' => '500020500010', 'name' => 'Hot Honey Hawaiian Pizza', 'brand' => 'M&S', 'category' => 'ready-to-cook', 'weight' => 521, 'weightUnit' => 'g', 'description' => 'Stone baked pizza with ham, pineapple and a hot honey drizzle', 'ingredients' => ['Wheat Flour', 'Mozzarella', 'Ham', 'Pineapple', 'Tomato Sauce', 'Hot Honey'], 'allergens' => ['Gluten', 'Milk'], 'shelfLifeDays' => 4, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated. Oven cook only.'],
            ['upc' => '500020500020', 'name' => 'Collection Crispy Jewelled Vegetable Samosas', 'brand' => 'M&S Collection', 'category' => 'ready-to-cook', 'weight' => 180, 'weightUnit' => 'g', 'description' => 'Crispy filo pastry parcels filled with spiced vegetables and pomegranate', 'ingredients' => ['Filo Pastry', 'Potato', 'Peas', 'Spices', 'Pomegranate'], 'allergens' => ['Gluten'], 'shelfLifeDays' => 4, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated. Oven cook.'],
            ['upc' => '500020500030', 'name' => 'Our Best Ever Macaroni Cheese', 'brand' => 'Our Best Ever', 'category' => 'ready-to-cook', 'weight' => 400, 'weightUnit' => 'g', 'description' => 'A blend of mature cheddar, Red Leicester and Gruyère in a rich cheese sauce with macaroni', 'ingredients' => ['Macaroni Pasta', 'Mature Cheddar', 'Red Leicester', 'Gruyère', 'Cream', 'Butter'], 'allergens' => ['Gluten', 'Milk'], 'shelfLifeDays' => 5, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated. Oven cook for best results.'],

            // Bakery & Cakes
            ['upc' => '500020600010', 'name' => 'Tiramisu Hot Cross Buns', 'brand' => 'M&S', 'category' => 'bakery-cakes', 'weight' => 240, 'weightUnit' => 'g', 'description' => 'Hot cross buns with coffee flavour and mascarpone filling, topped with a cocoa cross', 'ingredients' => ['Wheat Flour', 'Sugar', 'Mascarpone', 'Coffee Extract', 'Cocoa Powder', 'Butter', 'Yeast'], 'allergens' => ['Gluten', 'Milk', 'Eggs'], 'shelfLifeDays' => 3, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Store in a cool, dry place.'],
            ['upc' => '500020600020', 'name' => 'Red Velvet Filled Hot Cross Buns', 'brand' => 'M&S', 'category' => 'bakery-cakes', 'weight' => 280, 'weightUnit' => 'g', 'description' => 'Red velvet hot cross buns with a white chocolate cream cheese filling', 'ingredients' => ['Wheat Flour', 'Sugar', 'Cream Cheese', 'White Chocolate', 'Cocoa Powder', 'Beetroot Powder', 'Butter'], 'allergens' => ['Gluten', 'Milk', 'Eggs', 'Soya'], 'shelfLifeDays' => 3, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Store in a cool, dry place.'],
            ['upc' => '500020600030', 'name' => 'Colin the Caterpillar Cake', 'brand' => 'Colin the Caterpillar', 'category' => 'bakery-cakes', 'weight' => 630, 'weightUnit' => 'g', 'description' => 'The iconic milk chocolate caterpillar cake with a chocolate buttercream filling', 'ingredients' => ['Milk Chocolate (30%)', 'Sugar', 'Wheat Flour', 'Butter', 'Eggs', 'Cocoa Powder'], 'allergens' => ['Gluten', 'Milk', 'Eggs', 'Soya'], 'shelfLifeDays' => 14, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Store in a cool, dry place.'],

            // Dairy
            ['upc' => '500020700010', 'name' => 'Organic Whole Milk', 'brand' => 'M&S Organic', 'category' => 'dairy', 'weight' => 1136, 'weightUnit' => 'g', 'description' => 'Organic whole milk from British farms, 2 pints', 'ingredients' => ['Whole Milk'], 'allergens' => ['Milk'], 'shelfLifeDays' => 7, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated 0-4°C. Use within 3 days of opening.'],
            ['upc' => '500020700020', 'name' => 'Cornish Clotted Cream', 'brand' => 'M&S', 'category' => 'dairy', 'weight' => 227, 'weightUnit' => 'g', 'description' => 'Traditionally made Cornish clotted cream with a golden crust', 'ingredients' => ['Cream (Milk)'], 'allergens' => ['Milk'], 'shelfLifeDays' => 10, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated.'],

            // Deli
            ['upc' => '500020800010', 'name' => 'Italian Prosciutto', 'brand' => 'M&S Collection', 'category' => 'deli', 'weight' => 80, 'weightUnit' => 'g', 'description' => 'Dry cured Italian ham matured for 16 months', 'ingredients' => ['Pork Leg', 'Salt'], 'allergens' => [], 'shelfLifeDays' => 21, 'countryOfOrigin' => 'Italy', 'storageInstructions' => 'Keep refrigerated. Once opened use within 2 days.'],
            ['upc' => '500020800020', 'name' => 'Korean Style Chicken Wrap', 'brand' => 'M&S', 'category' => 'food-on-the-move', 'weight' => 229, 'weightUnit' => 'g', 'description' => 'Tortilla wrap with Korean-style fried chicken, kimchi slaw and gochujang mayo', 'ingredients' => ['Tortilla Wrap', 'Chicken (28%)', 'Kimchi Slaw', 'Gochujang Mayo', 'Lettuce'], 'allergens' => ['Gluten', 'Eggs', 'Soya', 'Sesame'], 'shelfLifeDays' => 2, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated.'],

            // Confectionery / Snacks
            ['upc' => '500020900010', 'name' => 'Percy Pig Original', 'brand' => 'Percy Pig', 'category' => 'confectionery', 'weight' => 170, 'weightUnit' => 'g', 'description' => 'The nation\'s favourite Percy Pig fruit gum sweets', 'ingredients' => ['Glucose Syrup', 'Sugar', 'Gelatine', 'Fruit Juice Concentrates (Apple, Strawberry, Raspberry, Grape)'], 'allergens' => [], 'shelfLifeDays' => 365, 'countryOfOrigin' => 'Germany', 'storageInstructions' => 'Store in a cool, dry place.', 'nutritionalInfo' => ['calories' => 345, 'fat' => 0.1, 'carbohydrates' => 78, 'sugars' => 55, 'protein' => 6.5]],
            ['upc' => '500020900020', 'name' => 'Dippy Egg Cookie Cup', 'brand' => 'M&S', 'category' => 'confectionery', 'weight' => 110, 'weightUnit' => 'g', 'description' => 'Chocolate cookie cup with a white chocolate and salted caramel filling', 'ingredients' => ['Milk Chocolate', 'White Chocolate', 'Wheat Flour', 'Butter', 'Sugar', 'Salted Caramel'], 'allergens' => ['Gluten', 'Milk', 'Eggs', 'Soya'], 'shelfLifeDays' => 60, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Store in a cool, dry place.'],

            // Food Cupboard
            ['upc' => '500021000010', 'name' => 'Basmati Rice', 'brand' => 'M&S', 'category' => 'food-cupboard', 'weight' => 300, 'weightUnit' => 'g', 'description' => 'Fluffy steamed basmati rice, ready in 2 minutes', 'ingredients' => ['Basmati Rice', 'Water'], 'allergens' => [], 'shelfLifeDays' => 180, 'countryOfOrigin' => 'India', 'storageInstructions' => 'Store in a cool, dry place. Once opened, refrigerate and use within 1 day.'],
            ['upc' => '500021000020', 'name' => 'Ghost Chilli Hot Sauce', 'brand' => 'M&S', 'category' => 'food-cupboard', 'weight' => 150, 'weightUnit' => 'g', 'description' => 'Extremely hot ghost chilli sauce, not for the faint-hearted', 'ingredients' => ['Ghost Chilli (7%)', 'Vinegar', 'Tomatoes', 'Garlic', 'Salt', 'Sugar'], 'allergens' => [], 'shelfLifeDays' => 365, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Store in a cool, dry place. Refrigerate after opening.'],

            // Drinks
            ['upc' => '500021100010', 'name' => 'Cold Pressed Collagen Juice', 'brand' => 'M&S', 'category' => 'drinks', 'weight' => 300, 'weightUnit' => 'g', 'description' => 'Cold pressed fruit juice with added collagen', 'ingredients' => ['Apple Juice', 'Grape Juice', 'Collagen Peptides', 'Lemon Juice'], 'allergens' => [], 'shelfLifeDays' => 10, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated. Shake well before use.'],
            ['upc' => '500021100020', 'name' => 'High Protein Chocolate Shot', 'brand' => 'High Protein', 'category' => 'drinks', 'weight' => 300, 'weightUnit' => 'g', 'description' => 'High protein chocolate flavoured drink with 25g protein', 'ingredients' => ['Skimmed Milk', 'Milk Protein', 'Cocoa Powder', 'Sweetener'], 'allergens' => ['Milk'], 'shelfLifeDays' => 14, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated.', 'nutritionalInfo' => ['calories' => 150, 'protein' => 25, 'fat' => 1.5, 'carbohydrates' => 12]],

            // Frozen
            ['upc' => '500021200010', 'name' => 'Gastropub Steak and Ale Pie', 'brand' => 'M&S Gastropub', 'category' => 'frozen-food', 'weight' => 250, 'weightUnit' => 'g', 'description' => 'British beef steak in an ale gravy topped with puff pastry', 'ingredients' => ['Wheat Flour', 'Beef (26%)', 'Ale', 'Butter', 'Onion', 'Beef Stock'], 'allergens' => ['Gluten', 'Milk', 'Barley'], 'shelfLifeDays' => 270, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep frozen at -18°C. Do not refreeze once thawed.'],
            ['upc' => '500021200020', 'name' => 'Gluten Free Apple & Brown Sugar Hot Cross Buns', 'brand' => 'Made Without Wheat', 'category' => 'frozen-food', 'weight' => 250, 'weightUnit' => 'g', 'description' => 'Gluten-free hot cross buns with Bramley apple pieces and brown sugar', 'ingredients' => ['Rice Flour', 'Apple Pieces (12%)', 'Brown Sugar', 'Potato Starch', 'Yeast', 'Mixed Spice'], 'allergens' => ['Eggs'], 'shelfLifeDays' => 180, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep frozen. Defrost before serving.'],

            // Chilled Desserts
            ['upc' => '500021300010', 'name' => 'Collection Belgian Chocolate Mousse', 'brand' => 'M&S Collection', 'category' => 'chilled-desserts', 'weight' => 180, 'weightUnit' => 'g', 'description' => 'Indulgent Belgian dark chocolate mousse with a chocolate ganache layer', 'ingredients' => ['Dark Chocolate (35%)', 'Cream', 'Sugar', 'Eggs', 'Cocoa Butter'], 'allergens' => ['Milk', 'Eggs', 'Soya'], 'shelfLifeDays' => 7, 'countryOfOrigin' => 'United Kingdom', 'storageInstructions' => 'Keep refrigerated.'],
        ];

        $created = [];
        foreach ($products as $data) {
            try {
                $existing = $this->productService->findByUpc($data['upc']);
                if ($existing) {
                    $io->text(sprintf('  ✓ Already exists: %s (%s)', $data['name'], $data['upc']));
                    $created[$data['upc']] = $existing;
                    continue;
                }
                $product = $this->productService->create($data);
                $created[$data['upc']] = $product;
                $io->text(sprintf('  + Created: %s (%s)', $data['name'], $data['upc']));
            } catch (\Throwable $e) {
                $io->error(sprintf('Failed to create %s: %s', $data['name'], $e->getMessage()));
            }
        }

        $io->text(sprintf('Total products: %d', count($created)));
        return $created;
    }

    private function seedWarehouses(SymfonyStyle $io): array
    {
        $io->section('Seeding Warehouses & Locations');

        $warehouseDefinitions = [
            [
                'code' => 'MK-001',
                'name' => 'Milton Keynes Distribution Centre',
                'address' => 'Precedent Drive, Rooksley',
                'city' => 'Milton Keynes',
                'state' => 'Buckinghamshire',
                'postalCode' => 'MK13 8HF',
                'country' => 'United Kingdom',
                'size' => 'large',
            ],
            [
                'code' => 'BD-001',
                'name' => 'Bradford National Distribution Centre',
                'address' => 'Euroway Trading Estate',
                'city' => 'Bradford',
                'state' => 'West Yorkshire',
                'postalCode' => 'BD4 6SE',
                'country' => 'United Kingdom',
                'size' => 'large',
            ],
            [
                'code' => 'TH-001',
                'name' => 'Thatcham Distribution Centre',
                'address' => 'Colthrop Way, Colthrop Business Park',
                'city' => 'Thatcham',
                'state' => 'Berkshire',
                'postalCode' => 'RG19 4NR',
                'country' => 'United Kingdom',
                'size' => 'standard',
            ],
            [
                'code' => 'DV-001',
                'name' => 'Daventry Regional Distribution Centre',
                'address' => 'Royal Oak Way, Daventry International Rail Freight Terminal',
                'city' => 'Daventry',
                'state' => 'Northamptonshire',
                'postalCode' => 'NN11 8RQ',
                'country' => 'United Kingdom',
                'size' => 'standard',
            ],
            [
                'code' => 'CF-001',
                'name' => 'Chesterfield Distribution Centre',
                'address' => 'Markham Vale',
                'city' => 'Chesterfield',
                'state' => 'Derbyshire',
                'postalCode' => 'S44 5HY',
                'country' => 'United Kingdom',
                'size' => 'standard',
            ],
            [
                'code' => 'FV-001',
                'name' => 'Faversham Distribution Centre',
                'address' => 'Oare Creek Business Park',
                'city' => 'Faversham',
                'state' => 'Kent',
                'postalCode' => 'ME13 7DZ',
                'country' => 'United Kingdom',
                'size' => 'standard',
            ],
        ];

        $created = [];
        foreach ($warehouseDefinitions as $whDef) {
            $size = $whDef['size'];
            unset($whDef['size']);

            try {
                $existing = $this->warehouseService->findByCode($whDef['code']);
                if ($existing) {
                    $io->text(sprintf('  ✓ Already exists: %s (%s)', $whDef['name'], $whDef['code']));
                    $created[$whDef['code']] = ['warehouse' => $existing, 'size' => $size];
                    continue;
                }

                $warehouse = $this->warehouseService->create($whDef);
                $io->text(sprintf('  + Created warehouse: %s (%s)', $whDef['name'], $whDef['code']));

                $this->seedLocations($io, $warehouse->getId(), $size);
                $created[$whDef['code']] = ['warehouse' => $warehouse, 'size' => $size];
            } catch (\Throwable $e) {
                $io->error(sprintf('Failed to create %s: %s', $whDef['name'], $e->getMessage()));
            }
        }

        $io->text(sprintf('Total warehouses: %d', count($created)));
        return $created;
    }

    private function seedLocations(SymfonyStyle $io, string $warehouseId, string $size): void
    {
        $isLarge = $size === 'large';

        // Receiving docks
        $receivingCount = $isLarge ? 4 : 2;
        for ($i = 1; $i <= $receivingCount; $i++) {
            $this->warehouseService->addLocation($warehouseId, [
                'name' => sprintf('Receiving Dock %s', str_pad((string)$i, 2, '0', STR_PAD_LEFT)),
                'type' => 'receiving',
                'aisle' => 'R',
                'rack' => (string)$i,
                'capacity' => $isLarge ? 1000 : 500,
            ]);
        }
        $io->text(sprintf('    + %d receiving docks', $receivingCount));

        // Storage locations
        $aisleCount = $isLarge ? 8 : 4;
        $rackCount = $isLarge ? 6 : 4;
        $shelfCount = $isLarge ? 4 : 3;
        $storageCount = 0;
        for ($a = 1; $a <= $aisleCount; $a++) {
            for ($r = 1; $r <= $rackCount; $r++) {
                for ($s = 1; $s <= $shelfCount; $s++) {
                    $this->warehouseService->addLocation($warehouseId, [
                        'name' => sprintf('STO-A%s-R%s-S%s', str_pad((string)$a, 2, '0', STR_PAD_LEFT), str_pad((string)$r, 2, '0', STR_PAD_LEFT), str_pad((string)$s, 2, '0', STR_PAD_LEFT)),
                        'type' => 'storage',
                        'aisle' => sprintf('A%s', str_pad((string)$a, 2, '0', STR_PAD_LEFT)),
                        'rack' => sprintf('R%s', str_pad((string)$r, 2, '0', STR_PAD_LEFT)),
                        'shelf' => sprintf('S%s', str_pad((string)$s, 2, '0', STR_PAD_LEFT)),
                        'capacity' => 100,
                    ]);
                    $storageCount++;
                }
            }
        }
        $io->text(sprintf('    + %d storage locations (%d aisles × %d racks × %d shelves)', $storageCount, $aisleCount, $rackCount, $shelfCount));

        // Picking locations
        $pickingCount = $isLarge ? 12 : 6;
        for ($i = 1; $i <= $pickingCount; $i++) {
            $this->warehouseService->addLocation($warehouseId, [
                'name' => sprintf('Pick Zone %s', str_pad((string)$i, 2, '0', STR_PAD_LEFT)),
                'type' => 'picking',
                'aisle' => 'P',
                'rack' => (string)$i,
                'capacity' => 50,
            ]);
        }
        $io->text(sprintf('    + %d picking zones', $pickingCount));

        // Picked/staging locations
        $pickedCount = $isLarge ? 6 : 3;
        for ($i = 1; $i <= $pickedCount; $i++) {
            $this->warehouseService->addLocation($warehouseId, [
                'name' => sprintf('Staging Lane %s', str_pad((string)$i, 2, '0', STR_PAD_LEFT)),
                'type' => 'picked',
                'aisle' => 'D',
                'rack' => (string)$i,
                'capacity' => 200,
            ]);
        }
        $io->text(sprintf('    + %d staging lanes (picked)', $pickedCount));
    }

    private function seedStock(SymfonyStyle $io, array $products, array $warehouses): void
    {
        $io->section('Seeding Initial Stock (receiving into warehouses)');

        // Stock allocation: receive a range of products into each warehouse
        $productList = array_values($products);
        $warehouseList = array_values($warehouses);

        foreach ($warehouseList as $whEntry) {
            $warehouse = $whEntry['warehouse'];
            $isLarge = $whEntry['size'] === 'large';
            $whId = $warehouse->getId();

            // Refresh the warehouse to get locations
            $this->dm->refresh($warehouse);
            $locations = $warehouse->getLocations();

            // Find a receiving location
            $receivingLoc = null;
            foreach ($locations as $loc) {
                if ($loc->getType() === 'receiving') {
                    $receivingLoc = $loc;
                    break;
                }
            }

            if (!$receivingLoc) {
                $io->warning(sprintf('  No receiving location found for %s, skipping stock.', $warehouse->getName()));
                continue;
            }

            // Receive a subset of products - large warehouses get more
            $productSubset = $isLarge ? $productList : array_slice($productList, 0, (int)(count($productList) * 0.6));
            $receivedCount = 0;

            foreach ($productSubset as $product) {
                $qty = $isLarge ? rand(100, 500) : rand(30, 150);
                try {
                    $this->stockService->receiveStock([
                        'productId' => $product->getId(),
                        'warehouseId' => $whId,
                        'locationId' => $receivingLoc->getId(),
                        'quantity' => $qty,
                        'reference' => sprintf('SEED-%s-%s', $warehouse->getCode(), date('Ymd')),
                        'notes' => 'Initial seed stock',
                    ]);
                    $receivedCount++;
                } catch (\Throwable $e) {
                    $io->error(sprintf('  Failed to receive %s at %s: %s', $product->getName(), $warehouse->getName(), $e->getMessage()));
                }
            }
            $io->text(sprintf('  + %s: received %d products into %s', $warehouse->getName(), $receivedCount, $receivingLoc->getName()));
        }
    }
}
