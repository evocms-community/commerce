<?php

namespace Commerce\Module\Controllers;

class CurrencyController extends Controller implements \Commerce\Module\Interfaces\Controller
{
    private $lang;
    private $table = 'commerce_currency';

    public function __construct($modx, $module)
    {
        parent::__construct($modx, $module);
        $this->lang = $this->modx->commerce->getUserLanguage('module');
        $this->table = $this->modx->getFullTablename($this->table);
    }

    public function registerRoutes()
    {
        return [
            'index'  => 'index',
            'edit'   => 'show',
            'save'   => 'save',
            'delete' => 'delete',
        ];
    }

    public function index()
    {
        $query = $this->modx->db->select('*', $this->table, '', 'id ASC');

        return $this->view->render('currency_list.tpl', [
            'list'   => $this->modx->db->makeArray($query),
            'custom' => $this->module->invokeTemplateEvent('OnManagerCurrencyListRender'),
        ]);
    }

    public function show()
    {
        $currency_id = filter_input(INPUT_GET, 'currency_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!empty($currency_id)) {
            $db = ci()->db;
            $query = $db->select('*', $this->table, "`id` = '$currency_id'");
            $currency = $db->getRow($query);

            if (empty($currency)) {
                $this->module->sendRedirect('currency', ['error' => $this->lang['module.error.currency_not_found']]);
            }
        } else {
            $currency = [];
        }

        return $this->view->render('currency.tpl', [
            'currency' => $currency,
            'custom' => $this->module->invokeTemplateEvent('OnManagerCurrencyRender'),
        ]);
    }

    public function save()
    {
        $db = ci()->db;
        $currency_id = filter_input(INPUT_POST, 'currency_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!empty($currency_id)) {
            $query  = $db->select('*', $this->table, "`id` = '$currency_id'");
            $currency = $db->getRow($query);

            if (empty($currency)) {
                $this->module->sendRedirect('currency', ['error' => $this->lang['module.error.currency_not_found']]);
            }
        } else {
            $currency = [];
        }

        $data = $_POST;

        $result = $this->modx->commerce->validate($data, [
            'title' => [
                'lengthBetween' => [
                    'params'  => [2, 255],
                    'message' => 'title should be between 2 and 255 symbols',
                ],
            ],
            'code' => [
                'lengthBetween' => [
                    'params'  => [2, 8],
                    'message' => 'code should be between 2 and 8 symbols',
                ],
            ],
            'value' => [
                'decimal' => 'value should be decimal number',
                'min' => [
                    'params' => [0],
                    'message' => 'value should be greater than 0',
                ],
            ],
            'left' => [
                'maxLength' => [
                    'params'  => [32],
                    'message' => 'left symbol should be less than 32 symbols',
                ],
            ],
            'right' => [
                'maxLength' => [
                    'params'  => [32],
                    'message' => 'right symbol should be less than 32 symbols',
                ],
            ],
            'decimals' => [
                'numeric' => 'decimals should be numeric',
                'min' => [
                    'params'  => [0],
                    'message' => 'decimals should be greater than 0',
                ],
            ],
            'decsep' => [
                'maxLength' => [
                    'params'  => [32],
                    'message' => 'decimals separator should be less than 32 symbols',
                ],
            ],
            'thsep' => [
                'maxLength' => [
                    'params'  => [32],
                    'message' => 'thousands separator should be less than 32 symbols',
                ],
            ],
        ]);

        if (is_array($result)) {
            $this->module->sendRedirectBack(['validation_errors' => $result]);
        }

        $fields = [
            'title'    => $db->escape($data['title']),
            'code'     => $db->escape($data['code']),
            'value'    => $data['value'],
            'left'     => $db->escape($data['left']),
            'right'    => $db->escape($data['right']),
            'decimals' => $data['decimals'],
            'decsep'   => $db->escape($data['decsep']),
            'thsep'    => $db->escape($data['thsep']),
            'active'   => !empty($data['active']) ? 1 : 0,
            'default'  => !empty($data['default']) ? 1 : 0,
        ];

        if (!$fields['default']) {
            $query = $db->select('*', $this->table, "`default` = 1" . (!empty($currency['id']) ? " AND `id` != '" . $currency['id'] . "'" : ''));

            if (!$db->getRecordCount($query)) {
                $this->module->sendRedirectBack(['error' => 'default currency should be defined']);
            }
        }

        if (!$fields['active'] && $fields['default']) {
            $this->module->sendRedirectBack(['error' => 'default currency cannot be deactivated']);
        }

        $db->query('START TRANSACTION;');

        try {
            if (!empty($currency['id'])) {
                $db->update($fields, $this->table, "`id` = '" . $currency['id'] . "'");
            } else {
                $currency['id'] = $db->insert($fields, $this->table);
            }

            if ($fields['default'] == 1) {
                $db->update(['default' => 0], $this->table, "`id` != '" . $currency['id'] . "'");
                $this->modx->clearCache('full');
            }
        } catch (\Exception $e) {
            $db->query('ROLLBACK;');
            $this->module->sendRedirectBack(['error' => $e->getMessage()]);
        }

        $db->query('COMMIT;');
        $this->module->sendRedirect('currency', ['success' => $this->lang['module.currency_saved']]);
    }

    public function delete()
    {
        $currency_id = filter_input(INPUT_GET, 'currency_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!empty($currency_id)) {
            try {
                $db = ci()->db;
                $row = $db->getRow($db->select('*', $this->table, "`id` = '$currency_id'"));

                if (!empty($row)) {
                    if ($row['default'] == 1) {
                        $this->module->sendRedirect('currency', ['error' => $this->lang['module.error.default_currency_cannot_delete']]);
                    }

                    if ($db->delete($this->table, "`id` = '$currency_id'")) {
                        $this->module->sendRedirect('currency', ['success' => $this->lang['module.currency_deleted']]);
                    }
                }
            } catch (\Exception $e) {
                $this->module->sendRedirect('currency', ['error' => $e->getMessage()]);
            }
        }

        $this->module->sendRedirect('currency', ['error' => $this->lang['module.error.currency_not_found']]);
    }
}
