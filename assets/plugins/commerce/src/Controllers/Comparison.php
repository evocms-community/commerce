<?php

use Commerce\Controllers\Traits;

class ComparisonDocLister extends CustomLangDocLister
{
    use Traits\PrepareTrait;

    protected $compareTV = [];

    public function __construct($modx, $cfg = [], $startTime = null)
    {
        $cfg = $this->initializePrepare($cfg);

        $this->priceField = $modx->commerce->getSetting('price_field', 'price');
        $cfg['tvList']    = $this->priceField . (!empty($cfg['tvList']) ? ',' . $cfg['tvList'] : '');
        $this->priceField = (isset($cfg['tvPrefix']) ? $cfg['tvPrefix'] : 'tv.') . $this->priceField;

        $cfg['prepare'][] = [$this, 'prepareRow'];

        parent::__construct($modx, $cfg, $startTime);

        $this->compareTV = $this->getCompareTV();

        if (!empty($this->compareTV)) {
            $this->config->setConfig([
                'tvList' => implode(',', array_column($this->compareTV, 'name')) . (!empty($cfg['tvList']) ? ',' . $cfg['tvList'] : ''),
            ]);
        }
    }

    public function prepareRow($data, $modx, $DL, $eDL)
    {
        $data[$this->priceField] = ci()->currency->format($data[$this->priceField]);
        return $data;
    }

