<?php

namespace App\Service;

use App\Document\Product;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductService
{
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly ValidatorInterface $validator,
    ) {}

    public function findAll(): array
    {
        return $this->dm->getRepository(Product::class)->findAll();
    }

    public function findById(string $id): Product
    {
        $product = $this->dm->getRepository(Product::class)->find($id);

        if (!$product) {
            throw new \InvalidArgumentException('Product not found');
        }

        return $product;
    }

    public function findByUpc(string $upc): ?Product
    {
        return $this->dm->getRepository(Product::class)->findOneBy(['upc' => $upc]);
    }

    public function create(array $data): Product
    {
        $existing = $this->findByUpc($data['upc'] ?? '');
        if ($existing) {
            throw new \InvalidArgumentException('A product with this UPC already exists');
        }

        $product = new Product();
        $this->hydrateProduct($product, $data);
        $this->validate($product);

        $this->dm->persist($product);
        $this->dm->flush();

        return $product;
    }

    public function update(string $id, array $data): Product
    {
        $product = $this->findById($id);

        if (isset($data['upc']) && $data['upc'] !== $product->getUpc()) {
            $existing = $this->findByUpc($data['upc']);
            if ($existing) {
                throw new \InvalidArgumentException('A product with this UPC already exists');
            }
        }

        $this->hydrateProduct($product, $data);
        $product->setUpdatedAt(new \DateTimeImmutable());
        $this->validate($product);

        $this->dm->flush();

        return $product;
    }

    public function delete(string $id): void
    {
        $product = $this->findById($id);
        $this->dm->remove($product);
        $this->dm->flush();
    }

    private function hydrateProduct(Product $product, array $data): void
    {
        if (isset($data['upc'])) {
            $product->setUpc($data['upc']);
        }
        if (isset($data['ean'])) {
            $product->setEan($data['ean']);
        }
        if (isset($data['name'])) {
            $product->setName($data['name']);
        }
        if (isset($data['description'])) {
            $product->setDescription($data['description']);
        }
        if (isset($data['brand'])) {
            $product->setBrand($data['brand']);
        }
        if (isset($data['category'])) {
            $product->setCategory($data['category']);
        }
        if (isset($data['weight'])) {
            $product->setWeight((float) $data['weight']);
        }
        if (isset($data['weightUnit'])) {
            $product->setWeightUnit($data['weightUnit']);
        }
        if (isset($data['imageUrl'])) {
            $product->setImageUrl($data['imageUrl']);
        }
        if (isset($data['ingredients'])) {
            $product->setIngredients($data['ingredients']);
        }
        if (isset($data['allergens'])) {
            $product->setAllergens($data['allergens']);
        }
        if (isset($data['nutritionalInfo'])) {
            $product->setNutritionalInfo($data['nutritionalInfo']);
        }
        if (isset($data['storageInstructions'])) {
            $product->setStorageInstructions($data['storageInstructions']);
        }
        if (isset($data['shelfLifeDays'])) {
            $product->setShelfLifeDays((int) $data['shelfLifeDays']);
        }
        if (isset($data['countryOfOrigin'])) {
            $product->setCountryOfOrigin($data['countryOfOrigin']);
        }
        if (array_key_exists('isActive', $data)) {
            $product->setIsActive((bool) $data['isActive']);
        }
    }

    private function validate(Product $product): void
    {
        $errors = $this->validator->validate($product);

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            throw new \InvalidArgumentException(implode('; ', $messages));
        }
    }
}
