<?php

if (!defined('PS_ADMIN_DIR')) define('PS_ADMIN_DIR', getcwd().'/..');
include_once(PS_ADMIN_DIR.'/../config/config.inc.php');
include_once(PS_ADMIN_DIR.'/init.php');

if (Tools::getValue('token') == Tools::getAdminToken('AdminReferrers'.intval(Tab::getIdFromClassName('AdminReferrers')).intval(Tools::getValue('id_employee'))))
{
	if (Tools::isSubmit('ajaxProductFilter'))
		Referrer::getAjaxProduct(intval(Tools::getValue('id_referrer')), intval(Tools::getValue('id_product')), new Employee(intval(Tools::getValue('id_employee'))));
	else if (Tools::isSubmit('ajaxFillProducts'))
	{
		$jsonArray = array();
		$result = Db::getInstance()->ExecuteS('
		SELECT p.id_product, pl.name
		FROM '._DB_PREFIX_.'product p
		LEFT JOIN '._DB_PREFIX_.'product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = '.intval(Tools::getValue('id_lang')).')
		'.(Tools::getValue('filter') != 'undefined' ? 'WHERE name LIKE "%'.pSQL(Tools::getValue('filter')).'%"' : ''));
		foreach ($result as $row)
			$jsonArray[] = '{id_product:'.intval($row['id_product']).',name:\''.addslashes($row['name']).'\'}';
		die ('['.implode(',', $jsonArray).']');
	}
}

include_once(dirname(__FILE__).'/AdminStats.php');

class AdminReferrers extends AdminTab
{
	public function __construct()
	{
	 	$this->table = 'referrer';
	 	$this->className = 'Referrer';
	 	$this->view = true;
	 	$this->edit = true;
		$this->delete = true;
		
		$this->_select = 'IF(cache_orders > 0, ROUND(cache_sales/cache_orders, 2), 0) as cart, (cache_orders*base_fee) as fee1, (cache_sales*percent_fee/100) as fee2';
		$this->fieldsDisplay = array(
			'id_referrer' => array('title' => $this->l('ID'), 'width' => 25, 'align' => 'center'),
			'name' => array('title' => $this->l('Name'), 'width' => 80),
			'cache_visitors' => array('title' => $this->l('Visitors'), 'width' => 40, 'align' => 'center'),
			'cache_visits' => array('title' => $this->l('Visits'), 'width' => 40, 'align' => 'center'),
			'cache_pages' => array('title' => $this->l('Pages'), 'width' => 40, 'align' => 'center'),
			'cache_registrations' => array('title' => $this->l('Reg.'), 'width' => 40, 'align' => 'center'),
			'cache_orders' => array('title' => $this->l('Orders'), 'width' => 40, 'align' => 'center'),
			'cache_sales' => array('title' => $this->l('Sales'), 'width' => 100, 'align' => 'right', 'prefix' => '<b>', 'suffix' => '</b>', 'price' => true),
			'cart' => array('title' => $this->l('Avg. cart'), 'width' => 60, 'align' => 'right', 'price' => true),
			'cache_reg_rate' => array('title' => $this->l('Reg. rate'), 'width' => 40, 'align' => 'center', 'suffix' => '%'),
			'cache_order_rate' => array('title' => $this->l('Order rate'), 'width' => 40, 'align' => 'center', 'suffix' => '%'),
			'fee' => array('title' => $this->l('Fee'), 'width' => 40, 'align' => 'right', 'price' => true),);
			
		parent::__construct();
	}

	private function enableCalendar()
	{
		return (!Tools::isSubmit('add'.$this->table) AND !Tools::isSubmit('submitAdd'.$this->table) AND !Tools::isSubmit('update'.$this->table));
	}
	
