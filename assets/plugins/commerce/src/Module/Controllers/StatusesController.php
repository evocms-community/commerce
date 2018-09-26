<?php

namespace Commerce\Module\Controllers;

class StatusesController extends Controller
{
    private $lang;

    public function __construct($modx, $module)
    {
        parent::__construct($modx, $module);
        $this->lang = $this->modx->commerce->getUserLanguage('statuses');
    }

    public function showList()
    {
        $query = $this->modx->db->select('*', $this->modx->getFullTablename('commerce_order_statuses'), '', 'id ASC');

        return $this->view->render('statuses_list.tpl', [
            'list'   => $this->modx->db->makeArray($query),
            'custom' => $this->module->invokeTemplateEvent('OnManagerStatusesListRender'),
        ]);
    }

    public function show()
    {
        $order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        $processor = $this->modx->commerce->loadProcessor();
        $order = $processor->loadOrder($order_id);

        if (empty($order)) {
            $this->module->flash->set('error', $this->lang['module.error.order_not_found']);
            $this->module->sendRedirect('orders');
        }

        $config  = [
            'imageField' => 'tv.image',
            'tvList'     => 'image',
        ];

        $groups     = $this->getOrderGroups();
        $columns    = $this->getOrderCartColumns();
        $subcolumns = $this->getOrderSubtotalsColumns();

        $this->modx->invokeEvent('OnManagerBeforeOrderRender', [
            'groups'     => &$groups,
            'config'     => &$config,
            'columns'    => &$columns,
            'subcolumns' => &$subcolumns,
        ]);

        foreach ($groups as $group_id => &$group) {
            $group['fields'] = $this->sortFields($group['fields']);

            $values = $this->processFields($group['fields'], ['data' => $order]);

            array_walk($group['fields'], function(&$item, $key) use ($values) {
                $item['value'] = $values[$key];
            });
        }

        unset($group);

        $columns = $this->sortFields($columns);
        $config  = $this->injectPrepare($config, $columns);

        $cart = $processor->getCart();
        $cartData = $this->modx->runSnippet('DocLister', array_merge($config, [
            'controller' => 'Cart',
            'dir'        => 'assets/plugins/commerce/src/Controllers/',
            'sortType'   => 'doclist',
            'idType'     => 'documents',
            'documents'  => array_column($cart->getItems(), 'id'),
            'instance'   => 'order',
            'cart'       => $cart,
            'tree'       => 0,
            'api'        => 1,
        ]));

        $subcolumns = $this->sortFields($subcolumns);
        $subtotals  = [];
        $cart->getSubtotals($subtotals, $total);

        foreach ($subtotals as $i => $row) {
            $subtotals[$i]['cells'] = $this->processFields($subcolumns, ['data' => $row]);
        }

        $query   = $this->modx->db->select('*', $this->modx->getFullTablename('commerce_order_history'), "`order_id` = '" . $order['id'] . "'", 'created_at DESC');
        $history = $this->modx->db->makeArray($query);

        return $this->view->render('order.tpl', [
            'order'      => $order,
            'groups'     => $groups,
            'cartData'   => json_decode($cartData, true),
            'columns'    => $columns,
            'statuses'   => $this->getStatuses(),
            'subcolumns' => $subcolumns,
            'subtotals'  => $subtotals,
            'history'    => $history,
            'custom'     => $this->module->invokeTemplateEvent('OnManagerOrderRender'),
        ]);
    }
}
