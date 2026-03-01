<?php

namespace App\Service;

use App\Document\Location;
use App\Document\Warehouse;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WarehouseService
{
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly ValidatorInterface $validator,
    ) {}

    public function findAll(): array
    {
        return $this->dm->getRepository(Warehouse::class)->findAll();
    }

    public function findById(string $id): Warehouse
    {
        $warehouse = $this->dm->getRepository(Warehouse::class)->find($id);

        if (!$warehouse) {
            throw new \InvalidArgumentException('Warehouse not found');
        }

        return $warehouse;
    }

    public function findByCode(string $code): ?Warehouse
    {
        return $this->dm->getRepository(Warehouse::class)->findOneBy(['code' => $code]);
    }

    public function create(array $data): Warehouse
    {
        $existing = $this->findByCode($data['code'] ?? '');
        if ($existing) {
            throw new \InvalidArgumentException('A warehouse with this code already exists');
        }

        $warehouse = new Warehouse();
        $this->hydrateWarehouse($warehouse, $data);
        $this->validate($warehouse);

        $this->dm->persist($warehouse);
        $this->dm->flush();

        return $warehouse;
    }

    public function update(string $id, array $data): Warehouse
    {
        $warehouse = $this->findById($id);

        if (isset($data['code']) && $data['code'] !== $warehouse->getCode()) {
            $existing = $this->findByCode($data['code']);
            if ($existing) {
                throw new \InvalidArgumentException('A warehouse with this code already exists');
            }
        }

        $this->hydrateWarehouse($warehouse, $data);
        $warehouse->setUpdatedAt(new \DateTimeImmutable());
        $this->validate($warehouse);

        $this->dm->flush();

        return $warehouse;
    }

    public function delete(string $id): void
    {
        $warehouse = $this->findById($id);
        $this->dm->remove($warehouse);
        $this->dm->flush();
    }

    public function addLocation(string $warehouseId, array $data): Location
    {
        $warehouse = $this->findById($warehouseId);

        $location = new Location();
        $location->setId(Uuid::v4()->toRfc4122());
        $this->hydrateLocation($location, $data);

        $errors = $this->validator->validate($location);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            throw new \InvalidArgumentException(implode('; ', $messages));
        }

        $warehouse->addLocation($location);
        $warehouse->setUpdatedAt(new \DateTimeImmutable());
        $this->dm->flush();

        return $location;
    }

    public function updateLocation(string $warehouseId, string $locationId, array $data): Location
    {
        $warehouse = $this->findById($warehouseId);
        $location = $warehouse->findLocationById($locationId);

        if (!$location) {
            throw new \InvalidArgumentException('Location not found');
        }

        $this->hydrateLocation($location, $data);

        $errors = $this->validator->validate($location);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            throw new \InvalidArgumentException(implode('; ', $messages));
        }

        $warehouse->setUpdatedAt(new \DateTimeImmutable());
        $this->dm->flush();

        return $location;
    }

    public function deleteLocation(string $warehouseId, string $locationId): void
    {
        $warehouse = $this->findById($warehouseId);
        $location = $warehouse->findLocationById($locationId);

        if (!$location) {
            throw new \InvalidArgumentException('Location not found');
        }

        $warehouse->removeLocation($location);
        $warehouse->setUpdatedAt(new \DateTimeImmutable());
        $this->dm->flush();
    }

    public function getLocation(string $warehouseId, string $locationId): Location
    {
        $warehouse = $this->findById($warehouseId);
        $location = $warehouse->findLocationById($locationId);

        if (!$location) {
            throw new \InvalidArgumentException('Location not found');
        }

        return $location;
    }

    private function hydrateWarehouse(Warehouse $warehouse, array $data): void
    {
        if (isset($data['name'])) {
            $warehouse->setName($data['name']);
        }
        if (isset($data['code'])) {
            $warehouse->setCode($data['code']);
        }
        if (isset($data['address'])) {
            $warehouse->setAddress($data['address']);
        }
        if (isset($data['city'])) {
            $warehouse->setCity($data['city']);
        }
        if (isset($data['state'])) {
            $warehouse->setState($data['state']);
        }
        if (isset($data['postalCode'])) {
            $warehouse->setPostalCode($data['postalCode']);
        }
        if (isset($data['country'])) {
            $warehouse->setCountry($data['country']);
        }
        if (array_key_exists('isActive', $data)) {
            $warehouse->setIsActive((bool) $data['isActive']);
        }
    }

    private function hydrateLocation(Location $location, array $data): void
    {
        if (isset($data['name'])) {
            $location->setName($data['name']);
        }
        if (isset($data['type'])) {
            $location->setType($data['type']);
        }
        if (isset($data['aisle'])) {
            $location->setAisle($data['aisle']);
        }
        if (isset($data['rack'])) {
            $location->setRack($data['rack']);
        }
        if (isset($data['shelf'])) {
            $location->setShelf($data['shelf']);
        }
        if (isset($data['bin'])) {
            $location->setBin($data['bin']);
        }
        if (isset($data['capacity'])) {
            $location->setCapacity((int) $data['capacity']);
        }
        if (array_key_exists('isActive', $data)) {
            $location->setIsActive((bool) $data['isActive']);
        }
    }

    private function validate(Warehouse $warehouse): void
    {
        $errors = $this->validator->validate($warehouse);

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            throw new \InvalidArgumentException(implode('; ', $messages));
        }
    }
}