	public function display()
	{
		global $cookie, $currentIndex;
		
		$products = Product::getSimpleProducts(intval($cookie->id_lang));
		$productsArray = array();
		foreach ($products as $product)
			$productsArray[] = $product['id_product'];
		
		echo '
		<script type="text/javascript">
			var productIds = new Array(\''.implode('\',\'', $productsArray).'\');
			var referrerStatus = new Array();
			
			function newProductLine(id_referrer, result)
			{
				return \'\'+
				\'<tr id="trprid_\'+id_referrer+\'_\'+result.id_product+\'" style="background-color: rgb(255, 255, 187);">\'+
				\'	<td align="center">--</td>\'+
				\'	<td align="center">\'+result.id_product+\'</td>\'+
				\'	<td>\'+result.product_name+\'</td>\'+
				\'	<td align="center">\'+result.uniqs+\'</td>\'+
				\'	<td align="center">\'+result.visits+\'</td>\'+
				\'	<td align="center">\'+result.pages+\'</td>\'+
				\'	<td align="center">\'+result.registrations+\'</td>\'+
				\'	<td align="center">\'+result.orders+\'</td>\'+
				\'	<td align="right">\'+result.sales+\'</td>\'+
				\'	<td align="center">\'+result.reg_rate+\'</td>\'+
				\'	<td align="center">\'+result.order_rate+\'</td>\'+
				\'	<td align="center">\'+result.base_fee+\'</td>\'+
				\'	<td align="center">\'+result.percent_fee+\'</td>\'+
				\'	<td align="center">--</td>\'+
				\'</tr>\';
			}
			
			function showProductLines(id_referrer)
			{
				if (!referrerStatus[id_referrer])
				{
					referrerStatus[id_referrer] = true;
					for (var i = 0; i < productIds.length; ++i)
						$.getJSON("'.dirname($currentIndex).'/tabs/AdminReferrers.php",{ajaxProductFilter:1,id_employee:'.intval($cookie->id_employee).',token:"'.Tools::getValue('token').'",id_referrer:id_referrer,id_product:productIds[i]},
							function(result) {
								var newLine = newProductLine(id_referrer, result[0]);
								$(newLine).hide().insertAfter(getE(\'trid_\'+id_referrer)).fadeIn();
							}
						);
				}
				else
				{
					referrerStatus[id_referrer] = false;
					for (var i = 0; i < productIds.length; ++i)
						$("#trprid_"+id_referrer+"_"+productIds[i]).fadeOut("fast",	function(){$("#trprid_"+i).remove();});
				}
			}
		</script>';
		
		if ($this->enableCalendar())
		{
			echo '<div style="float: left; margin-right: 20px;">';
			echo AdminStatsTab::displayCalendarStatic(array('Calendar' => $this->l('Calendar'), 'Today' => $this->l('Today'), 'Month' => $this->l('Month'), 'Year' => $this->l('Year')));
			echo '</div>
			<div style="float: left; margin-right: 20px;">
				<fieldset class="width3"><legend><img src="../img/admin/tab-preferences.gif" /> '.$this->l('Settings').'</legend>
					<form action="'.$currentIndex.'&token='.Tools::getValue('token').'" method="post">
						<label>'.$this->l('Save direct traffic').'</label>
						<div class="float" style="margin-left: 200px;">
							<label class="t" for="tracking_dt_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Yes').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" name="tracking_dt" id="tracking_dt_on" value="1" '.(intval(Tools::getValue('tracking_dt', Configuration::get('TRACKING_DIRECT_TRAFFIC'))) ? 'checked="checked"' : '').' />
							<label class="t" for="tracking_dt_on"> '.$this->l('Yes').'</label>
							<label class="t" for="tracking_dt_off"><img src="../img/admin/disabled.gif" alt="'.$this->l('No').'" title="'.$this->l('No').'" style="margin-left: 10px;" /></label>
							<input type="radio" name="tracking_dt" id="tracking_dt_off" value="0" '.(!intval(Tools::getValue('tracking_dt', Configuration::get('TRACKING_DIRECT_TRAFFIC'))) ? 'checked="checked"' : '').'/>
							<label class="t" for="tracking_dt_off"> '.$this->l('No').'</label>
						</div>
						<br class="clear" />
						<p>'.$this->l('Direct traffic can be quite consuming, you should consider enable it only if you have a strong database server.').'</p>
						<input type="submit" class="button" value="'.$this->l('   Save   ').'" name="submitSettings" />
					</form>
					<hr />
					<form action="'.$currentIndex.'&token='.Tools::getValue('token').'" method="post">
					<p>'.$this->l('For you to sort and filter, data ara cached. You can refresh the cache by clicking on the button below.').'</p>
						<input type="submit" class="button" value="'.$this->l('Refresh data').'" name="submitRefreshData" />
					</form>
				</fieldset>
			</div>';
		}
		echo '<div class="clear space">&nbsp;</div>';
		parent::display();
		echo '<div class="clear space">&nbsp;</div>';
	}
	
	public function postProcess()
	{
		global $currentIndex;
		
		if ($this->enableCalendar())
		{
			$calendarTab = new AdminStats();
			$calendarTab->postProcess();
		}

		if (Tools::isSubmit('submitSettings'))
			if ($this->tabAccess['edit'] === '1')
				if (Configuration::updateValue('TRACKING_DIRECT_TRAFFIC', intval(Tools::getValue('tracking_dt'))))
					Tools::redirectAdmin($currentIndex.'&conf=4&token='.Tools::getValue('token'));

		if (ModuleGraph::getDateBetween() != Configuration::get('PS_REFERRERS_CACHE_LIKE') OR Tools::isSubmit('submitRefreshData'))
			Referrer::refreshCache();
		
		return parent::postProcess();
	}
	
	public function displayForm()
	{
		global $currentIndex;
		
		$obj = $this->loadObject(true);
		foreach (array('http_referer_like', 'http_referer_regexp', 'request_uri_like', 'request_uri_regexp') as $field)
			$obj->{$field} = str_replace('\\', '\\\\', $obj->{$field});
		$uri = 'http://'.htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__;

		echo '
		<form action="'.$currentIndex.'&submitAdd'.$this->table.'=1&token='.$this->token.'" method="post" class="width2">
		'.($obj->id ? '<input type="hidden" name="id_'.$this->table.'" value="'.$obj->id.'" />' : '').'
			<fieldset class="width4"><legend><img src="../img/admin/affiliation.png" /> '.$this->l('Affiliate').'</legend>
				<label>'.$this->l('Name').'</label>
				<div class="margin-form">
					<input type="text" size="20" name="name" value="'.htmlentities($this->getFieldValue($obj, 'name'), ENT_COMPAT, 'UTF-8').'" /> <sup>*</sup>
				</div>
				<label>'.$this->l('passwd:').'</label>
				<div class="margin-form">
					<input type="password" name="passwd" value="" />
					<p>'.$this->l('Leave blank if no change').'</p>
				</div>
				<p>
					'.$this->l('Affiliates can access to their own data with these name and password.').'<br />
					'.$this->l('Front access:').' <a href="'.$uri.'modules/trackingfront/stats.php" style="font-style: italic;">'.$uri.'modules/trackingfront/stats.php</a>
				</p>
			</fieldset>
			<br class="clear" />
			<fieldset class="width4"><legend><img src="../img/admin/money.png" /> '.$this->l('Commission plan').'</legend>
				<label>'.$this->l('Base fee').'</label>
				<div class="margin-form">
					<input type="text" size="8" name="base_fee" value="'.htmlentities($this->getFieldValue($obj, 'base_fee'), ENT_COMPAT, 'UTF-8').'" />
					<p>'.$this->l('Fee given for each order placed.').'</p>
				</div>
				<label>'.$this->l('Percent fee').'</label>
				<div class="margin-form">
					<input type="text" size="8" name="percent_fee" value="'.htmlentities($this->getFieldValue($obj, 'percent_fee'), ENT_COMPAT, 'UTF-8').'" />
					<p>'.$this->l('Percent of the sales.').'</p>
				</div>
			</fieldset>
			<br class="clear" />
			<fieldset class="width4"><legend onclick="openCloseLayer(\'tracking_help\')" style="cursor: pointer;"><img src="../img/admin/help.png" /> '.$this->l('Help').'</legend>
			<div id="tracking_help" style="display: none;">
			'.utf8_encode('
				<p>D�finitions :</p>
				<ul style="list-style: disc; margin-left: 20px;">
					<li>
						les champs <b>http_referer</b> repr�sentent le site d\'o� vient le visiteur.<br />
						Par exemple, les visiteurs qui viennent de Google sur votre boutique auront un http_referer qui pourra ressembler � celui-ci : <a href="http://www.google.fr/search?q=prestashop" style="font-style: italic;">http://www.google.fr/search?q=prestashop</a>.<br />
						Si le visiteur vient directement sur le site (en tapant l\'adresse ou depuis ses favoris par exemple), le http_referer sera vide.<br />
						Donc pour filtrer tous les visiteurs provenant de Google, il vous suffit de taper "%google%" dans ce champ, ou encore "%google.fr%" si vous ne voulez que les visiteurs provenant de Google France.
					</li>
					<br />
					<li>
						les champs <b>request_uri</b> repr�sentent l\'URL par laquelle le visiteur arrive sur votre boutique.<br />
						Si il acc�de directement � une fiche produit, cette adresse sera par exemple <a href="'.$uri.'music-ipods/1-ipod-nano.html" style="font-style: italic;">'.$uri.'music-ipods/1-ipod-nano.html</a>.<br />
						L\'int�r�t est que vous pouvez rajouter des indicateurs dans les liens qui pointent vers votre site. Ainsi, si vous postez sur le forum prestashop un lien <a href="'.$uri.'index.php?prestashop" style="font-style: italic;">'.$uri.'index.php?prestashop</a> et que vous entrez dans le champ request_uri le mot "%prestashop", vous pourrez filtrer tous les visiteurs provenant du forum.
						Cette m�thode est plus fiable que le filtre par http_referer, mais elle comporte des dangers. Si un moteur de recherche r�f�rence une page contenant le lien en question, alors il le proposera dans ses r�sultats de recherche, et vous n\'aurez plus alors seulement les visiteurs du forum mais �galement ceux qui viennent de ce moteur par ce lien.
					</li>
					<br />
					<li>
						les champs <b>include</b> indiquent ce qui doit �tre inclu dans l\'URL.
					</li>
					<br />
					<li>
						les champs <b>exclude</b> indiquent ce qui ne doit pas �tre inclu dans l\'URL.
					</li>
					<br />
					<li>
						En mode simple, vous avez la possibilit� d\'utiliser des <b>caract�res g�n�riques</b>, c\'est-�-dire qui peuvent remplacer n\'importe quels autres caract�res :
						<ul>
							<li>"_" remplacera un seul caract�re. Pour utiliser un vrai "_", vous devez taper "\\\\_".</li>
							<li>"%" remplacera n\'importe quel nombre de caract�res. Pour utiliser un vrai "%", vous devez taper "\\\\%".</li>
						</ul>
					</li>
					<br />
					<li>
						'.$this->l('The simple mode uses the MySQL "LIKE", but for a higher potency you can use').' <b>'.$this->l('MySQL regular expressions').'</b>.
						<a href="http://dev.mysql.com/doc/refman/5.0/en/regexp.html" target="_blank" style="font-style: italic;">'.$this->l('Take a look to the documentation for more details...').'</a>
					</li>
				</ul>
			').'
			</div>
			</fieldset>
			<br class="clear" />
			<fieldset class="width4"><legend><img src="../img/admin/affiliation.png" /> '.$this->l('Technical information - Simple mode').'</legend>
				<a style="cursor: pointer; font-style: italic;" onclick="openCloseLayer(\'tracking_help\');"><img src="../img/admin/help.png" /> '.$this->l('Get help!').'</a><br />
				<br class="clear" />
				<h3>'.$this->l('HTTP referrer').'</h3>
				<label>'.$this->l('Include').'</label>
				<div class="margin-form">
					<textarea cols="40" rows="1" name="http_referer_like">'.str_replace('\\', '\\\\', htmlentities($this->getFieldValue($obj, 'http_referer_like'), ENT_COMPAT, 'UTF-8')).'</textarea>
				</div>
				<label>'.$this->l('Exclude').'</label>
				<div class="margin-form">
					<textarea cols="40" rows="1" name="http_referer_like_not">'.str_replace('\\', '\\\\', htmlentities($this->getFieldValue($obj, 'http_referer_like_not'), ENT_COMPAT, 'UTF-8')).'</textarea>
				</div>
				<h3>'.$this->l('Request Uri').'</h3>
				<label>'.$this->l('Include').'</label>
				<div class="margin-form">
					<textarea cols="40" rows="1" name="request_uri_like">'.str_replace('\\', '\\\\', htmlentities($this->getFieldValue($obj, 'request_uri_like'), ENT_COMPAT, 'UTF-8')).'</textarea>
				</div>
				<label>'.$this->l('Exclude').'</label>
				<div class="margin-form">
					<textarea cols="40" rows="1" name="request_uri_like_not">'.str_replace('\\', '\\\\', htmlentities($this->getFieldValue($obj, 'request_uri_like_not'), ENT_COMPAT, 'UTF-8')).'</textarea>
				</div>
				<br class="clear" />
				<div class="margin-form">
					<input type="submit" value="'.$this->l('   Save   ').'" name="submitAdd'.$this->table.'" class="button" />
				</div>
				<br class="clear" />
				'.$this->l('If you know how to use MySQL regular expressions, you can use the').' <a style="cursor: pointer; font-weight: bold;" onclick="openCloseLayer(\'tracking_expert\');">'.$this->l('expert mode').'.</a>
			</fieldset>
			<br class="clear" />
			<fieldset class="width4"><legend onclick="openCloseLayer(\'tracking_expert\')" style="cursor: pointer;"><img src="../img/admin/affiliation.png" /> '.$this->l('Technical information - Expert mode').'</legend>
			<div id="tracking_expert" style="display: none;">
				<h3>'.$this->l('HTTP referrer').'</h3>
				<label>'.$this->l('Include').'</label>
				<div class="margin-form">
					<textarea cols="40" rows="1" name="http_referer_regexp">'.str_replace('\\', '\\\\', htmlentities($this->getFieldValue($obj, 'http_referer_regexp'), ENT_COMPAT, 'UTF-8')).'</textarea>
				</div>
				<label>'.$this->l('Exclude').'</label>
				<div class="margin-form">
					<textarea cols="40" rows="1" name="http_referer_regexp_not">'.str_replace('\\', '\\\\', htmlentities($this->getFieldValue($obj, 'http_referer_regexp_not'), ENT_COMPAT, 'UTF-8')).'</textarea>
				</div>
				<h3>'.$this->l('Request Uri').'</h3>
				<label>'.$this->l('Include').'</label>
				<div class="margin-form">
					<textarea cols="40" rows="1" name="request_uri_regexp">'.str_replace('\\', '\\\\', htmlentities($this->getFieldValue($obj, 'request_uri_regexp'), ENT_COMPAT, 'UTF-8')).'</textarea>
				</div>
				<label>'.$this->l('Exclude').'</label>
				<div class="margin-form">
					<textarea cols="40" rows="1" name="request_uri_regexp_not">'.str_replace('\\', '\\\\', htmlentities($this->getFieldValue($obj, 'request_uri_regexp_not'), ENT_COMPAT, 'UTF-8')).'</textarea>
				</div>
				<br class="clear" />
				<div class="margin-form">
					<input type="submit" value="'.$this->l('   Save   ').'" name="submitAdd'.$this->table.'" class="button" />
				</div>
			</div>
			</fieldset>
		</form>';
	}
	
	public function viewreferrer()
	{
		global $cookie, $currentIndex;
		$referrer = new Referrer(intval(Tools::getValue('id_referrer')));

		$displayTab = array(
			'uniqs' => $this->l('Unique visitors'),
			'visitors' => $this->l('Visitors'),
			'visits' => $this->l('Visits'),
			'pages' => $this->l('Pages viewed'),
			'registrations' => $this->l('Registrations'),
			'orders' => $this->l('Orders'),
			'sales' => $this->l('Sales'),
			'reg_rate' => $this->l('Registration rate'),
			'order_rate' => $this->l('Order rate'),
			'base_fee' => $this->l('Base fee'),
			'percent_fee' => $this->l('Percent fee'));
		echo '
		<script type="text/javascript">
			function updateConversionRate(id_product)
			{
				$.getJSON("'.dirname($currentIndex).'/tabs/AdminReferrers.php",{ajaxProductFilter:1,id_employee:'.intval($cookie->id_employee).',token:"'.Tools::getValue('token').'",id_referrer:'.$referrer->id.',id_product:id_product},
					function(j) {';
		foreach ($displayTab as $key => $value)
			echo '$("#'.$key.'").html(j[0].'.$key.');';
		echo '		}
				)
			}
			
			function fillProducts(filter)
			{
				var form = document.layers ? document.forms.product : document.product;
				var filter = form.filterProduct.value;
				$.getJSON("'.dirname($currentIndex).'/tabs/AdminReferrers.php",
					{ajaxFillProducts:1,id_employee:'.intval($cookie->id_employee).',token:"'.Tools::getValue('token').'",id_lang:'.intval($cookie->id_lang).',filter:filter},
					function(j) {
						
						form.selectProduct.length = j.length + 1;
						for (var i = 0; i < j.length; i++)
						{
							form.selectProduct.options[i+1].value = j[i].id_product;
							form.selectProduct.options[i+1].text = j[i].name;
						}
					}
				);
			}
		</script>
		<fieldset class="width3" style="float: left"><legend><img src="../img/admin/tab-stats.gif" /> Statistics</legend>
			<h2>'.$referrer->name.'</h2>
			<table>';
		foreach ($displayTab as $data => $label)
			echo '<tr><td>'.$label.'</td><td style="color:green;font-weight:bold;padding-left:20px;" id="'.$data.'"></td></tr>';
		echo '</table>
		<br class="clear" />
		<form id="product" name="product">
			'.$this->l('Filter by product:').'
			<select id="selectProduct" name="selectProduct" style="width: 200px;" onfocus="fillProducts();" onchange="updateConversionRate(this.value);">
				<option value="0" selected="selected">-- '.$this->l('All').' --</option>
			</select> <input type="text" size="25" id="filterProduct" name="filterProduct" onkeyup="fillProducts();" class="space" />
		</form>
		</fieldset>
		<script type="text/javascript">
			updateConversionRate(0);
		</script>';
	}
	
	public function displayListContent($token = NULL)
	{
		global $currentIndex;

		$irow = 0;
		$currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
		if ($this->_list)
			foreach ($this->_list AS $tr)
			{
				$id = $tr[$this->identifier];
				echo '<tr id="trid_'.$id.'"'.($irow++ % 2 ? ' class="alt_row"' : '').'>
				<td class="center"><input type="checkbox" name="'.$this->table.'Box[]" value="'.$id.'" class="noborder" /></td>';
				foreach ($this->fieldsDisplay AS $key => $params)
				{
					echo '<td onclick="showProductLines('.$id.');" class="pointer '.(isset($params['align']) ? $params['align'] : '').'">'.(isset($params['prefix']) ? $params['prefix'] : '');
					if (isset($tr[$key]) AND isset($params['price']))
						echo Tools::displayPrice($tr[$key], $currency);
					elseif (isset($tr[$key]))
						echo $tr[$key];
					else
						echo '--';
					echo (isset($params['suffix']) ? $params['suffix'] : '').'</td>';
				}
				echo '
				<td class="center" style="width: 60px">
					<a href="'.$currentIndex.'&'.$this->identifier.'='.$id.'&view'.$this->table.'&token='.($token!=NULL ? $token : $this->token).'">
					<img src="../img/admin/details.gif" border="0" alt="'.$this->l('View').'" title="'.$this->l('View').'" /></a>
					<a href="'.$currentIndex.'&'.$this->identifier.'='.$id.'&update'.$this->table.'&token='.($token!=NULL ? $token : $this->token).'">
					<img src="../img/admin/edit.gif" border="0" alt="'.$this->l('Edit').'" title="'.$this->l('Edit').'" /></a>
					<a href="'.$currentIndex.'&'.$this->identifier.'='.$id.'&delete'.$this->table.'&token='.($token!=NULL ? $token : $this->token).'" onclick="return confirm(\''.addslashes($this->l('Delete item #')).$id.' ?\');">
					<img src="../img/admin/delete.gif" border="0" alt="'.$this->l('Delete').'" title="'.$this->l('Delete').'" /></a>
				</tr>';
			}
	}
}

?>
