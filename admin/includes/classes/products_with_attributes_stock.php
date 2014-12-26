<?php
/**
 * @package admin/includes/classes
 * products_with_attributes_stock.php
 * @copyright Copyright 2003-2014 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id:  $
 *
 * Updated for Stock by Attributes 1.5.3.1
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

class products_with_attributes_stock
	{	
		function get_products_attributes($products_id, $languageId=1)
		{
			global $db;
			// Added the following to query "and patrib.attributes_display_only != 1" This removed read only attributes from the stock selection.
			$query = '	select 
							patrib.products_attributes_id, patrib.options_values_price, patrib.price_prefix,
			 				popt.products_options_name, pval.products_options_values_name
			 			from '.TABLE_PRODUCTS_ATTRIBUTES.' as patrib, '.TABLE_PRODUCTS_OPTIONS.' as popt, '.TABLE_PRODUCTS_OPTIONS_VALUES.' as pval
			 			where
			 				patrib.products_id = "'.$products_id.'" AND patrib.options_id = popt.products_options_id
			 				AND popt.language_id = "'.$languageId.'" and popt.language_id = pval.language_id
							and patrib.options_values_id = pval.products_options_values_id
							and patrib.attributes_display_only != 1';
			
			$attributes = $db->Execute($query);
			
			if($attributes->RecordCount()>0)
			{
				while(!$attributes->EOF)
				{
					$attributes_array[$attributes->fields['products_options_name']][] =
						array('id' => $attributes->fields['products_attributes_id'],
							  'text' => $attributes->fields['products_options_values_name']
							  			. ' (' . $attributes->fields['price_prefix']
										. '$'.zen_round($attributes->fields['options_values_price'],2) . ')' );
					$attributes->MoveNext();
				}
	
				return $attributes_array;
	
			}
			else
			{
				return false;
			}
		}
	
		function update_parent_products_stock($products_id)
		{
			global $db;

			$query = 'select sum(quantity) as quantity from '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' where products_id = "'.(int)$products_id.'"';
			$quantity = $db->Execute($query);
			$query = 'update '.TABLE_PRODUCTS.' set  products_quantity="'.$quantity->fields['quantity'].'" where products_id="'.(int)$products_id.'"';
			$db->Execute($query);
		}
    
    function update_all_parent_products_stock() {
      global $db;
      $products_array = $this->get_products_with_attributes();
      foreach ($products_array as $products_id) {
        $query = 'select sum(quantity) as quantity from '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' where products_id = "'.(int)$products_id.'"';
        $quantity = $db->Execute($query);
        $query = 'update '.TABLE_PRODUCTS.' set  products_quantity="'.$quantity->fields['quantity'].'" where products_id="'.(int)$products_id.'"';
        $db->Execute($query);
      }
    }
    
    // returns an array of product ids which contain attributes
    function get_products_with_attributes() {
      global $db;
      if(isset($_SESSION['languages_id'])){ $language_id = (int)$_SESSION['languages_id'];} else { $language_id=1;}
      $query = 'SELECT DISTINCT attrib.products_id, description.products_name, products.products_quantity, products.products_model, products.products_image
                FROM '.TABLE_PRODUCTS_ATTRIBUTES.' attrib, '.TABLE_PRODUCTS_DESCRIPTION.' description, '.TABLE_PRODUCTS.' products
                WHERE attrib.products_id = description.products_id AND
                      attrib.products_id = products.products_id AND 
                      description.language_id='.$language_id.' 
                ORDER BY description.products_name ';
      $products = $db->Execute($query);
      while(!$products->EOF){
        $products_array[] = $products->fields['products_id'];
        $products->MoveNext();
      }
      return $products_array;
    }
	
	
		function get_attributes_name($attribute_id, $languageId=1)
		{
			global $db;

			$query = 'select patrib.products_attributes_id, popt.products_options_name, pval.products_options_values_name
			 			from '.TABLE_PRODUCTS_ATTRIBUTES.' as patrib, '.TABLE_PRODUCTS_OPTIONS.' as popt, '.TABLE_PRODUCTS_OPTIONS_VALUES.' as pval
			 			where patrib.products_attributes_id = "'.$attribute_id.'"
							AND patrib.options_id = popt.products_options_id
			 				AND popt.language_id = "'.$languageId.'"
							and popt.language_id = pval.language_id
							and patrib.options_values_id = pval.products_options_values_id';
							
			$attributes = $db->Execute($query);
			if(!$attributes->EOF)
			{		
				$attributes_output = array('option' => $attributes->fields['products_options_name'],
										   'value' => $attributes->fields['products_options_values_name']);
				return $attributes_output;
			}
			else
			{
				return false;
			}
		}
        
        
/**
 * @desc displays the filtered product-rows
 * 
 * Passed Options
 * $SearchBoxOnly
 * $ReturnedPage
 * $NumberRecordsShown
 */
