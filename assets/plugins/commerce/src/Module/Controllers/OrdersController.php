<?php

namespace Commerce\Module\Controllers;

class OrdersController extends Controller implements \Commerce\Module\Interfaces\Controller
{
    use \Commerce\Module\CustomizableFieldsTrait;

    private $lang;

    protected $statuses = null;

    public function __construct($modx, $module)
    {
        parent::__construct($modx, $module);
        $this->lang = $this->modx->commerce->getUserLanguage('order');
    }

    public function registerRoutes()
    {
        return [
            'index'         => 'index',
            'edit'          => 'edit',
            'save'          => 'save',
            'get-tree'      => 'getTree',
            'view'          => 'view',
            'change-status' => 'changeStatus',
        ];
    }

    public function index()
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
            'custom'  => $this->module->invokeTemplateEvent('OnManagerOrdersListRender', [
                'orders' => $list,
            ]),
        ]);
    }

    public function view()
    {
        $order = $this->loadOrderFromRequest();

        if (empty($order)) {
            $this->module->sendRedirect('orders', ['error' => $this->lang['module.error.order_not_found']]);
        }

        $config     = $this->getDefaultDocListerCartConfig();
        $groups     = $this->getOrderGroups();
        $columns    = $this->getOrderCartColumns();
        $subcolumns = $this->getOrderSubtotalsColumns();

        $this->modx->invokeEvent('OnManagerBeforeOrderRender', [
            'order'      => &$order,
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

        $cart = $this->modx->commerce->loadProcessor()->getCart();

        $products = $this->modx->runSnippet('DocLister', array_merge($config, [
            'controller' => 'Cart',
            'dir'        => 'assets/plugins/commerce/src/Controllers/',
            'idType'     => 'documents',
            'documents'  => array_column($cart->getItems(), 'id'),
            'instance'   => 'order',
            'cart'       => $cart,
            'tree'       => 0,
            'api'        => 1,
        ]));

        $products = json_decode($products, true);

        $subcolumns = $this->sortFields($subcolumns);
        $subtotals  = [];
        $cart->getSubtotals($subtotals, $total);

        foreach ($subtotals as $i => $row) {
            $subtotals[$i]['cells'] = $this->processFields($subcolumns, ['data' => $row]);
        }

        $query   = $this->modx->db->select('*', $this->modx->getFullTablename('commerce_order_history'), "`order_id` = '" . $order['id'] . "'", 'created_at DESC');
        $history = $this->modx->db->makeArray($query);

        $users = [];

        if (!empty($history)) {
            $query = $this->modx->db->select('*', $this->modx->getFullTablename('user_attributes'), "`internalKey` IN (" . implode(',', array_column($history, 'user_id')) . ")");

            while ($row = $this->modx->db->getRow($query)) {
                $users[$row['internalKey']] = $row['fullname'];
            }
        }

        return $this->view->render('order.tpl', [
            'order'      => $order,
            'groups'     => $groups,
            'cartData'   => $products,
            'columns'    => $columns,
            'statuses'   => $this->getStatuses(),
            'subcolumns' => $subcolumns,
            'subtotals'  => $subtotals,
            'history'    => $history,
            'users'      => $users,
            'custom'     => $this->module->invokeTemplateEvent('OnManagerOrderRender', [
                'order'     => $order,
                'products'  => $products,
                'subtotals' => $subtotals,
            ]),
        ]);
    }

    public function edit()
    {
        $order = $this->loadOrderFromRequest();

        if (empty($order)) {
            $this->module->sendRedirect('orders', ['error' => $this->lang['module.error.order_not_found']]);
        }

        $lang = $this->modx->commerce->getUserLanguage('order');
        $this->view->setLang($lang);

        $config     = $this->getDefaultDocListerCartConfig();
        $fields     = $this->getOrderEditableFields();
        $columns    = $this->getOrderCartEditableColumns();
        $subcolumns = $this->getOrderSubtotalsEditableColumns();

        $this->modx->invokeEvent('OnManagerBeforeOrderEditRender', [
            'order'      => &$order,
            'fields'     => &$fields,
            'config'     => &$config,
            'columns'    => &$columns,
            'subcolumns' => &$subcolumns,
        ]);

        $fields = $this->sortFields($fields);
        $values = $this->processFields($fields, ['data' => $order]);

        array_walk($fields, function(&$item, $key) use ($values) {
            $item['content'] = $values[$key];
        });

        $cart    = $this->modx->commerce->loadProcessor()->getCart();
        $columns = $this->sortFields($columns);
        $config  = $this->injectPrepare($config, $columns);

        $products = $this->modx->runSnippet('DocLister', array_merge($config, [
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

        $products = json_decode($products, true);

        $documents = $this->getDocuments($order);

        $subcolumns = $this->sortFields($subcolumns);
        $subtotals  = [];
        $cart->getSubtotals($subtotals, $total);

        foreach ($subtotals as $i => $row) {
            $subtotals[$i]['cells'] = $this->processFields($subcolumns, ['data' => array_merge(['iteration' => $i + 1], $row)]);
        }

        $productBlank = [
            'id'        => '{%id%}',
            'iteration' => '{%iteration%}',
            'title'     => '{%pagetitle%}',
            'count'     => 1,
            'price'     => '{%tv_price%}',
            'currency'  => $order['currency'],
        ];

        $productBlank = array_merge($productBlank, [
            'cells' => $this->processFields($columns, [
                'data' => $productBlank,
                'DL'   => null,
                'eDL'  => null,
            ]),
        ]);

        $subtotalBlank = [
            'id'        => '',
            'iteration' => '{%iteration%}',
            'title'     => '',
            'price'     => '0',
            'currency'  => $order['currency'],
        ];

        $subtotalBlank = array_merge($subtotalBlank, [
            'cells' => $this->processFields($subcolumns, ['data' => $subtotalBlank]),
        ]);

        return $this->view->render('order_edit.tpl', [
            'order'         => $order,
            'fields'        => $fields,
            'products'      => $products,
            'columns'       => $columns,
            'subcolumns'    => $subcolumns,
            'subtotals'     => $subtotals,
            'productBlank'  => $productBlank,
            'subtotalBlank' => $subtotalBlank,
            'documents'     => $documents,
            'custom'        => $this->module->invokeTemplateEvent('OnManagerOrderEditRender', [
                'order'     => $order,
                'products'  => $products,
                'subtotals' => $subtotals,
            ]),
        ]);
    }

    public function save()
    {
        $order = $this->loadOrderFromRequest();

        if (empty($order)) {
            $this->module->sendRedirect('orders', ['error' => $this->lang['module.error.order_not_found']]);
        }

        $fields     = $this->getOrderEditableFields();
        $columns    = $this->getOrderCartEditableColumns();
        $subcolumns = $this->getOrderSubtotalsEditableColumns();

        $orderData = $orderCart = $orderSubtotals = [];

        if (!empty($_POST['order'])) {
            $orderData = $_POST['order'];

            if (!empty($orderData['cart'])) {
                unset($orderData['cart']);
            }

            if (!empty($orderData['subtotals'])) {
                unset($orderData['subtotals']);
            }
        }

        if (!empty($_POST['order']['cart'])) {
            $orderCart = $_POST['order']['cart'];
        }

        if (!empty($_POST['order']['subtotals'])) {
            $orderSubtotals = $_POST['order']['subtotals'];
        }

        $data = [
            'order' => [
                'data'  => &$orderData,
                'rules' => $this->collectRules($fields),
            ],
            'cart' => [
                'data'  => &$orderCart,
                'rules' => $this->collectRules($columns),
                'iterable' => true,
            ],
            'subtotals' => [
                'data'  => &$orderSubtotals,
                'rules' => $this->collectRules($subcolumns),
                'iterable' => true,
            ],
        ];

        $this->modx->invokeEvent('OnManagerBeforeOrderValidating', [
            'order' => $order,
            'data'  => &$data,
        ]);

        $errors = [];

        foreach ($data as $key => $row) {
            if (empty($row['iterable'])) {
                $row['data'] = [$row['data']];
            }

            $rowErrors = [];

            foreach ($row['data'] as $rowData) {
                $result = $this->modx->commerce->validate($rowData, $row['rules']);

                if (is_array($result)) {
                    $rowErrors = array_merge($rowErrors, $result);
                }
            }

            if (!empty($rowErrors)) {
                $errors = array_merge($errors, $rowErrors);
            }
        }

        $this->modx->invokeEvent('OnManagerOrderValidated', [
            'order'  => $order,
            'data'   => &$data,
            'errors' => &$errors,
        ]);

        if (!empty($errors)) {
            $this->module->sendRedirectWithQuery('orders/edit', 'order_id=' . $order['id'], ['validation_errors' => $errors]);
        }

        $fields = &$orderData[0]['fields'];

        $fields['delivery_method_title'] = '';

        if (!empty($fields['delivery_method'])) {
            $list = $this->modx->commerce->getDeliveries();

            if (isset($list[$fields['delivery_method']])) {
                $fields['delivery_method_title'] = $list[$fields['delivery_method']]['title'];
            }
        }

        $fields['payment_method_title'] = '';

        if (!empty($fields['payment_method'])) {
            $list = $this->modx->commerce->getPayments();

            if (isset($list[$fields['payment_method']])) {
                $fields['payment_method_title'] = $list[$fields['payment_method']]['title'];
            }
        }

        unset($fields);

        $processor = $this->modx->commerce->loadProcessor();

        $result = $processor->updateOrder($order['id'], [
            'values'    => $data['order']['data'][0],
            'items'     => $data['cart']['data'],
            'subtotals' => $data['subtotals']['data'],
        ]);

        if (!$result) {
            $this->module->sendRedirectWithQuery('orders/edit', 'order_id=' . $order['id'], ['error' => $this->lang['module.error.order_not_saved']]);
        }

        if (!empty($_POST['notify']) && !empty($order['email'])) {
            $tpl      = ci()->tpl;
            $lang     = $this->modx->commerce->getUserLanguage('order');
            $template = $this->modx->commerce->getSetting('order_changed', $this->modx->commerce->getUserLanguageTemplate('order_changed'));

            $order = $processor->loadOrder($order['id'], true);
            $processor->getCart();

            $subjectTpl = $lang['order.order_data_changed'];
            $preventSending = false;

            $templateData = [
                'order_id' => $order['id'],
                'order'    => $order,
            ];

            $this->modx->invokeEvent('OnBeforeCustomerNotifySending', [
                'reason'  => 'order_changed',
                'order'   => &$order,
                'subject' => &$subjectTpl,
                'body'    => &$template,
                'data'    => &$templateData,
                'prevent' => &$preventSending,
            ]);

            if (!$preventSending) {
                $body    = $tpl->parseChunk($template, $templateData, true);
                $subject = $tpl->parseChunk($subjectTpl, $templateData, true);

                $mailer = new \Helpers\Mailer($this->modx, [
                    'to'      => $order['email'],
                    'subject' => $subject,
                ]);

                $mailer->send($body);

                if (!empty($_POST['history_description'])) {
                    $description = trim($_POST['history_description']);
                } else {
                    $description = $this->lang['module.order_changed'];
                }

                $processor->addOrderHistory($order['id'], $order['status_id'], $description, $notify = true);
            }
        }

        $this->module->sendRedirectWithQuery('orders/view', 'order_id=' . $order['id'], ['success' => $this->lang['module.order_saved']]);
    }

    private function collectRules($fields)
    {
        $rules = [];

        foreach ($fields as $name => $field) {
            if (isset($field['rules'])) {
                $rules[$name] = $field['rules'];
            } else if (isset($field['!rules'])) {
                $rules['!' . $name] = $field['!rules'];
            }
        }

        return $rules;
    }

    public function getTree()
    {
        $order = $this->loadOrderFromRequest();
        $parent_id = filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return json_encode([
            'status' => 'success',
            'markup' => $this->view->render('selector_tree.tpl', [
                'rows' => $this->getDocuments($order, $parent_id),
            ]),
        ], JSON_UNESCAPED_UNICODE);
    }

    private function getDocuments($order, $parent_id = 0)
    {
        $config = [
            'tvList' => ['image', 'price'],
            'api'    => ['id', 'pagetitle', 'isfolder', 'tv.image', 'tv.price'],
        ];

        $this->modx->invokeEvent('OnManagerBeforeSelectorLevelRender', [
            'config' => &$config,
            'order'  => $order,
        ]);

        if (!isset($config['prepare'])) {
            $config['prepare'] = [];
        } else if (!is_array($config['prepare']) && !is_callable($config['prepare'])) {
            $config['prepare'] = [$config['prepare']];
        }

        $currency = ci()->currency;

        $config['prepare'][] = function($data, $modx, $DL, $eDL) use ($order, $currency) {
            if (!empty($data['tv.price'])) {
                $data['tv.price'] = $currency->convertFromDefault($data['tv.price'], $order['currency']);
            }

            return $data;
        };

        $result = $this->modx->runSnippet('DocLister', array_merge($config, [
            'depth'   => 0,
            'idType'  => 'parents',
            'parents' => $parent_id,
            'orderBy' => 'menuindex ASC',
        ]));

        $result = json_decode($result, true);

        if (!is_array($result)) {
            $result = [];
        }

        return $result;
    }

    private function loadOrderFromRequest()
    {
        $type = !empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' ? INPUT_POST : INPUT_GET;
        $order_id = filter_input($type, 'order_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!empty($order_id)) {
            $processor = $this->modx->commerce->loadProcessor();
            return $processor->loadOrder($order_id);
        }

        return null;
    }

    public function changeStatus()
    {
        $data = array_merge($_POST, $_GET);

        $result = $this->modx->commerce->validate($data, [
            'order_id' => [
                'numeric' => 'status_id should be numeric',
            ],
            'status_id' => [
                'numeric' => 'status_id should be numeric',
            ],
            '!description' => [
                'string' => 'description should be string',
            ],
        ]);

        if (is_array($result)) {
            $this->module->sendRedirectBack(['validation_errors' => $result]);
        }

        $processor = $this->modx->commerce->loadProcessor();
        $order = $processor->loadOrder($data['order_id']);

        if (empty($order)) {
            $this->module->sendRedirectBack(['error' => $this->lang['module.error.order_not_found']]);
        }

        $shouldNotify = false;

        if (isset($data['notify'])) {
            $shouldNotify = !empty($data['notify']);
        } else {
            $query = $this->modx->db->select('notify', $this->modx->getFullTablename('commerce_order_statuses'), "`id` = '" . $this->modx->db->escape($data['status_id']) . "'");
            $shouldNotify = !empty($this->modx->db->getValue($query));
        }

        $processor->changeStatus($order['id'], $data['status_id'], !empty($data['description']) ? $data['description'] : '', $shouldNotify);

        $this->module->sendRedirectBack(['success' => $this->lang['module.status_changed']]);
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

    private function getDefaultDocListerCartConfig()
    {
        return [
            'imageField' => 'tv.image',
            'tvList'     => 'image',
        ];
    }

    private function getOrdersListColumns()
    {
        $statuses = $this->getStatuses();
        $defaultCurrency = ci()->currency->getDefaultCurrencyCode();

        return [
            'id' => [
                'title'   => '#',
                'content' => 'id',
                'sort'    => 0,
                'style'   => 'width: 1%; text-align: center;',
            ],
            'date' => [
                'title'   => $this->lang['order.created_at'],
                'content' => function($data, $DL, $eDL) {
                    return (new \DateTime($data['created_at']))->format('d.m.Y H:i:s');
                },
                'sort' => 10,
            ],
            'name' => [
                'title'   => $this->lang['order.name_field'],
                'content' => 'name',
                'sort'    => 20,
            ],
            'phone' => [
                'title'   => $this->lang['order.phone_field'],
                'content' => 'phone',
                'sort'    => 30,
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
                'sort'    => 40,
                'style'   => 'white-space: nowrap;',
            ],
            'amount' => [
                'title'   => $this->lang['order.amount_title'],
                'content' => function($data, $DL, $eDL) use ($defaultCurrency) {
                    $currency = ci()->currency;
                    $out = $currency->format($data['amount'], $data['currency']);

                    if ($data['currency'] != $defaultCurrency) {
                        $out .= '<br>(' . $currency->formatWithDefault($data['amount'], $data['currency']) . ')';
                    }

                    return $out;
                },
                'style'   => 'text-align: right;',
                'sort'    => 50,
                'style'   => 'white-space: nowrap; text-align: right;',
            ],
            'delivery' => [
                'title' => $this->lang['order.delivery_title'],
                'content' => function($data, $DL, $eDL) {
                    return !empty($data['fields']['delivery_method_title']) ? $data['fields']['delivery_method_title'] : '';
                },
                'sort' => 60,
            ],
            'payment' => [
                'title' => $this->lang['order.payment_title'],
                'content' => function($data, $DL, $eDL) {
                    return !empty($data['fields']['payment_method_title']) ? $data['fields']['payment_method_title'] : '';
                },
                'sort' => 70,
            ],
            'status' => [
                'title' => $this->lang['order.status_title'],
                'content' => function($data, $DL, $eDL) use ($statuses) {
                    $out = '';

                    foreach ($statuses as $id => $title) {
                        $out .= '<option value="' . $id . '"' . ($id == $data['status_id'] ? ' selected' : '') . '>' . $title . '</option>';
                    }

                    return '<select name="status_id" onchange="location = \'' . $this->module->makeUrl('orders/change-status', 'order_id=' . $data['id'] . '&status_id=') . '\' + jQuery(this).val();">' . $out . '</select>';
                },
                'sort' => 80,
            ],
        ];
    }

    private function getOrderGroups()
    {
        $statuses = $this->getStatuses();
        $defaultCurrency = ci()->currency->getDefaultCurrencyCode();

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
                        'content' => function($data) use ($defaultCurrency) {
                            $currency = ci()->currency;
                            $out = $currency->format($data['amount'], $data['currency']);

                            if ($data['currency'] != $defaultCurrency) {
                                $out .= '<br>(' . $currency->formatWithDefault($data['amount'], $data['currency']) . ')';
                            }

                            return '<strong>' . $out . '</strong>';
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
        $lang = ci()->commerce->getUserLanguage('cart');
        $order = ci()->commerce->loadProcessor()->getOrder();
        $defaultCurrency = ci()->currency->getDefaultCurrencyCode();

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
                'content' => function($data, $DL, $eDL) {
                    $url = $this->modx->makeUrl($data['id']);
                    return '<a href="' . $url . '" target="_blank">' . $data['title'] . '</a>';
                },
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
                'content' => function($data, $DL, $eDL) use ($order, $defaultCurrency) {
                    $currency = ci()->currency;
                    $out = $currency->format($data['price'], $order['currency']);

                    if ($order['currency'] != $defaultCurrency) {
                        $out .= '<br>(' . $currency->formatWithDefault($data['price'], $order['currency']) . ')';
                    }

                    return $out;
                },
                'style' => 'text-align: right; white-space: nowrap;',
                'sort' => 60,
            ],
            'summary' => [
                'title'   => $lang['cart.item_summary'],
                'content' => function($data, $DL, $eDL) use ($order, $defaultCurrency) {
                    $currency = ci()->currency;
                    $out = $currency->format($data['total'], $order['currency']);

                    if ($order['currency'] != $defaultCurrency) {
                        $out .= '<br>(' . $currency->formatWithDefault($data['total'], $order['currency']) . ')';
                    }

                    return $out;
                },
                'style' => 'text-align: right; white-space: nowrap;',
                'sort' => 70,
            ],
        ];
    }

    private function getOrderSubtotalsColumns()
    {
        $commerce = ci()->commerce;
        $currency = ci()->currency;
        $lang  = $commerce->getUserLanguage('cart');
        $order = $commerce->loadProcessor()->getOrder();
        $defaultCurrency = $currency->getDefaultCurrencyCode();

        return [
            'title' => [
                'title'   => $lang['cart.item_title'],
                'content' => 'title',
                'sort'    => 10,
            ],
            'price' => [
                'title'   => $lang['cart.item_price'],
                'content' => function($data) use ($order, $currency, $defaultCurrency) {
                    $currency = ci()->currency;
                    $out = $currency->format($data['price'], $order['currency']);

                    if ($order['currency'] != $defaultCurrency) {
                        $out .= '<br>(' . $currency->formatWithDefault($data['price'], $order['currency']) . ')';
                    }

                    return $out;
                },
                'style' => 'text-align: right; white-space: nowrap;',
                'sort' => 20,
            ],
        ];
    }

    private function getOrderEditableFields()
    {
        return [
            'name' => [
                'title'   => $this->lang['order.name_field'],
                'content' => function($data) {
                    return '<input type="text" class="form-control" name="order[name]" value="' . htmlentities($data['name']) . '">';
                },
                '!rules'   => [
                    'lengthBetween' => [
                        'params'  => [2, 255],
                        'message' => $this->lang['module.error.name_length'],
                    ],
                ],
                'sort'    => 10,
            ],
            'phone' => [
                'title'   => $this->lang['order.phone_field'],
                'content' => function($data) {
                    return '<input type="text" class="form-control" name="order[phone]" value="' . htmlentities($data['phone']) . '">';
                },
                'sort'    => 20,
            ],
            'email' => [
                'title'   => $this->lang['order.email_field'],
                'content' => function($data) {
                    return '<input type="text" class="form-control" name="order[email]" value="' . htmlentities($data['email']) . '">';
                },
                '!rules'   => [
                    'email'    => $this->lang['module.error.email_incorrect'],
                ],
                'sort' => 30,
            ],
            'delivery_method' => [
                'title' => $this->lang['order.delivery_title'],
                'content' => function($data) {
                    $list = ci()->commerce->getDeliveries();
                    $out = '<option value="">' . $this->lang['module.not_selected'] . '</option>';

                    foreach ($list as $name => $row) {
                        $out .= '<option value="' . $name . '"' . (isset($data['fields']['delivery_method']) && $name == $data['fields']['delivery_method'] ? ' selected' : '') . '>' . $row['title'] . '</option>';
                    }

                    return '<select class="form-control" name="order[fields][delivery_method]">' . $out . '</select>';
                },
                'sort' => 40,
            ],
            'payment_method' => [
                'title' => $this->lang['order.payment_title'],
                'content' => function($data) {
                    $list = ci()->commerce->getPayments();
                    $out = '<option value="">' . $this->lang['module.not_selected'] . '</option>';

                    foreach ($list as $name => $row) {
                        $out .= '<option value="' . $name . '"' . (isset($data['fields']['payment_method']) && $name == $data['fields']['payment_method'] ? ' selected' : '') . '>' . $row['title'] . '</option>';
                    }

                    return '<select class="form-control" name="order[fields][payment_method]">' . $out . '</select>';
                },
                'sort' => 50,
            ],
        ];
    }

    private function getOrderCartEditableColumns()
    {
        $lang  = ci()->commerce->getUserLanguage('cart');
        $order = ci()->commerce->loadProcessor()->getOrder();

        return [
            'number' => [
                'title'   => '#',
                'content' => 'iteration',
                'style'   => 'width: 1%; text-align: center;',
                'sort'    => 10,
            ],
            'id' => [
                'title'   => 'ID',
                'content' => 'id',
                'style'   => 'width: 1%; text-align: center;',
                'sort'    => 10,
            ],
            'title' => [
                'title'   => $lang['cart.item_title'],
                'content' => function($data, $DL, $eDL) {
                    return '
                        <input type="hidden" name="order[cart][' . $data['iteration'] . '][order_row_id]" value="' . htmlentities($data['order_row_id']) . '">
                        <input type="hidden" name="order[cart][' . $data['iteration'] . '][id]" value="' . htmlentities($data['id']) . '">
                        <input type="text" class="form-control" name="order[cart][' . $data['iteration'] . '][title]" value="' . htmlentities($data['title']) . '">
                    ';
                },
                'rules'   => [
                    'required' => $this->lang['module.error.producttitle_required'],
                    'lengthBetween' => [
                        'params'  => [2, 255],
                        'message' => $this->lang['module.error.producttitle_length'],
                    ],
                ],
                'sort'    => 20,
            ],
            'count' => [
                'title'   => $lang['cart.count'],
                'content' => function($data, $DL, $eDL) {
                    return '
                        <input type="text" class="form-control" name="order[cart][' . $data['iteration'] . '][count]" value="' . htmlentities($data['count']) . '">
                    ';
                },
                'rules'   => [
                    'required' => $this->lang['module.error.productcount_required'],
                    'greater' => [
                        'params'  => 0,
                        'message' => $this->lang['module.error.productcount_positive'],
                    ],
                ],
                'style'   => 'width: 10%; text-align: right;',
                'sort'    => 30,
            ],
            'price' => [
                'title'   => $lang['cart.item_price'],
                'content' => function($data, $DL, $eDL) use ($order) {
                    return '
                        <div style="white-space: nowrap;">
                            <input type="text" class="form-control" name="order[cart][' . $data['iteration'] . '][price]" value="' . htmlentities($data['price']) . '" style="width: 80px; text-align: right;">
                            ' . $order['currency'] . '
                        </div>
                    ';
                },
                'rules'   => [
                    'required' => $this->lang['module.error.productprice_required'],
                    'greater' => [
                        'params'  => 0,
                        'message' => $this->lang['module.error.productprice_positive'],
                    ],
                ],
                'sort'    => 30,
                'style'   => 'white-space: nowrap; width: 10%; text-align: right;',
            ],
        ];
    }

    private function getOrderSubtotalsEditableColumns()
    {
        $lang  = ci()->commerce->getUserLanguage('cart');
        $order = ci()->commerce->loadProcessor()->getOrder();

        return [
            'number' => [
                'title'   => '#',
                'content' => 'iteration',
                'sort'    => 10,
                'style'   => 'width: 1%;',
            ],
            'title' => [
                'title'   => $lang['cart.item_title'],
                'content' => function($data) {
                    return '
                        <input type="hidden" name="order[subtotals][' . $data['iteration'] . '][id]" value="' . htmlentities($data['id']) . '">
                        <input type="text" class="form-control" name="order[subtotals][' . $data['iteration'] . '][title]" value="' . htmlentities($data['title']) . '">
                    ';
                },
                'rules'   => [
                    'required' => $this->lang['module.error.subtotaltitle_required'],
                    'lengthBetween' => [
                        'params'  => [2, 255],
                        'message' => $this->lang['module.error.subtotaltitle_length'],
                    ],
                ],
                'sort'    => 20,
            ],
            'price' => [
                'title'   => $lang['cart.item_price'],
                'content' => function($data) use ($order) {
                    return '
                        <div style="white-space: nowrap;">
                            <input type="text" class="form-control" name="order[subtotals][' . $data['iteration'] . '][price]" value="' . htmlentities($data['price']) . '" style="width: 80px; text-align: right;">
                            ' . $order['currency'] . '
                        </div>
                    ';
                },
                'sort'    => 30,
                'style'   => 'width: 10%; text-align: right;',
            ],
        ];
    }
}
