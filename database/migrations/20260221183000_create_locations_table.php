<?php

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE postal_codes (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                region VARCHAR(191) NOT NULL,
                district VARCHAR(191) NOT NULL,
                settlement VARCHAR(191) NOT NULL,
                post_office VARCHAR(191) NOT NULL,
                post_code CHAR(5) NOT NULL,
                api_created TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_post_code (post_code),
                UNIQUE KEY uq_post_office (
                    region,
                    district,
                    settlement,
                    post_office
                )
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS postal_codes");
    }
};
