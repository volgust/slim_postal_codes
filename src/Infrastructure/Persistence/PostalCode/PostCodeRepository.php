<?php

namespace App\Infrastructure\Persistence\PostalCode;

use App\Domain\PostalCode\Contracts\PostCodeRepositoryInterface;
use PDO;
use App\Domain\PostalCode\Entity\PostCode;

/**
 * MySQL implementation of PostCodeRepositoryInterface.
 *
 * Handles CRUD operations for postal codes using a PDO connection.
 */
class PostCodeRepository implements PostCodeRepositoryInterface
{
    /**
     * Constructor.
     *
     * @param \PDO $pdo PDO connection to the database.
     */
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Find a postal code by its value.
     *
     * @param string $postCode The 5-digit post code to search for.
     * @return array|null The postal code record as associative array, or null if not found.
     */
    public function findByPostCode(string $postCode): ?array
    {
        $stmt = $this->pdo->prepare("
        SELECT *
        FROM postal_codes l
        WHERE l.post_code = :post_code
        LIMIT 1
    ");

        $stmt->execute(['post_code' => $postCode]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Search postal codes by address components (region, district, settlement, post office).
     *
     * @param string $address Partial address to search for.
     * @return array List of matching postal codes as associative arrays (max 50).
     */
    public function searchByAddress(string $address): array
    {
        $stmt = $this->pdo->prepare("
        SELECT *
        FROM postal_codes l
        WHERE l.region LIKE :address
           OR l.district LIKE :address
           OR l.settlement LIKE :address
           OR l.post_office LIKE :address
        ORDER BY 
            l.region ASC,
            l.district ASC,
            l.settlement ASC,
            l.post_office ASC
        LIMIT 50
    ");

        $stmt->execute([
            'address' => "%{$address}%"
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Paginate postal code records.
     *
     * @param int $limit Number of records per page.
     * @param int $page Page number (1-based).
     * @return array List of postal codes as associative arrays.
     */
    public function paginate(int $limit, int $page): array
    {
        $offset = ($page - 1) * $limit;

        $stmt = $this->pdo->prepare("
        SELECT *
        FROM postal_codes l
        ORDER BY l.post_code ASC
        LIMIT :limit OFFSET :offset
    ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new postal code record.
     *
     * @param PostCode $postCode Entity containing postal code data.
     * @return PostCode The saved PostCode entity with assigned ID.
     */
    public function create(PostCode $postCode): PostCode
    {
        $data = $postCode->toArray();

        $stmt = $this->pdo->prepare("
            INSERT INTO postal_codes
            (region, district, settlement, post_office, post_code, api_created)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['region'],
            $data['district'],
            $data['settlement'],
            $data['post_office'],
            $data['post_code'],
            $data['api_created']
        ]);

        $id = (int) $this->pdo->lastInsertId();

        return new PostCode(
            $id,
            $data['region'],
            $data['district'],
            $data['settlement'],
            $data['post_office'],
            $data['post_code'],
            $data['api_created']
        );
    }

    /**
     * Find a postal code record by ID.
     *
     * @param int $id The record ID.
     * @return array The postal code record as associative array.
     * @throws \RuntimeException If the record is not found.
     */
    public function findById(int $id): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM postal_codes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $location = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$location) {
            throw new \RuntimeException("Location not found with ID {$id}");
        }

        return $location;
    }

    /**
     * Check if a post code already exists in the database.
     *
     * @param string $postCode The 5-digit post code.
     * @return bool True if the post code exists, false otherwise.
     */
    public function existsByPostCode(string $postCode): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM postal_codes WHERE post_code = :post_code LIMIT 1");
        $stmt->execute([':post_code' => $postCode]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Delete multiple postal codes by their values.
     *
     * @param array $postCodes List of post codes to delete.
     * @return int Number of deleted rows.
     */
    public function deleteByPostCodes(array $postCodes): int
    {
        $placeholders = implode(',', array_fill(0, count($postCodes), '?'));

        $stmt = $this->pdo->prepare(
            "DELETE FROM postal_codes WHERE post_code IN ($placeholders)"
        );

        $stmt->execute($postCodes);

        return $stmt->rowCount();
    }
}
