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
        $columns = $this->getOrdersListColumns();

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

        $columns   = $this->sortFields($columns);
        $config    = $this->injectPrepare($config, $columns);
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

        $groups  = $this->getOrderGroups();
        $columns = $this->getOrderCartColumns();

        $this->modx->invokeEvent('OnManagerBeforeOrderRender', [
            'groups'  => &$groups,
            'config'  => &$config,
            'columns' => &$columns,
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

        $query   = $this->modx->db->select('*', $this->modx->getFullTablename('commerce_order_history'), "`order_id` = '" . $order['id'] . "'", 'created_at DESC');
        $history = $this->modx->db->makeArray($query);

        return $this->view->render('order.tpl', [
            'order'    => $order,
            'groups'   => $groups,
            'cartData' => json_decode($cartData, true),
            'columns'  => $columns,
            'statuses' => $this->getStatuses(),
            'history'  => $history,
        ]);
    }

    public function save()
    {
        return true;
    }

    private function getStatuses()
    {
        if (is_null($this->statuses)) {
            $query = $this->modx->db->select('id, title', $this->modx->getFullTablename('commerce_order_statuses'));
            $this->statuses = [];

            while ($row = $this->modx->db->getRow($query)) {
                $this->statuses[$row['id']] = $row['title'];
            }
        }

        return $this->statuses;
    }

    private function sortFields($fields)
    {
        uasort($fields, function($a, $b) {
            return $a['sort'] - $b['sort'];
        });

        return $fields;
    }

    private function processFields($fields, $args)
    {
        $data = reset($args);
        $result = [];

        foreach ($fields as $name => $field) {
            if (isset($field['content'])) {
                if (is_string($field['content']) && isset($data[$field['content']])) {
                    $result[$name] = $data[$field['content']];
                    continue;
                }

                if (is_callable($field['content'])) {
                    $result[$name] = call_user_func_array($field['content'], $args);
                    continue;
                }
            }

            $result[$name] = '';
        }

        return $result;
    }

    private function injectPrepare($config, $fields)
    {
        if (isset($config['prepare'])) {
            if (!is_array($config['prepare'])) {
                $config['prepare'] = explode(',', $config['prepare']);
            }
        } else {
            $config['prepare'] = [];
        }

        $index = 0;

        $config['prepare'][] = function($data, $modx, $DL, $eDL) use ($fields, &$index) {
            $data['fields']    = json_decode($data['fields'], true);
            $data['index']     = $index;
            $data['iteration'] = ++$index;
            $data['cells']     = $this->processFields($fields, compact('data', 'DL', 'eDL'));
            return $data;
        };

        return $config;
    }

    private function getOrdersListColumns()
    {
        $statuses = $this->getStatuses();

        return [
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
    }

    private function getOrderGroups()
    {
        $statuses = $this->getStatuses();

        return [
            'order_info' => [
                'title' => $this->lang['order.order_info'],
                'width' => '33.333%',
                'fields' => [
                    'id' => [
                        'title'   => $this->lang['order.order_id'],
                        'content' => function($data) {
                            return '<strong>#' . $data['id'] . '</strong>';
                        },
                        'sort' => 10,
                    ],
                    'date' => [
                        'title'   => $this->lang['order.created_at'],
                        'content' => function($data) {
                            return (new \DateTime($data['created_at']))->format('d.m.Y H:i:s');
                        },
                        'sort' => 20,
                    ],
                    'status' => [
                        'title'   => $this->lang['order.status_title'],
                        'content' => function ($data) use ($statuses) {
                            return isset($statuses[$data['status_id']]) ? $statuses[$data['status_id']] : '';
                        },
                        'sort' => 30,
                    ],
                ],
            ],
            'contact' => [
                'title' => $this->lang['order.contact_group_title'],
                'width' => '33.333%',
                'fields' => [
                    'name' => [
                        'title'   => $this->lang['order.name_field'],
                        'content' => 'name',
                        'sort'    => 10,
                    ],
                    'phone' => [
                        'title'   => $this->lang['order.phone_field'],
                        'content' => 'phone',
                        'sort'    => 20,
                    ],
                    'email' => [
                        'title'   => $this->lang['order.email_field'],
                        'content' => function($data) {
                            if (!empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                                return '<a href="mailto:' . $data['email'] . '">' . $data['email'] . '</a>';
                            }

                            return '';
                        },
                        'sort' => 30,
                    ],
                ],
            ],
            'payment_delivery' => [
                'title' => $this->lang['order.payment_delivery_group_title'],
                'width' => '33.333%',
                'fields' => [
                    'amount' => [
                        'title'   => $this->lang['order.to_pay_title'],
                        'content' => function($data) {
                            return '<strong>' . $this->modx->commerce->formatPrice($data['amount']) . '</strong>';
                        },
                        'sort' => 10,
                    ],
                    'delivery' => [
                        'title' => $this->lang['order.delivery_title'],
                        'content' => function($data) {
                            return !empty($data['fields']['delivery_method_title']) ? $data['fields']['delivery_method_title'] : '';
                        },
                        'sort' => 20,
                    ],
                    'payment' => [
                        'title' => $this->lang['order.payment_title'],
                        'content' => function($data) {
                            return !empty($data['fields']['payment_method_title']) ? $data['fields']['payment_method_title'] : '';
                        },
                        'sort' => 30,
                    ],
                ],
            ],
        ];
    }

    private function getOrderCartColumns()
    {
        $lang = $this->modx->commerce->getUserLanguage('cart');

        return [
            'position' => [
                'title'   => '#',
                'content' => 'iteration',
                'style'   => 'width: 1%;',
                'sort'    => 10,
            ],
            'image' => [
                'title' => $lang['cart.image'],
                'content' => function($data, $DL, $eDL) {
                    $imageField = $DL->getCFGDef('imageField', 'image');

                    if (!empty($data[$imageField])) {
                        $image = $this->modx->getConfig('site_url') . $this->modx->runSnippet('phpthumb', [
                            'input' => $data[$imageField],
                            'options' => 'w=80,h=80,f=jpg,bg=FFFFFF,far=C'
                        ]);

                        return '<img src="' . $image . '" alt="">';
                    }

                    return '';
                },
                'sort' => 20,
            ],
            'title' => [
                'title'   => $lang['cart.item_title'],
                'content' => 'title',
                'sort'    => 30,
            ],
            'options' => [
                'title' => $lang['cart.item_options'],
                'content' => function($data, $DL, $eDL) {
                    if (!empty($data['options'])) {
                        return '<pre>' . json_encode($data['options'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</pre>';
                    }
                    return '';
                },
                'sort' => 40,
            ],
            'count' => [
                'title'   => $lang['cart.count'],
                'content' => 'count',
                'style'   => 'text-align: center;',
                'sort'    => 50,
            ],
            'price' => [
                'title'   => $lang['cart.item_price'],
                'content' => function($data, $DL, $eDL) {
                    return $this->modx->commerce->formatPrice($data['price']);
                },
                'style' => 'text-align: right; white-space: nowrap;',
                'sort' => 60,
            ],
            'summary' => [
                'title'   => $lang['cart.item_summary'],
                'content' => function($data, $DL, $eDL) {
                    return $this->modx->commerce->formatPrice($data['total']);
                },
                'style' => 'text-align: right; white-space: nowrap;',
                'sort' => 70,
            ],
        ];
    }
}
