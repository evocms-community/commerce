<?php

namespace Commerce\Controllers\Traits;

trait PrepareTrait
{
    protected function initializePrepare($cfg)
    {
        foreach (['prepare', 'prepareWrap', 'prepareProcess', 'prepareBeforeProcess'] as $field) {
            if (isset($cfg[$field])) {
                if (!is_array($cfg[$field])) {
                    $cfg[$field] = explode(',', $cfg[$field]);
                } else if (is_callable($cfg[$field])) {
                    $cfg[$field] = [$cfg[$field]];
                }
            } else {
                $cfg[$field] = [];
            }
        }

        return $cfg;
    }
}
