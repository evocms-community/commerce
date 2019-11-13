<?php

namespace Commerce\Module\Controllers;

class StatusesController extends Controller implements \Commerce\Module\Interfaces\Controller
{
    private $lang;
    private $table = 'commerce_order_statuses';

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
        $list  = $this->modx->db->makeArray($query);

        return $this->view->render('statuses_list.tpl', [
            'list'   => $list,
            'custom' => $this->module->invokeTemplateEvent('OnManagerStatusesListRender', [
                'list' => $list,
            ]),
        ]);
    }

    public function show()
    {
        $status_id = filter_input(INPUT_GET, 'status_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!empty($status_id)) {
            $query  = $this->modx->db->select('*', $this->table, "`id` = '$status_id'");
            $status = $this->modx->db->getRow($query);

            if (empty($status)) {
                $this->module->sendRedirect('statuses', ['error' => $this->lang['module.error.status_not_found']]);
            }
        } else {
            $status = [];
        }

        return $this->view->render('status.tpl', [
            'status' => $status,
            'custom' => $this->module->invokeTemplateEvent('OnManagerStatusRender', [
                'status' => $status,
            ]),
        ]);
    }

    public function save()
    {
        $db = ci()->db;
        $status_id = filter_input(INPUT_POST, 'status_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!empty($status_id)) {
            $query  = $db->select('*', $this->table, "`id` = '$status_id'");
            $status = $db->getRow($query);

            if (empty($status)) {
                $this->module->sendRedirect('statuses', ['error' => $this->lang['module.error.status_not_found']]);
            }
        } else {
            $status = [];
        }

        $data = $_POST;

        $result = $this->modx->commerce->validate($data, [
            'title' => [
                'lengthBetween' => [
                    'params'  => [2, 255],
                    'message' => 'title should be between 2 and 255 symbols',
                ],
            ],
        ]);

        if (is_array($result)) {
            $this->module->sendRedirectBack(['validation_errors' => $result]);
        }

        $fields = [
            'title'   => $db->escape($data['title']),
            'notify'  => !empty($data['notify']) ? 1 : 0,
            'default' => !empty($data['default']) ? 1 : 0,
        ];

        if ($fields['default'] == 0) {
            $query = $db->select('*', $this->table, "`default` = 1" . (!empty($status['id']) ? " AND `id` != '" . $status['id'] . "'" : ''));

            if (!$db->getRecordCount($query)) {
                $this->module->sendRedirectBack(['error' => 'default status should be defined']);
            }
        }

        try {
            if (!empty($status['id'])) {
                $db->update($fields, $this->table, "`id` = '" . $status['id'] . "'");
            } else {
                $status['id'] = $db->insert($fields, $this->table);
            }

            if ($fields['default'] == 1) {
                $db->update(['default' => 0], $this->table, "`id` != '" . $status['id'] . "'");
                $this->modx->clearCache('full');
            }
        } catch (\Exception $e) {
            $this->module->sendRedirectBack(['error' => $e->getMessage()]);
        }

        $this->module->sendRedirect('statuses', ['success' => $this->lang['module.status_saved']]);
    }

    public function delete()
    {
        $status_id = filter_input(INPUT_GET, 'status_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!empty($status_id)) {
            try {
                $db = ci()->db;
                $row = $db->getRow($db->select('*', $this->table, "`id` = '$status_id'"));

                if (!empty($row)) {
                    if ($row['default'] == 1) {
                        $this->module->sendRedirect('statuses', ['error' => $this->lang['module.error.default_status_cannot_delete']]);
                    }

                    if ($db->delete($this->table, "`id` = '$status_id'")) {
                        $this->module->sendRedirect('statuses', ['success' => $this->lang['module.status_deleted']]);
                    }
                }
            } catch (\Exception $e) {
                $this->module->sendRedirect('statuses', ['error' => $e->getMessage()]);
            }
        }

        $this->module->sendRedirect('statuses', ['error' => $this->lang['module.error.status_not_found']]);
    }
}
