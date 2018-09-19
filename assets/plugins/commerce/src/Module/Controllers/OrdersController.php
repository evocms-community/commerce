<?php

namespace Commerce\Module\Controllers;

class OrdersController extends Controller
{
    private $lang;

    public function __construct($modx, $module)
    {
        parent::__construct($modx, $module);
        $this->lang = $this->modx->commerce->getUserLanguage('order');
    }

    public function showList()
    {
        $query = $this->modx->db->select('id, title', $this->modx->getFullTablename('commerce_order_statuses'));
        $statuses = [];

        while ($row = $this->modx->db->getRow($query)) {
            $statuses[$row['id']] = $row['title'];
        }

        $columns = [
            'id' => [
                'title'   => '#',
                'content' => 'id',
                'sort'    => 0,
                'style'   => 'width: 1%; text-align: center;',
            ],
            'name' => [
                'title'   => $this->lang['order.name_field'],
                'content' => 'name',
                'sort'    => 10,
            ],
            'phone' => [
                'title'   => $this->lang['order.phone_field'],
                'content' => 'phone',
                'sort'    => 20,
                'style'   => 'white-space: nowrap;',
            ],
            'email' => [
                'title'   => $this->lang['order.email_field'],
                'content' => function($data, $DL, $eDL) {
                    if (!empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                        return '<a href="mailto:' . $data['email'] . '">' . $data['email'] . '</a>';
                    }

                    return '';
                },
                'sort'    => 30,
                'style'   => 'white-space: nowrap;',
            ],
            'amount' => [
                'title'   => $this->lang['order.amount_title'],
                'content' => function($data, $DL, $eDL) {
                    return $this->modx->commerce->formatPrice($data['amount']);
                },
                'style'   => 'text-align: right;',
                'sort'    => 40,
                'style'   => 'white-space: nowrap; text-align: right;',
            ],
            'delivery' => [
                'title' => $this->lang['order.delivery_title'],
                'content' => function($data, $DL, $eDL) {
                    return !empty($data['fields']['delivery_method_title']) ? $data['fields']['delivery_method_title'] : '';
                },
                'sort' => 50,
            ],
            'payment' => [
                'title' => $this->lang['order.payment_title'],
                'content' => function($data, $DL, $eDL) {
                    return !empty($data['fields']['payment_method_title']) ? $data['fields']['payment_method_title'] : '';
                },
                'sort' => 60,
            ],
            'status' => [
                'title' => $this->lang['order.status_title'],
                'content' => function($data, $DL, $eDL) use ($statuses) {
                    return isset($statuses[$data['status_id']]) ? $statuses[$data['status_id']] : '';
                },
                'sort' => 70,
            ],
        ];

        $config = [
            'orderBy'         => 'created_at DESC',
            'display'         => 10,
            'paginate'        => 'pages',
            'TplWrapPaginate' => '@CODE:<ul class="[+class+]">[+wrap+]</ul>',
            'TplCurrentPage'  => '@CODE:<li class="page-item active"><span class="page-link">[+num+]</span></li>',
            'TplPage'         => '@CODE:<li class="page-item"><a href="[+link+]" class="page-link page" data-page="[+num+]">[+num+]</a></li>',
            'TplNextP'        => '@CODE:',
            'TplPrevP'        => '@CODE:',
        ];

        $this->modx->invokeEvent('OnManagerBeforeOrdersListRender', [
            'config'  => &$config,
            'columns' => &$columns,
        ]);

        uasort($columns, function($a, $b) {
            return $a['sort'] - $b['sort'];
        });

        if (isset($config['prepare'])) {
            if (!is_array($config['prepare'])) {
                $config['prepare'] = explode(',', $config['prepare']);
            }
        } else {
            $config['prepare'] = [];
        }

        $index = 0;

        $config['prepare'][] = function($data, $DL, $eDL) use ($columns, &$index) {
            $cells = [];
            
            $data['fields']    = json_decode($data['fields'], true);
            $data['index']     = $index;
            $data['iteration'] = ++$index;

            foreach ($columns as $name => $column) {
                if (isset($column['content'])) {
                    if (is_string($column['content']) && isset($data[$column['content']])) {
                        $cells[$name] = $data[$column['content']];
                        continue;
                    }

                    if (is_callable($column['content'])) {
                        $cells[$name] = call_user_func($column['content'], $data, $DL, $eDL);
                        continue;
                    }
                }

                $cells[$name] = '';
            }

            $data['cells'] = $cells;
            return $data;
        };

        $ordersUrl = $this->module->makeUrl('orders');

        $list = $this->modx->runSnippet('DocLister', array_merge($config, [
            'controller'      => 'onetable',
            'table'           => 'commerce_orders',
            'idType'          => 'documents',
            'id'              => 'list',
            'showParent'      => '-1',
            'api'             => 1,
            'ignoreEmpty'     => 1,
            'makePaginateUrl' => function($link, $modx, $DL, $pager) use ($ordersUrl) {
                return $ordersUrl;
            },
        ]));

        $list = json_decode($list, true);
        
        return $this->view->render('orders_list.tpl', [
            'columns' => $columns,
            'orders'  => $list,
        ]);
    }

    public function show()
    {
        $order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!empty($order_id)) {
            $query = $this->modx->db->select('*', $this->modx->getFullTablename('commerce_orders'), "`id` = '$order_id'");
            $order = $this->modx->db->getRow($query);
        }

        if (empty($order)) {
            $this->module->flash->set('error', $this->lang['module.error.order_not_found']);
            $this->module->sendRedirect('orders');
        }

        return $this->view->render('order.tpl', [
            'order' => $order,
        ]);
    }

    public function save()
    {
        return true;
    }
}
