<?php

namespace App\Services;

use App\Repositories\CustomerIntiRepository;

class DashboardService
{
    private $customerRepository;

    public function __construct()
    {
        $this->customerRepository = new CustomerIntiRepository();
    }

    public function stats()
    {
        $rows = $this->customerRepository->listAll('', 100000, 0);
        $total = count($rows);
        $active = 0;
        $inactive = 0;
        $geotagged = 0;

        foreach ($rows as $row) {
            $status = $row['status'] ?? 'Aktif';
            if ($status === 'Aktif') {
                $active++;
            } else {
                $inactive++;
            }

            if (($row['lat'] ?? null) !== null && ($row['lng'] ?? null) !== null) {
                $geotagged++;
            }
        }

        return array(
            'total_customers' => $total,
            'active_customers' => $active,
            'inactive_customers' => $inactive,
            'geotagged_customers' => $geotagged,
        );
    }
}
