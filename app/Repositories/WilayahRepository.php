<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class WilayahRepository
{
    private $pdo;
    private $driver;

    public function __construct()
    {
        $this->pdo = Database::connection();
        $this->driver = Database::driver();
    }

    public function provinces()
    {
        try {
            return $this->pdo->query('SELECT id, name FROM provinces ORDER BY name ASC')->fetchAll();
        } catch (\Throwable $exception) {
            return array();
        }
    }

    public function cities($provinceId = null)
    {
        try {
            if ($provinceId) {
                $statement = $this->pdo->prepare('SELECT id, province_id, name FROM cities WHERE province_id = :province_id ORDER BY name ASC');
                $statement->bindValue(':province_id', (int) $provinceId, PDO::PARAM_INT);
                $statement->execute();
                return $statement->fetchAll();
            }
            return $this->pdo->query('SELECT id, province_id, name FROM cities ORDER BY name ASC')->fetchAll();
        } catch (\Throwable $exception) {
            return array();
        }
    }

    public function districts($cityId = null)
    {
        try {
            if ($cityId) {
                $statement = $this->pdo->prepare('SELECT id, city_id, name FROM districts WHERE city_id = :city_id ORDER BY name ASC');
                $statement->bindValue(':city_id', (int) $cityId, PDO::PARAM_INT);
                $statement->execute();
                return $statement->fetchAll();
            }
            return $this->pdo->query('SELECT id, city_id, name FROM districts ORDER BY name ASC')->fetchAll();
        } catch (\Throwable $exception) {
            return array();
        }
    }

    public function villages($districtId = null)
    {
        try {
            if ($districtId) {
                $statement = $this->pdo->prepare('SELECT id, district_id, name FROM villages WHERE district_id = :district_id ORDER BY name ASC');
                $statement->bindValue(':district_id', (int) $districtId, PDO::PARAM_INT);
                $statement->execute();
                return $statement->fetchAll();
            }
            return $this->pdo->query('SELECT id, district_id, name FROM villages ORDER BY name ASC')->fetchAll();
        } catch (\Throwable $exception) {
            return array();
        }
    }

    public function flatSummary()
    {
        $summary = array(
            'provinces' => 0,
            'cities' => 0,
            'districts' => 0,
            'villages' => 0,
        );

        try {
            $summary['provinces'] = (int) ($this->pdo->query('SELECT COUNT(*) AS total FROM provinces')->fetch()['total'] ?? 0);
            $summary['cities'] = (int) ($this->pdo->query('SELECT COUNT(*) AS total FROM cities')->fetch()['total'] ?? 0);
            $summary['districts'] = (int) ($this->pdo->query('SELECT COUNT(*) AS total FROM districts')->fetch()['total'] ?? 0);
            $summary['villages'] = (int) ($this->pdo->query('SELECT COUNT(*) AS total FROM villages')->fetch()['total'] ?? 0);
        } catch (\Throwable $exception) {
            return $summary;
        }

        return $summary;
    }
}
