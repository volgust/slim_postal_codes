<?php

namespace App\Infrastructure\Persistence\PostalCode;

use App\Domain\PostalCode\Contracts\PostCodeRepositoryInterface;
use PDO;

class MySQLPostCodeRepository implements PostCodeRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByPostCode(string $postCode): ?array
    {
        $stmt = $this->pdo->prepare("
        SELECT *
        FROM locations l
        WHERE l.post_code = :post_code
        LIMIT 1
    ");

        $stmt->execute(['post_code' => $postCode]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function searchByAddress(string $address): array
    {
        $stmt = $this->pdo->prepare("
        SELECT *
        FROM locations l
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

    public function paginate(int $limit, int $page): array
    {
        $offset = ($page - 1) * $limit;

        $stmt = $this->pdo->prepare("
        SELECT *
        FROM locations l
        ORDER BY l.post_code ASC
        LIMIT :limit OFFSET :offset
    ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
