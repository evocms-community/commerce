<?php

namespace Commerce\Module\Controllers;

use Exception;

class StatusesController extends Controller implements \Commerce\Module\Interfaces\Controller
{
    protected $lang;

    protected $table = 'commerce_order_statuses';

    protected $icon = 'fa fa-play-circle';

    public function __construct($modx, $module)
    {
        parent::__construct($modx, $module);
        $this->lang = $this->modx->commerce->getUserLanguage('module');
        $this->table = $this->modx->getFullTableName($this->table);
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
        $list = ci()->statuses->getStatuses();

        return $this->view->render('statuses_list.tpl', [
            'list'   => $list,
            'custom' => $this->module->invokeTemplateEvent('OnManagerStatusesListRender', [
                'controller' => $this,
                'list'       => $list,
            ]),
        ]);
    }

    public function show()
    {
        $status_id = filter_input(INPUT_GET, 'status_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!empty($status_id)) {
            try {
                $status = ci()->statuses->getStatus($status_id);
                $status['color'] = !empty($status['color']) ? $status['color'] : 'FFFFFF';
            } catch (Exception $e) {
                $this->module->sendRedirect('statuses', ['error' => $this->lang['module.error.status_not_found']]);
            }
        } else {
            $status = ['color' => 'FFFFFF'];
        }

        return $this->view->render('status.tpl', [
            'status' => $status,
            'custom' => $this->module->invokeTemplateEvent('OnManagerStatusRender', [
                'controller' => $this,
                'status'     => $status,
            ]),
        ]);
    }

    public function save()
    {
        $db = ci()->db;
        $status_id = filter_input(INPUT_POST, 'status_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!empty($status_id)) {
            try {
                $status = ci()->statuses->getStatus($status_id);
            } catch (Exception $e) {
                $this->module->sendRedirect('statuses', ['error' => $this->lang['module.error.status_not_found']]);
            }
        } else {
            $status = [];
        }

        $data = $_POST;
        $data['color'] = !empty($data['color']) && is_scalar($data['color']) && preg_match('/^[0-9a-fA-F]{6}$/',
            $data['color']) ? $data['color'] : 'FFFFFF';

        $result = $this->modx->commerce->validate($data, [
            'title' => [
                'lengthBetween' => [
                    'params'  => [2, 255],
                    'message' => 'title should be between 2 and 255 symbols',
                ],
            ],
            'alias' => [
                'matches' => [
                    'params'  => '/^[0-9a-z\._]{2,255}$/',
                    'message' => 'alias can contain digits, symbols "a"-"z", "." and "_" and should be between 2 and 255 symbols',
                ],
            ],
        ]);

        if (is_array($result)) {
            $this->module->sendRedirectBack(['validation_errors' => $result]);
        }

        $fields = [
            'title'     => $db->escape($data['title']),
            'alias'     => $db->escape($data['alias']),
            'color'     => $db->escape(strtoupper($data['color'])),
            'notify'    => !empty($data['notify']) ? 1 : 0,
            'canbepaid' => !empty($data['canbepaid']) ? 1 : 0,
            'default'   => !empty($data['default']) ? 1 : 0,
        ];

        if ($fields['default'] == 0) {
            $query = $db->select('*', $this->table,
                "`default` = 1" . (!empty($status['id']) ? " AND `id` != '" . $status['id'] . "'" : ''));

            if (!$db->getRecordCount($query)) {
                $this->module->sendRedirectBack(['error' => 'default status should be defined']);
            }
        }

        try {
            if ($db->begin(0, 'Commerce') === false) {
                throw new Exception("Cannot begin transaction!");
            }

            if (!empty($status['id'])) {
                if (!$db->update($fields, $this->table, "`id` = '" . $status['id'] . "'")) {
                    throw new Exception("Cannot update status [" . print_r($status['id'],
                            true) . "]!\n" . print_r($fields, true));
                }
            } else {
                $status['id'] = $db->insert($fields, $this->table);

                if (!$status['id']) {
                    throw new Exception("Cannot insert status row!\n" . print_r($fields, true));
                }
            }
            ci()->cache->forget('statuses');

            if ($fields['default'] == 1) {
                if (!$db->update(['default' => 0], $this->table, "`id` != '" . $status['id'] . "'")) {
                    throw new Exception("Cannot change default status!");
                }

                $this->modx->clearCache('full');
            }

            if ($db->commit() === false) {
                throw new Exception("Cannot commit transaction!");
            }
        } catch (Exception $e) {
            $db->rollback();
            $this->modx->logEvent(0, 3, 'Saving error:<br><pre>' . $e->getMessage() . '</pre>', 'Commerce');
            $this->module->sendRedirectBack(['error' => $e->getMessage()]);
        }

        $this->module->sendRedirect('statuses', ['success' => $this->lang['module.status_saved']]);
    }

    public function delete()
    {
        $status_id = filter_input(INPUT_GET, 'status_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!empty($status_id)) {
            $db = ci()->db;
            try {
                $row = ci()->statuses->getStatus($status_id);
                if (!empty($row)) {
                    if ($row['default'] == 1) {
                        $this->module->sendRedirect('statuses',
                            ['error' => $this->lang['module.error.default_status_cannot_delete']]);
                    }

                    if ($db->delete($this->table, "`id` = '$status_id'")) {
                        ci()->cache->forget('statuses');
                        $this->module->sendRedirect('statuses', ['success' => $this->lang['module.status_deleted']]);
                    }
                }
            } catch (Exception $e) {
                $this->module->sendRedirect('statuses', ['error' => $this->lang['module.error.status_not_found']]);
            }
        }
    }
}
