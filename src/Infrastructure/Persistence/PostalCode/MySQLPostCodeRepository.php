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
        SELECT 
            po.post_code AS post_code,
            po.name AS post_office,
            s.name AS settlement,
            d.name AS district,
            r.name AS region
        FROM post_offices po
        JOIN settlements s ON s.id = po.settlement_id 
        JOIN districts d ON d.id = s.district_id
        JOIN regions r ON r.id = d.region_id
        WHERE po.post_code = :post_code
        LIMIT 1
    ");

        $stmt->execute(['post_code' => $postCode]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function searchByAddress(string $address): array
    {
        $stmt = $this->pdo->prepare("
        SELECT 
            po.post_code AS post_code,
            po.name AS post_office,
            s.name AS settlement,
            d.name AS district,
            r.name AS region
        FROM post_offices po
        JOIN settlements s ON s.id = po.settlement_id 
        JOIN districts d ON d.id = s.district_id
        JOIN regions r ON r.id = d.region_id
        WHERE r.name LIKE :address
           OR d.name LIKE :address
           OR s.name LIKE :address
           OR po.name LIKE :address
        ORDER BY 
            r.name ASC,
            d.name ASC,
            s.name ASC,
            po.name ASC
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
        SELECT 
            po.post_code AS post_code,
            po.name AS post_office,
            s.name AS settlement,
            d.name AS district,
            r.name AS region
        FROM post_offices po
        JOIN settlements s ON s.id = po.settlement_id 
        JOIN districts d ON d.id = s.district_id
        JOIN regions r ON r.id = d.region_id
        ORDER BY po.post_code ASC
        LIMIT :limit OFFSET :offset
    ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