    protected function getCompareTV()
    {
        foreach (['tvCategory', 'includeTV', 'excludeTV'] as $param) {
            $val = trim($this->getCFGDef($param, ''), " \t,");

            if (!empty($val)) {
                $$param = array_map('trim', explode(',', $val));
            }
        }

        $where = [];

        if (!empty($tvCategory)) {
            $where[] = "`category` IN ('" . implode("','", $tvCategory) . "')";
        }

        if (!empty($excludeTV)) {
            $where[] = "`name` NOT IN ('" . implode("','", $excludeTV) . "')";
        }

        if ($this->getCFGDef('checkBoundingList', 0)) {
            $query = $this->modx->db->select('id', $this->modx->getFullTablename('site_tmplvars'), "`name` = 'tovarparams'");

            if ($this->modx->db->getRecordCount($query)) {
                $tvid    = $this->modx->db->getValue($query);
                $docid   = $this->getCFGDef('category');
                $parents = $this->modx->getParentIds($docid);
                array_unshift($parents, $docid);
                $parents = array_values($parents);

                $query = $this->modx->db->select('*', $this->modx->getFullTablename('site_tmplvar_contentvalues'), "`contentid` IN ('" . implode("','", $parents) . "') AND `tmplvarid` = '$tvid'");

                if ($this->modx->db->getRecordCount($query)) {
                    $parents = array_flip($parents);

                    while ($row = $this->modx->db->getRow($query)) {
                        $value = json_decode($row['value']);

                        if ($value && !empty($value->fieldValue)) {
                            $parents[$row['contentid']] = $value->fieldValue;
                        }
                    }

                    foreach ($parents as $docid => $values) {
                        if (is_array($values)) {
                            $tvids = array_unique(array_column($values, 'param_id'));

                            if (!empty($tvids)) {
                                $where[] = "`id` IN ('" . implode("','", $tvids) . "')";
                            }

                            break;
                        }
                    }
                }
            }
        }

        $where = implode(' AND ', $where);

        if (!empty($includeTV)) {
            $where = "($where) OR `name` IN ('" . implode("','", $includeTV) . "')";
        }

        $where = 'WHERE (' . $where . ')';

        $templates = array_filter(\APIhelpers::cleanIDs($this->modx->commerce->getSetting('product_templates', '')));
        $templates = array_filter($templates, 'is_numeric');
        if (!empty($templates)) {
            $where = 'LEFT JOIN ' . $this->modx->getFullTablename('site_tmplvar_templates') . ' tt ON tt.tmplvarid = t.id AND tt.templateid = ' . $templates[0] . ' ' . $where . ' ORDER BY tt.rank' ;
        }

        $query  = $this->modx->db->query('SELECT * FROM ' . $this->modx->getFullTablename('site_tmplvars') . ' t ' . $where);
        $result = [];

        while ($row = $this->modx->db->getRow($query)) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    /**
     * @param string $tpl
     * @return string
     */
    public function _render($tpl = '')
    {
        $out = '';

        $this->toPlaceholders(count($this->_docs), 1, "display"); // [+display+] - сколько показано на странице.

        $i = 1;
        $sysPlh = $this->renameKeyArr($this->_plh, $this->getCFGDef("sysKey", "dl"));
        if (count($this->_docs) > 0) {
            $headerTpl = $this->getCFGDef('headerTpl');
            $footerTpl = $this->getCFGDef('footerTpl');
            $keyTpl    = $this->getCFGDef('keyTpl');
            $valueTpl  = $this->getCFGDef('valueTpl');
            $rowTpl    = $this->getCFGDef('rowTpl');
            $tvPrefix  = $this->getCFGDef('tvPrefix', 'tv.');
            $rows      = $this->getCFGDef('rows');

            $cells = [
                'header' => [
                    $this->parseChunk($keyTpl),
                ],
                'values' => [],
                'footer' => [
                    $this->parseChunk($keyTpl),
                ],
            ];

            foreach ($this->compareTV as $tvID => $tvRow) {
                $cells['values'][$tvID] = [
                    $this->parseChunk($keyTpl, $tvRow),
                ];
            }

            /**
             * @var $extUser user_DL_Extender
             */
            if ($extUser = $this->getExtender('user')) {
                $extUser->init($this, array('fields' => $this->getCFGDef("userFields", "")));
            }

            /**
             * @var $extSummary summary_DL_Extender
             */
            $extSummary = $this->getExtender('summary');

            /**
             * @var $extPrepare prepare_DL_Extender
             */
            $extPrepare = $this->getExtender('prepare');

            $this->skippedDocs = 0;
            foreach ($this->_docs as $item) {
                $this->renderTPL = $tpl;
                if ($extUser) {
                    $item = $extUser->setUserData($item); //[+user.id.createdby+], [+user.fullname.publishedby+], [+dl.user.publishedby+]....
                }

                $item['row'] = $rows[$item['id']];

                $item['summary'] = $extSummary ? $this->getSummary($item, $extSummary, 'introtext', 'content') : '';

                $item = array_merge(
                    $item,
                    $sysPlh
                ); //inside the chunks available all placeholders set via $modx->toPlaceholders with prefix id, and with prefix sysKey
                $item['iteration'] = $i; //[+iteration+] - Number element. Starting from zero

                $item['title'] = ($item['menutitle'] == '' ? $item['pagetitle'] : $item['menutitle']);

                if ($this->getCFGDef('makeUrl', 1)) {
                    if ($item['type'] == 'reference') {
                        $item['url'] = is_numeric($item['content']) ? $this->modx->makeUrl(
                            $item['content'],
                            '',
                            '',
                            $this->getCFGDef('urlScheme', '')
                        ) : $item['content'];
                    } else {
                        $item['url'] = $this->modx->makeUrl($item['id'], '', '', $this->getCFGDef('urlScheme', ''));
                    }
                }
                $date = $this->getCFGDef('dateSource', 'pub_date');
                if (isset($item[$date])) {
                    if (!$item[$date] && $date == 'pub_date' && isset($item['createdon'])) {
                        $date = 'createdon';
                    }
                    $_date = is_numeric($item[$date]) && $item[$date] == (int)$item[$date] ? $item[$date] : strtotime($item[$date]);
                    if ($_date !== false) {
                        $_date = $_date + $this->modx->config['server_offset_time'];
                        $dateFormat = $this->getCFGDef('dateFormat', 'd.m.Y H:i');
                        if ($dateFormat) {
                            $item['date'] = date($dateFormat, $_date);
                        }
                    }
                }

                $findTpl = $this->renderTPL;
                $tmp = $this->uniformPrepare($item, $i);
                extract($tmp, EXTR_SKIP);
                if ($this->renderTPL == '') {
                    $this->renderTPL = $findTpl;
                }

                if ($extPrepare) {
                    $item = $extPrepare->init($this, array(
                        'data'      => $item,
                        'nameParam' => 'prepare'
                    ));
                    if ($item === false) {
                        $this->skippedDocs++;
                        continue;
                    }
                }

                $cells['header'][] = $this->parseChunk($headerTpl, $item);
                $cells['footer'][] = $this->parseChunk($footerTpl, $item);

                foreach ($this->compareTV as $tvID => $tvRow) {
                    $cells['values'][$tvID][] = $this->parseChunk($valueTpl, array_merge($item, [
                        'tv'    => $tvRow,
                        'value' => $item[$tvPrefix . $tvRow['name']],
                    ]));
                }

                $i++;
            }

            $out .= $this->parseChunk($rowTpl, [
                'row' => implode($cells['header']),
            ]);

            foreach ($cells['values'] as $row) {
                $out .= $this->parseChunk($rowTpl, [
                    'row' => implode($row),
                ]);
            }

            $out .= $this->parseChunk($rowTpl, [
                'row' => implode($cells['footer']),
            ]);
        } else {
            $noneTPL = $this->getCFGDef('noneTPL', '');
            $out = ($noneTPL != '') ? $this->parseChunk($noneTPL, $sysPlh) : '';
        }

        $out = $this->renderWrap($out);

        return $this->toPlaceholders($out);
    }
}
