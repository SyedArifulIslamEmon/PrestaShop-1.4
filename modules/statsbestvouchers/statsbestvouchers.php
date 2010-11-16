<?php

/**
  * Statistics
  * @category stats
  *
  * @author PrestaShop
  * @copyright PrestaShop
  * @license http://www.opensource.org/licenses/osl-3.0.php Open-source licence 3.0
  * @version 1.4
  */
  
if (!defined('_CAN_LOAD_FILES_'))
	exit;

class StatsBestVouchers extends ModuleGrid
{
	private $_html = null;
	private $_query =  null;
	private $_columns = null;
	private $_defaultSortColumn = null;
	private $_emptyMessage = null;
	private $_pagingMessage = null;
	
	function __construct()
	{
		$this->name = 'statsbestvouchers';
		$this->tab = 'analytics_stats';
		$this->version = 1.0;
		
		$this->_defaultSortColumn = 'ca';
		$this->_emptyMessage = $this->l('Empty recordset returned');
		$this->_pagingMessage = $this->l('Displaying').' {0} - {1} '.$this->l('of').' {2}';
		
		$this->_columns = array(
			array(
				'id' => 'name',
				'header' => $this->l('Name'),
				'dataIndex' => 'name',
				'align' => 'left',
				'width' => 300
			),
			array(
				'id' => 'ca',
				'header' => $this->l('Sales'),
				'dataIndex' => 'ca',
				'width' => 30,
				'align' => 'right'
			),
			array(
				'id' => 'total',
				'header' => $this->l('Total used'),
				'dataIndex' => 'total',
				'width' => 30,
				'align' => 'right'
			)
		);
		
		parent::__construct();
		
		$this->displayName = $this->l('Best vouchers');
		$this->description = $this->l('A list of the best vouchers');
	}
	
	public function install()
	{
		return (parent::install() AND $this->registerHook('AdminStatsModules'));
	}
	
	public function hookAdminStatsModules($params)
	{
		$engineParams = array(
			'id' => 'id_product',
			'title' => $this->displayName,
			'columns' => $this->_columns,
			'defaultSortColumn' => $this->_defaultSortColumn,
			'emptyMessage' => $this->_emptyMessage,
			'pagingMessage' => $this->_pagingMessage
		);
	
		$this->_html = '
		<fieldset class="width3"><legend><img src="../modules/'.$this->name.'/logo.gif" /> '.$this->displayName.'</legend>
			'.ModuleGrid::engine($engineParams).'
		</fieldset>';
		return $this->_html;
	}
	
	public function getTotalCount()
	{
		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->GetRow('SELECT COUNT(`id_order_discount`) total FROM `'._DB_PREFIX_.'order_discount`');
		return $result['total'];
	}

	public function getData()
	{	
		$this->_totalCount = $this->getTotalCount();
		$this->_query = '
		SELECT od.name, COUNT(od.id_discount) as total, SUM(o.total_paid_real) / o.conversion_rate as ca
		FROM '._DB_PREFIX_.'order_discount od
		LEFT JOIN '._DB_PREFIX_.'orders o ON o.id_order = od.id_order
		WHERE o.valid = 1
		AND o.invoice_date BETWEEN '.$this->getDate().'
		GROUP BY od.id_discount';

		if (Validate::IsName($this->_sort))
		{
			$this->_query .= ' ORDER BY `'.$this->_sort.'`';
			if (isset($this->_direction))
				$this->_query .= ' '.$this->_direction;
		}
		if (($this->_start === 0 OR Validate::IsUnsignedInt($this->_start)) AND Validate::IsUnsignedInt($this->_limit))
			$this->_query .= ' LIMIT '.$this->_start.', '.($this->_limit);
		$this->_values = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS($this->_query);
	}
}