<?php

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE locations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                region_name VARCHAR(191) NOT NULL,
                district_name VARCHAR(191) NOT NULL,
                settlement_name VARCHAR(191) NOT NULL,
                post_office_name VARCHAR(191) NOT NULL,
                post_code CHAR(5) NOT NULL,
                api_created TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_post_code (post_code),
                UNIQUE KEY uq_post_office (
                    region_name,
                    district_name,
                    settlement_name,
                    post_office_name
                )
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS locations");

    }
};

