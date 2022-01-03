<?php

namespace Commerce;

use Exception;

class Statuses
{
    protected $modx;
    protected $statuses = [];

    public function __construct(\DocumentParser $modx)
    {
        $this->modx = $modx;
        $this->loadStatuses();
    }

    protected function loadStatuses()
    {
        $this->statuses = ci()->cache->getOrCreate('statuses', function() {
            $statuses = [];
            $q = $this->modx->db->query("SELECT * FROM {$this->modx->getFullTableName('commerce_order_statuses')} ORDER BY `id` ASC");
            while ($row = $this->modx->db->getRow($q)) {
                $row['color'] = !empty($row['color']) ? $row['color'] : 'FFFFFF';
                $statuses[$row['id']] = $row;
            }

            return $statuses;
        });
    }

    /**
     * @param $id
     * @return array
     * @throws Exception
     */
    public function getStatus($id)
    {
        if (isset($this->statuses[$id])) {
            $out = $this->statuses[$id];
        } else {
            throw new Exception('Status ' . $id . ' not found');
        }

        return $out;
    }

    /**
     * @return array
     */
    public function getStatuses()
    {
        return $this->statuses;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getDefaultStatus()
    {
        $default = false;
        foreach ($this->statuses as $id => $status) {
            if ($status['default'] == 1) {
                $default = $id;
                break;
            }
        }
        if ($default === false) {
            throw new Exception('Default status not found');
        }

        return $default;
    }

    /**
     * @param $id
     * @return bool
     */
    public function canBePaid($id)
    {
        return isset($this->statuses[$id]) && $this->statuses[$id]['canbepaid'] == 1;
    }
}