function displayFilteredRows($SearchBoxOnly = null, $NumberRecordsShown = null, $ReturnedProductID = null){
        global $db;

        if(isset($_SESSION['languages_id'])){ $language_id = $_SESSION['languages_id'];} else { $language_id=1;}
        if( isset($_GET['search']) ){
            $s = zen_db_input($_GET['search']);
         	//$w = "(products.products_id = '$s' OR description.products_name LIKE '%$s%' OR products.products_model LIKE '%$s%') AND  " ;//original version of search
            //$w = "( products.products_id = '$s' OR description.products_name LIKE '%$s%' OR products.products_model LIKE '$s%' ) AND  " ;//changed search to products_model 'startes with'.
         	//$w = "( products.products_id = '$s' OR description.products_name LIKE '%$s%' ) AND  " ;//removed products_model from search
            $w = " AND ( products.products_id = '$s' OR description.products_name LIKE '%$s%' OR products.products_model LIKE '$s%' ) " ;//changed search to products_model 'startes with'.
		} 
		else {
		    $w = ''; 
			$s = '';
		}

      	//Show last edited record or Limit number of records displayed on page
      	$SearchRange = null;
      	if( $ReturnedProductID != null && !isset($_GET['search']) ){
      		$ReturnedProductID = zen_db_input($ReturnedProductID);
      		//$w = "( products.products_id = '$ReturnedProductID' ) AND  " ;//sets returned record to display
      		$w = " AND ( products.products_id = '$ReturnedProductID' ) " ;//sets returned record to display
	      	$SearchRange = "limit 1";//show only selected record
	  	}
	  	elseif( $NumberRecordsShown > 0 && $SearchBoxOnly == 'false' ){
	  		$NumberRecordsShown = zen_db_input($NumberRecordsShown);
			$SearchRange = " limit $NumberRecordsShown";//sets start record and total number of records to display
		}
		elseif( $SearchBoxOnly == 'true' && !isset($_GET['search']) ){
		   	$SearchRange = "limit 0";//hides all records
		}

    $html = zen_draw_form('stock_update', FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK . '_ajax', 'save=1&pid='.$ReturnedProductID, 'post', 'NONSSL');
    $html .= zen_image_submit('button_save.gif', IMAGE_SAVE) . ' Hint: To quickly edit click in the "Quantity in Stock" field.';
    $html .= '
    <table id="mainProductTable"> 
    <tr>
      <th class="thProdId">'.PWA_PRODUCT_ID.'</th>
      <th class="thProdName">'.PWA_PRODUCT_NAME.'</th>';
    
    if (STOCK_SHOW_IMAGE == 'true') $html .= '<th class="thProdImage">'.PWA_PRODUCT_IMAGE.'</th>';   

    $html .= '  
      <th class="thProdModel">'.PWA_PRODUCT_MODEL.'</th>            
      <th class="thProdQty">'.PWA_QUANTITY_FOR_ALL_VARIANTS.'</th>
      <th class="thProdAdd">'.PWA_ADD_QUANTITY.'</th> 
      <th class="thProdSync">'.PWA_SYNC_QUANTITY.'</th>
      </tr>
      ';
       
        $retArr = array();
        /*
        $query =    'select distinct attrib.products_id, description.products_name, products.products_quantity, products.products_model, products.products_image
                    FROM '.TABLE_PRODUCTS_ATTRIBUTES.' attrib, '.TABLE_PRODUCTS_DESCRIPTION.' description, '.TABLE_PRODUCTS.' products
                    WHERE attrib.products_id = description.products_id and
                    ' . $w . '
                    attrib.products_id = products.products_id and description.language_id='.$language_id.' order by description.products_name 
                    '.$SearchRange.'';
        */
        $query =    'select distinct attrib.products_id, description.products_name, products.products_quantity, 
						products.products_model, products.products_image, products.products_type, products.master_categories_id
						
						FROM '.TABLE_PRODUCTS_ATTRIBUTES.' attrib
						left join '.TABLE_PRODUCTS_DESCRIPTION.' description on (attrib.products_id = description.products_id)
						left join '.TABLE_PRODUCTS.' products on (attrib.products_id = products.products_id)
						
						WHERE description.language_id='.$language_id.'
						' . $w . '
						order by description.products_name
						'.$SearchRange.'';
        
        $products = $db->Execute($query);
        
        while(!$products->EOF){ 
			    $html .= '<tr>'."\n";
			    $html .= '<td colspan="7">'."\n";
			    $html .= '<div class="productGroup">'."\n";
			    $html .= '<table width="100%">'."\n";
		        $html .= '<tr class="productRow">'."\n";
		        $html .= '<td class="tdProdId" class="pwas">'.$products->fields['products_id'].'</td>';
		        $html .= '<td class="tdProdName">'.$products->fields['products_name'].'</td>';
		        
		        if (STOCK_SHOW_IMAGE == 'true') {$html .= '<td class="tdProdImage">'.zen_info_image($products->fields['products_image'], $products->fields['products_name'], "60", "60").'</td>';}
		        //product.php? page=1 & product_type=1 & cPath=13 & pID=1042 & action=new_product
		        //$html .= '<td class="tdProdModel">'.$products->fields['products_model'] .' </td>';
		        $html .= '<td class="tdProdModel">'.$products->fields['products_model'] . '<br /><a href="'.zen_href_link(FILENAME_PRODUCT, "page=1&amp;product_type=".$products->fields['products_type']."&amp;cPath=".$products->fields['master_categories_id']."&amp;pID=".$products->fields['products_id']."&amp;action=new_product", 'NONSSL').'">Link</a> </td>';
		        $html .= '<td class="tdProdQty">'.$products->fields['products_quantity'].'</td>';
		        $html .= '<td class="tdProdAdd"><a href="'.zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=add&amp;products_id=".$products->fields['products_id'], 'NONSSL').'">' . PWA_ADD_QUANTITY . '</a></td>';
		        $html .= '<td class="tdProdSync"><a href="'.zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=resync&amp;products_id=".$products->fields['products_id'], 'NONSSL').'">' . PWA_SYNC_QUANTITY . '</a></td>';
		        $html .= '</tr>'."\n";
		        $html .= '</table>'."\n";
          // SUB            
          $query = 'select * from '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' where products_id="'.$products->fields['products_id'].'"
                    order by sort ASC;';

          $attribute_products = $db->Execute($query);
          if($attribute_products->RecordCount() > 0)
          {

              $html .= '<table class="stockAttributesTable">';
              $html .= '<tr>';
              $html .= '<th class="stockAttributesHeadingStockId">'.PWA_STOCK_ID.'</th>
              			<th class="stockAttributesHeadingVariant">'.PWA_VARIANT.'</th>
              			<th class="stockAttributesHeadingQuantity">'.PWA_QUANTITY_IN_STOCK.'</th>
              			<th class="stockAttributesHeadingSort">'.PWA_SORT_ORDER.'</th>
              			<th class="stockAttributesHeadingCustomid">'.PWA_CUSTOM_ID.'</th>
              			<th class="stockAttributesHeadingEdit">'.PWA_EDIT.'</th>
              			<th class="stockAttributesHeadingDelete">'.PWA_DELETE.'</th>';
              $html .= '</tr>';

              while(!$attribute_products->EOF)
              {
                  $html .= '<tr id="sid-'. $attribute_products->fields['stock_id'] .'">';
                  $html .= '<td class="stockAttributesCellStockId">'."\n";
                  $html .= $attribute_products->fields['stock_id'];
                  $html .= '</td>'."\n";
                  $html .= '<td class="stockAttributesCellVariant">'."\n";

                  $attributes_of_stock = explode(',',$attribute_products->fields['stock_attributes']);
                  $attributes_output = array();
                  foreach($attributes_of_stock as $attri_id)
                  {
                      $stock_attribute = $this->get_attributes_name($attri_id, $_SESSION['languages_id']);
                      if ($stock_attribute['option'] == '' && $stock_attribute['value'] == '') {
                        // delete stock attribute
                        $db->Execute("DELETE FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " WHERE stock_id = " . $attribute_products->fields['stock_id'] . " LIMIT 1;");
                      } else { 
                        $attributes_output[] = '<strong>'.$stock_attribute['option'].':</strong> '.$stock_attribute['value'].'<br />';
                      }
                  }
                  sort($attributes_output);
                  $html .= implode("\n",$attributes_output);

                  $html .= '</td>'."\n";
                  $html .= '<td class="stockAttributesCellQuantity" id="stockid-'. $attribute_products->fields['stock_id'] .'">'.$attribute_products->fields['quantity'].'</td>'."\n";
                  $html .= '<td class="stockAttributesCellSort" id="stockid2-'. $attribute_products->fields['stock_id'] .'">'.$attribute_products->fields['sort'].'</td>'."\n";
                  $html .= '<td title="The Custom ID MUST be Unique, no duplicates allowed!" class="stockAttributesCellCustomid" id="stockid3-'. $attribute_products->fields['stock_id'] .'">'.$attribute_products->fields['customid'].'</td>'."\n";
                  $html .= '<td class="stockAttributesCellEdit">'."\n";
                  $html .= '<a href="'.zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=edit&amp;products_id=".$products->fields['products_id'].'&amp;attributes='.$attribute_products->fields['stock_attributes'].'&amp;q='.$attribute_products->fields['quantity'], 'NONSSL').'">'.PWA_EDIT_QUANTITY.'</a>'; //s_mack:prefill_quantity
                  $html .= '</td>'."\n";
                  $html .= '<td class="stockAttributesCellDelete">'."\n";
                  $html .= '<a href="'.zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=delete&amp;products_id=".$products->fields['products_id'].'&amp;attributes='.$attribute_products->fields['stock_attributes'], 'NONSSL').'">'.PWA_DELETE_VARIANT.'</a>';
                  $html .= '</div>';
                  $html .= '</td>'."\n";
                  $html .= '</tr>'."\n";
                 

                  $attribute_products->MoveNext();
              }
              $html .= '</table>';
          }
          $products->MoveNext();   
      }
      $html .= '</table>';
      $html .= zen_image_submit('button_save.gif', IMAGE_SAVE);
      $html .= '</form>'."\n";

      return $html;
    }

//Used with jquery to edit qty on stock page and to save
function saveAttrib(){

	global $db;
	$stock = new products_with_attributes_stock;
    $i = 0;
    foreach ($_POST as $key => $value) {
    	$id = intval(str_replace('stockid-', '', $key));//quantity
    	$id2 = intval(str_replace('stockid2-', '', $key));//sort
    	$id3 = intval(str_replace('stockid3-', '', $key));//customid
    	
        if($id > 0){
        	$value = doubleval($value);
        	if(empty($value) || is_null($value)){$value = 0;}
			$sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET quantity = $value WHERE stock_id = " .$id. " LIMIT 1";
            $db->execute($sql);
            $i++;
        }      
        if($id2 > 0){
        	$value = doubleval($value);
        	if(empty($value) || is_null($value)){$value = 0;}
        	$sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET sort = $value WHERE stock_id = " .$id2. " LIMIT 1";
        	$db->execute($sql);
        	$i++;
        }
        if($id3 > 0){
        	$value = addslashes($value);
        	$value = $stock->nullDataEntry($value);
        	if(empty($value) || is_null($value)){$value = 'null';}
        	$sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET customid = $value WHERE stock_id = " .$id3. " LIMIT 1";
        	$db->execute($sql);
        	$i++;
        }
    }
    $html = print_r($_POST, true);
    $html = "$i DS SAVED";
    return $html;  
}

//Update attribute qty
function updateAttribQty($stock_id = null, $quantity = null){
	global $db;

	if(empty($quantity) || is_null($quantity)){$quantity = 0;}
	if( is_numeric($stock_id) && is_numeric($quantity) ){
		$query = 'update `'.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.'` set quantity='.$quantity.' where stock_id='.$stock_id.' limit 1';
		$result = $db->execute($query);
	}

	return $result;
}

//New attribute qty insert
//The on duplicate updates an existing record instead of adding a new one
function insertNewAttribQty($products_id = null, $strAttributes = null, $quantity = null, $customid = null){
	global $db;
	$stock = new products_with_attributes_stock;
	$customid = addslashes($customid);
	$customid = $stock->nullDataEntry($customid);//sets proper quoting for input
	$strAttributes = $stock->nullDataEntry($strAttributes);//sets proper quoting for input
	
	if( is_numeric($products_id) && isset($strAttributes) && is_numeric($quantity) ){
 		$query = "insert into ". TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK ." (`products_id`,`stock_attributes`,`quantity`,`customid`) 
 					values ($products_id, $strAttributes, $quantity, $customid)
 							ON DUPLICATE KEY UPDATE 
 							`quantity` = $quantity,
					 		`customid` =  $customid";
 		$result = $db->execute($query);
	}
	
	return $result;
}

//Update Custom ID to Attribute using the StockID as a key
function updateCustomIDAttrib($stockid = null, $customid = null){
	global $db;
	$stock = new products_with_attributes_stock;
	$customid = addslashes($customid);
	$customid = $stock->nullDataEntry($customid);//sets proper quoting for input

	if( $customid && is_numeric($stockid) ){
		$query = 'update ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' set customid = ' . $customid . ' where stock_id = ' . $stockid . ' limit 1';
		$result = $db->execute($query);
	}

	return $result;
}

//************************* Select list Function *************************//
//need to update to allow passing the data for $Item "$Item = 'ID:&nbsp;&nbsp;' . $result->fields["products_id"];"
//used to get a rows id number based on the table and column
//$Table = the table in the database to use.
//$Field = the field name from the table to use that has primary key or other uniqueness.
//The $Field is used as the default 'name' for the post event.
//$current = the current value in the database for this item.
//$providedQuery = This is a provided query that overrides the default $Table and $Field,
//note the $Field input (field name) is required to get returned data if name is not set or if there is not a $providedQuery.
function selectItemID($Table, $Field, $current = null, $providedQuery = null, $name= null, $id = null, $class = null, $style = null, $onChange = null){

	global $db;
	
	if(!$name){
		//use the $Field as the select NAME if no $name is provided
		$name = zen_db_input($Field);
	}
	if(!$id){
		//use the $Field as the select ID if no $id is provided
		$id = zen_db_input($Field);
	}

	if($providedQuery){
		$query = $providedQuery;//provided from calling object
	}
	else{
		$Table = zen_db_input($Table);
		$Field = zen_db_input($Field);
 		$query = "SELECT * FROM $Table ORDER BY $Field ASC";
	}

	if($onChange){
		$onChange = "onchange=\"selectItem()\"";
	}
		
	$class = zen_db_input($class);
	
	$Output = "<SELECT class='".$class."' id='".$id."' name='".$name."' $onChange >";//create selection list
    $Output .= "<option value='' $style></option>";//adds blank entry as first item in list

	/* Fields that may be of use in returned set
	["products_id"]
	["products_name"]
	["products_quantity"]
	["products_model"]
	["products_image"]
	 */
    $i = 1;
	$result = $db->Execute($query);
   	while(!$result->EOF){

   		//set each row background color
   		if($i == 1){
   			$style = 'style="background-color:silver;"';
   			$i = 0;
   		}
   		else{
   			$style = null;//'style="background-color:blue;"';
   			$i = 1;
   		}
   		
        $rowID = $result->fields["products_id"];
        $Item = 'ID:&nbsp;&nbsp;' . $result->fields["products_id"];
        $Item .= '&nbsp;&nbsp;Model:&nbsp;&nbsp;' . $result->fields["products_model"];
        $Item .= '&nbsp;&nbsp;Name:&nbsp;&nbsp;' . $result->fields["products_name"];
            
		if ( ($Item == $current AND $current != NULL) || ($rowID == $current AND $current != NULL) ){
	    	$Output .= "<option selected='selected' $style value='".$rowID."'>$Item</option>";
	    }
	    else{
	    	$Output .= "<option $style value='".$rowID."'>$Item</option>";
	    }

		$result->MoveNext();
	}

	$Output .= "</select>";

	return $Output;
}

//NULL entry for database
function nullDataEntry($fieldtoNULL){

	//Need to test for absolute 0 (===), else compare will convert $fieldtoNULL to a number (null) and evauluate as a null 
	//This is due to PHP string to number compare "feature"
	if(!empty($fieldtoNULL) || $fieldtoNULL === 0){
		if(is_numeric($fieldtoNULL) || $fieldtoNULL === 0){
			$output = $fieldtoNULL;//returns number without quotes
		}
		else{
			$output = "'".$fieldtoNULL."'";//encases the string in quotes
		}
	}
	else{
		$output = 'null';
	}

	return $output;
}

  /* ********************************************************************* */
  /*  Ported from rhuseby: (my_stock_id MOD) and modified for SBA customid */
  /*  Added function to support attribute specific part numbers            */
  /* ********************************************************************* */
  function zen_get_customid($products_id, $attributes = null) {
  	global $db;
  	$customid_model_query = null;
  	$customid_query = null;
  	$products_id = zen_get_prid($products_id);
  
  	// check if there are attributes for this product
 	$stock_has_attributes = $db->Execute('select products_attributes_id 
  											from '.TABLE_PRODUCTS_ATTRIBUTES.' 
  											where products_id = ' . (int)$products_id . '');

  	if ( $stock_has_attributes->RecordCount() < 1 ) {
  		
  			//if no attributes return products_model
			$no_attribute_stock_query = 'select products_model 
  										from '.TABLE_PRODUCTS.' 
  										where products_id = '. (int)$products_id . ';';
  		$customid = $db->Execute($no_attribute_stock_query);
  		return $customid->fields['products_model'];
  	} 
  	else {
  		
  		if(is_array($attributes) and sizeof($attributes) > 0){
  			// check if attribute stock values have been set for the product
  			// if there are will we continue, otherwise we'll use product level data
			$attribute_stock = $db->Execute("select stock_id 
							  					from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " 
							  					where products_id = " . (int)$products_id . ";");
  	
  			if ($attribute_stock->RecordCount() > 0) {
  				// search for details for the particular attributes combination
  					$first_search = 'where options_values_id in ("'.implode('","',$attributes).'")';
  				
  				// obtain the attribute ids
  				$query = 'select products_attributes_id 
  						from '.TABLE_PRODUCTS_ATTRIBUTES.' 
  								'.$first_search.' 
  								and products_id='.$products_id.' 
  								order by products_attributes_id;';
  				$attributes_new = $db->Execute($query);
  				
  				while(!$attributes_new->EOF){
  					$stock_attributes[] = $attributes_new->fields['products_attributes_id'];
  					$attributes_new->MoveNext();
  				}

  				if(sizeof($stock_attributes) > 1){
  					$stock_attributes = implode(',',$stock_attributes);
  					$stock_attributes = str_ireplace(',', '","', $stock_attributes);					
  				} else {
  					$stock_attributes = $stock_attributes[0];
  				}
  			}
  			
  			//Get product model
  			$customid_model_query = 'select products_model 
						  					from '.TABLE_PRODUCTS.' 
						  					where products_id = '. (int)$products_id . ';';

  			//Get custom id as products_model
  			$customid_query = 'select customid as products_model
		  							from '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' 
		  							where products_id = '.(int)$products_id.' 
		  							and stock_attributes in ("'.$stock_attributes.'");';  
  		}
  		
  		$customid = $db->Execute($customid_query);
  		if($customid->fields['products_model']){
  		
	  		//Test to see if a custom ID exists
	  		//if there are custom IDs with the attribute, then return them.
	  			$multiplecid = null;
	  			while(!$customid->EOF){
	  				$multiplecid .= $customid->fields['products_model'] . ', ';
	  				$customid->MoveNext();
	  			}
	  			$multiplecid = rtrim($multiplecid, ', ');
	  			
	  			//return result for display
	  			return $multiplecid;
	  	
  		}
  		else{
  			$customid = null;
  			//This is used as a fall-back when custom ID is set to be displayed but no attribute is available.
  			//Get product model
  			$customid_model_query = 'select products_model
						  					from '.TABLE_PRODUCTS.'
						  					where products_id = '. (int)$products_id . ';';
  			$customid = $db->Execute($customid_model_query);
  			//return result for display
  			return $customid->fields['products_model'];
  		}
  		return;//nothing to return, should never reach this return
  	}
  }//end of function
  
}//end of class