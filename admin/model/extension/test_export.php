<?php

static $registry = null;

// Error Handler
function error_handler_for_test_export($errno, $errstr, $errfile, $errline) {
	global $registry;
	
	switch ($errno) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$errors = "Notice";
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$errors = "Warning";
			break;
		case E_ERROR:
		case E_USER_ERROR:
			$errors = "Fatal Error";
			break;
		default:
			$errors = "Unknown";
			break;
	}
	
	$config = $registry->get('config');
	$url = $registry->get('url');
	$request = $registry->get('request');
	$session = $registry->get('session');
	$log = $registry->get('log');
	
	if ($config->get('config_error_log')) {
		$log->write('PHP ' . $errors . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
	}

	if (($errors=='Warning') || ($errors=='Unknown')) {
		return true;
	}

	if (($errors != "Fatal Error") && isset($request->get['route']) && ($request->get['route']!='extension/test_export/download'))  {
		if ($config->get('config_error_display')) {
			echo '<b>' . $errors . '</b>: ' . $errstr . ' in <b>' . $errfile . '</b> on line <b>' . $errline . '</b>';
		}
	} else {
		$session->data['test_export_error'] = array( 'errstr'=>$errstr, 'errno'=>$errno, 'errfile'=>$errfile, 'errline'=>$errline );
		$token = $request->get['token'];
		$link = $url->link( 'extension/test_export', 'token='.$token, 'SSL' );
		header('Status: ' . 302);
		header('Location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $link));
		exit();
	}

	return true;
}


function fatal_error_shutdown_handler_for_test_export()
{
	$last_error = error_get_last();
	if ($last_error['type'] === E_ERROR) {
		// fatal error
		error_handler_for_test_export(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
	}
}


class ModelExtensionTestExport extends Model {
     
        protected $null_array = array();
        
        
        /*download-upload*/
	public function upload2( $filename ) {
		// we use our own error handler
		global $registry;
		$registry = $this->registry;
		set_error_handler('error_handler_for_test_export',E_ALL);
		register_shutdown_function('fatal_error_shutdown_handler_for_test_export');

		try {
			$this->session->data['test_export_nochange'] = 1;

			// we use the PHPExcel package from http://phpexcel.codeplex.com/
			$cwd = getcwd();
			chdir( DIR_SYSTEM.'PHPExcel' );
			require_once( 'Classes/PHPExcel.php' );
			chdir( $cwd );
			
			// Memory Optimization
			if ($this->config->get( 'test_export_settings_use_import_cache' )) {
				$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
				$cacheSettings = array( ' memoryCacheSize '  => '16MB'  );
				PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
			}

			// parse uploaded spreadsheet file
			$inputFileType = PHPExcel_IOFactory::identify($filename);
			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
			$objReader->setReadDataOnly(true);
			$reader = $objReader->load($filename);

			// validate data
			if (!$this->validateUpload2( $reader )) {
				return false;
			}
			$this->clearCache();
			$this->session->data['test_export_nochange'] = 0;
			$available_product_ids = array();
			$available_category_ids = array();

			$this->uploadProducts2( $reader, $available_product_ids );

			return true;
		} catch (Exception $e) {
			$errstr = $e->getMessage();
			$errline = $e->getLine();
			$errfile = $e->getFile();
			$errno = $e->getCode();
			$this->session->data['test_export_error'] = array( 'errstr'=>$errstr, 'errno'=>$errno, 'errfile'=>$errfile, 'errline'=>$errline );
			if ($this->config->get('config_error_log')) {
				$this->log->write('PHP ' . get_class($e) . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
			}
			return false;
		}
	}
 
        public function download2() {
		// we use our own error handler
		global $registry;
		$registry = $this->registry;
//		set_error_handler('error_handler_for_test_export',E_ALL);
//		register_shutdown_function('fatal_error_shutdown_handler_for_test_export');

		// Use the PHPExcel package from http://phpexcel.codeplex.com/
		$cwd = getcwd();
		chdir( DIR_SYSTEM.'PHPExcel' );
		require_once( 'Classes/PHPExcel.php' );
		PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_ExportImportValueBinder() );
		chdir( $cwd );



//		 Memory Optimization
		if ($this->config->get( 'test_export_settings_use_export_cache' )) {
			$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
			$cacheSettings = array( 'memoryCacheSize'  => '16MB' );  
			PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);  
		}

		try {
			// set appropriate timeout limit
			set_time_limit( 1800 );

			$languages = $this->getLanguages();
			$default_language_id = $this->getDefaultLanguageId();

			// create a new workbook
			$workbook = new PHPExcel();

			// set some default styles
			$workbook->getDefaultStyle()->getFont()->setName('Arial');
			$workbook->getDefaultStyle()->getFont()->setSize(10);
			//$workbook->getDefaultStyle()->getAlignment()->setIndent(0.5);
			$workbook->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			$workbook->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
			$workbook->getDefaultStyle()->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);

			// pre-define some commonly used styles
			$box_format = array(
				'fill' => array(
					'type'      => PHPExcel_Style_Fill::FILL_SOLID,
					'color'     => array( 'rgb' => 'F0F0F0')
				),
				/*
				'alignment' => array(
					'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
					'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
					'wrap'       => false,
					'indent'     => 0
				)
				*/
			);
			$text_format = array(
				'numberformat' => array(
					'code' => PHPExcel_Style_NumberFormat::FORMAT_TEXT
				),
				/*
				'alignment' => array(
					'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
					'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
					'wrap'       => false,
					'indent'     => 0
				)
				*/
			);
			$price_format = array(
				'numberformat' => array(
					'code' => '######0.00'
				),
				'alignment' => array(
					'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
					/*
					'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
					'wrap'       => false,
					'indent'     => 0
					*/
				)
			);
			$weight_format = array(
				'numberformat' => array(
					'code' => '##0.00'
				),
				'alignment' => array(
					'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
					/*
					'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
					'wrap'       => false,
					'indent'     => 0
					*/
				)
			);
			
			// create the worksheets
			$worksheet_index = 0;

                                // creating the Products worksheet
                                $workbook->setActiveSheetIndex($worksheet_index++);
                                $worksheet = $workbook->getActiveSheet();
                                $worksheet->setTitle( 'Products' );
                                $this->populateProductsWorksheet2( $worksheet, $languages, $default_language_id, $price_format, $box_format, $weight_format, $text_format );
                                $worksheet->freezePaneByColumnAndRow( 1, 2 );

			$workbook->setActiveSheetIndex(0);

                        
			// redirect output to client browser
			$datetime = date('Y-m-d');

                                $filename = 'products-'.$datetime;
                                $filename .= '.xlsx';
				

			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="'.$filename.'"');
			header('Cache-Control: max-age=0');
			$objWriter = PHPExcel_IOFactory::createWriter($workbook, 'Excel2007');
			$objWriter->setPreCalculateFormulas(false);
			$objWriter->save('php://output');

			// Clear the spreadsheet caches
			$this->clearSpreadsheetCache();
			exit;

		} catch (Exception $e) {
			$errstr = $e->getMessage();
			$errline = $e->getLine();
			$errfile = $e->getFile();
			$errno = $e->getCode();
			$this->session->data['test_export_error'] = array( 'errstr'=>$errstr, 'errno'=>$errno, 'errfile'=>$errfile, 'errline'=>$errline );
			if ($this->config->get('config_error_log')) {
				$this->log->write('PHP ' . get_class($e) . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
			}
			return;
		}
	}
        
 	protected function uploadProducts2( &$reader,  &$available_product_ids=array() ) {
		// get worksheet, if not there return immediately
		$data = $reader->getSheetByName( 'Products' );
		if ($data==null) {
			return;
		}

		// find the installed languages
		$languages = $this->getLanguages();


		// get list of the field names, some are only available for certain OpenCart versions
		$query = $this->db->query( "DESCRIBE `".DB_PREFIX."product`" );
		$product_fields = array();
		foreach ($query->rows as $row) {
			$product_fields[] = $row['Field'];
		}

		// load the worksheet cells and store them to the database
		$first_row = array();
		$i = 0;
		$k = $data->getHighestRow();
		for ($i=0; $i<$k; $i+=1) {
			if ($i==0) {
				$max_col = PHPExcel_Cell::columnIndexFromString( $data->getHighestColumn() );
				for ($j=1; $j<=$max_col; $j+=1) {
					$first_row[] = $this->getCell($data,$i,$j);
				}
				continue;
			}
			$j = 1;
			$product_id = trim($this->getCell($data,$i,$j++));
//			if ($product_id=="") {
//				continue;
//			}
			$names = array();
			while ($this->startsWith($first_row[$j-1],"name(")) {
				$language_code = substr($first_row[$j-1],strlen("name("),strlen($first_row[$j-1])-strlen("name(")-1);
				$name = $this->getCell($data,$i,$j++);
				$name = htmlspecialchars( $name );
				$names[$language_code] = $name;
			}

			$quantity = $this->getCell($data,$i,$j++,'0');
			$image_name = $this->getCell($data,$i,$j++);
			$price = $this->getCell($data,$i,$j++,'0.00');
			$status = $this->getCell($data,$i,$j++,'true');

                        
			$product = array();
			$product['product_id'] = $product_id;
			$product['names'] = $names;
			$product['quantity'] = $quantity;
			$product['image'] = $image_name;
			$product['price'] = $price;
			$product['status'] = $status;
                                        
	
                        if($product_id=="" || !$this->isProductId($product_id)){
                            $this->storeProductIntoDatabase2( $product, $languages, $product_fields);
                        }
                        else{
                            $this->updateProductIntoDatabase2( $product, $languages, $product_fields);
                        }
			
		}
	}      
               
	protected function storeProductIntoDatabase2( &$product, &$languages, &$product_fields ) {
		
		$product_id = $product['product_id'];
		$names = $product['names'];
		$quantity = $product['quantity'];
		$image = $this->db->escape($product['image']);
		$price = trim($product['price']);
		$status = $product['status'];
		$status = ((strtoupper($status)=="TRUE") || (strtoupper($status)=="YES") || (strtoupper($status)=="ENABLED")) ? 1 : 0;


		// generate and execute SQL for inserting the product
		$sql  = "INSERT INTO `".DB_PREFIX."product` (`quantity`,`image`,`price`,`status`)VALUES ";
		$sql .= "($quantity,'$image','$price','$status');";

		$this->db->query($sql);
                
                
                $product_id=$this->db->getLastId();
		foreach ($languages as $language) {
                    
			$language_code = $language['code'];
			$language_id = $language['language_id'];
			$name = isset($names[$language_code]) ? $this->db->escape($names[$language_code]) : '';

                        
			$sql  = "INSERT INTO `".DB_PREFIX."product_description` (`product_id`, `language_id`, `name`) VALUES ";
			$sql .= "( $product_id, $language_id, '$name' );";

			$this->db->query($sql);
		}

	}    
        
	protected function updateProductIntoDatabase2( &$product, &$languages, &$product_fields ) {
		
		$product_id = $product['product_id'];
		$names = $product['names'];
		$quantity = $product['quantity'];
		$image = $this->db->escape($product['image']);
		$price = trim($product['price']);
		$status = $product['status'];
		$status = ((strtoupper($status)=="TRUE") || (strtoupper($status)=="YES") || (strtoupper($status)=="ENABLED")) ? 1 : 0;


		// generate and execute SQL for updating the product
		$sql  = "UPDATE `".DB_PREFIX."product` SET "
                        . "`quantity`='".$quantity."',"
                        . "`image`='".$image."',"
                        . "`price`='".$price."',"
                        . "`status`= '".$status."' "
                        . "WHERE "
                        . "`product_id`='".$product_id."'";

		$this->db->query($sql);
                
		foreach ($languages as $language) {
                    
			$language_code = $language['code'];
			$language_id = $language['language_id'];
			$name = isset($names[$language_code]) ? $this->db->escape($names[$language_code]) : '';

                        
                        $sql  = "UPDATE `".DB_PREFIX."product_description` SET "
                        . "`name`='".$name."'"
                        . " WHERE "
                        . "`product_id`='".$product_id."'"
                        . " AND "        
                        . "`language_id`='".$language_id."'";
			$this->db->query($sql);
		}

	}          



        /*validate*/
	function validateHeading( &$data, &$expected, &$multilingual ) {
		$default_language_code = $this->config->get('config_language');
		$heading = array();
		$k = PHPExcel_Cell::columnIndexFromString( $data->getHighestColumn() );
		$i = 0;
		for ($j=1; $j <= $k; $j+=1) {
			$entry = $this->getCell($data,$i,$j);
			$bracket_start = strripos( $entry, '(', 0 );
			if ($bracket_start === false) {
				if (in_array( $entry, $multilingual )) {
					return false;
				}
				$heading[] = strtolower($entry);
			} else {
				$name = strtolower(substr( $entry, 0, $bracket_start ));
				if (!in_array( $name, $multilingual )) {
					return false;
				}
				$bracket_end = strripos( $entry, ')', $bracket_start );
				if ($bracket_end <= $bracket_start) {
					return false;
				}
				if ($bracket_end+1 != strlen($entry)) {
					return false;
				}
				$language_code = strtolower(substr( $entry, $bracket_start+1, $bracket_end-$bracket_start-1 ));
				if (count($heading) <= 0) {
					return false;
				}
				if ($heading[count($heading)-1] != $name) {
					$heading[] = $name;
				}
			}
		}
                
		for ($i=0; $i < count($expected); $i+=1) {
			if (!isset($heading[$i])) {
				return false;
			}
			if ($heading[$i] != $expected[$i]) {
				return false;
			}
		}
		return true;
	}

	protected function validateProducts2( &$reader ) {
		$data = $reader->getSheetByName( 'Products' );
		if ($data==null) {
			return false;
		}

		$expected_heading = array
		( "product_id", "name", "quantity",  "image_name","price", "status" );
                $expected_multilingual = array( "name" );
                
		return $this->validateHeading( $data, $expected_heading, $expected_multilingual );
	}        
        
	protected function validateProductIdColumns( &$reader ) {
		$data = $reader->getSheetByName( 'Products' );
		if ($data==null) {
			return false;
		}
		$ok = true;
		
		// only unique numeric product_ids can be used in worksheet 'Products'
		$has_missing_product_ids = false;
		$product_ids = array();
		$k = $data->getHighestRow();
		for ($i=1; $i<$k; $i+=1) {
			$product_id = trim($this->getCell($data,$i,1));
			if ($product_id=="") {
//				if (!$has_missing_product_ids) {
//					$msg = str_replace( '%1', 'Products', $this->language->get( 'error_missing_product_id' ) );
//					$this->log->write( $msg );
//					$has_missing_product_ids = true;
//				}
//				$ok = false;
				continue;
			}
			if (!ctype_digit($product_id)) {
				$msg = str_replace( '%2', $product_id, str_replace( '%1', 'Products', $this->language->get( 'error_invalid_product_id' ) ) );
				$this->log->write( $msg );
				$ok = false;
				continue;
			}
			if (in_array( $product_id, $product_ids )) {
				$msg = str_replace( '%2', $product_id, str_replace( '%1', 'Products', $this->language->get( 'error_duplicate_product_id' ) ) );
				$this->log->write( $msg );
				$ok = false;
				continue;
			}
			$product_ids[] = $product_id;
		}

		return $ok;
	}

	protected function validateUpload2( &$reader )
	{
		$ok = true;
                

		if (!$this->validateProducts2( $reader )) {
			$this->log->write( $this->language->get('error_products_header') );
			$ok = false;
		}

		if (!$this->validateProductIdColumns( $reader )) {
			$ok = false;
		}
		
		
		return $ok;
	}


        /*Excel object*/
	protected function setColumnStyles( &$worksheet, &$styles, $min_row, $max_row ) {
		if ($max_row < $min_row) {
			return;
		}
		foreach ($styles as $col=>$style) {
			$from = PHPExcel_Cell::stringFromColumnIndex($col).$min_row;
			$to = PHPExcel_Cell::stringFromColumnIndex($col).$max_row;
			$range = $from.':'.$to;
			$worksheet->getStyle( $range )->applyFromArray( $style, false );
		}
	}

	protected function setCellRow( $worksheet, $row/*1-based*/, $data, &$default_style=null, &$styles=null ) {
		if (!empty($default_style)) {
			$worksheet->getStyle( "$row:$row" )->applyFromArray( $default_style, false );
		}
		if (!empty($styles)) {
			foreach ($styles as $col=>$style) {
				$worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($style,false);
			}
		}
		$worksheet->fromArray( $data, null, 'A'.$row, true );

	}

	protected function setCell( &$worksheet, $row/*1-based*/, $col/*0-based*/, $val, &$style=null ) {
		$worksheet->setCellValueByColumnAndRow( $col, $row, $val );
		if (!empty($style)) {
			$worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray( $style, false );
		}
	}
        
	function getCell(&$worksheet,$row,$col,$default_val='') {
		$col -= 1; // we use 1-based, PHPExcel uses 0-based column index
		$row += 1; // we use 0-based, PHPExcel uses 1-based row index
		$val = ($worksheet->cellExistsByColumnAndRow($col,$row)) ? $worksheet->getCellByColumnAndRow($col,$row)->getValue() : $default_val;
		if ($val===null) {
			$val = $default_val;
		}
		return $val;
	}
        
	protected function startsWith( $haystack, $needle ) {
		if (strlen( $haystack ) < strlen( $needle )) {
			return false;
		}
		return (substr( $haystack, 0, strlen($needle) ) == $needle);
	}

	protected function endsWith( $haystack, $needle ) {
		if (strlen( $haystack ) < strlen( $needle )) {
			return false;
		}
		return (substr( $haystack, strlen($haystack)-strlen($needle), strlen($needle) ) == $needle);
	}


        /*Cash*/
	protected function clearSpreadsheetCache() {
		$files = glob(DIR_CACHE . 'Spreadsheet_Excel_Writer' . '*');
		
		if ($files) {
			foreach ($files as $file) {
				if (file_exists($file)) {
					@unlink($file);
					clearstatcache();
				}
			}
		}
	}
 
	protected function clearCache() {
		$this->cache->delete('*');
	}
  

        /*Languages*/
	protected function getDefaultLanguageId() {
		$code = $this->config->get('config_language');
		$sql = "SELECT language_id FROM `".DB_PREFIX."language` WHERE code = '$code'";
		$result = $this->db->query( $sql );
		$language_id = 1;
		if ($result->rows) {
			foreach ($result->rows as $row) {
				$language_id = $row['language_id'];
				break;
			}
		}
		return $language_id;
	}

	protected function getLanguages() {
		$query = $this->db->query( "SELECT * FROM `".DB_PREFIX."language` WHERE `status`=1 ORDER BY `code`" );
		return $query->rows;
	}
        
        
        /*Products*/
	protected function getProducts( &$languages, $default_language_id, $product_fields, $exist_meta_title, $offset=null, $rows=null, $min_id=null, $max_id=null ) {
		$sql  = "SELECT ";
		$sql .= "  p.product_id,";
		$sql .= "  GROUP_CONCAT( DISTINCT CAST(pc.category_id AS CHAR(11)) SEPARATOR \",\" ) AS categories,";
		$sql .= "  p.sku,";
		$sql .= "  p.upc,";
		if (in_array( 'ean', $product_fields )) {
			$sql .= "  p.ean,";
		}
		if (in_array('jan',$product_fields)) {
			$sql .= "  p.jan,";
		}
		if (in_array('isbn',$product_fields)) {
			$sql .= "  p.isbn,";
		}
		if (in_array('mpn',$product_fields)) {
			$sql .= "  p.mpn,";
		}
		$sql .= "  p.location,";
		$sql .= "  p.quantity,";
		$sql .= "  p.model,";
		$sql .= "  m.name AS manufacturer,";
		$sql .= "  p.image AS image_name,";
		$sql .= "  p.shipping,";
		$sql .= "  p.price,";
		$sql .= "  p.points,";
		$sql .= "  p.date_added,";
		$sql .= "  p.date_modified,";
		$sql .= "  p.date_available,";
		$sql .= "  p.weight,";
		$sql .= "  wc.unit AS weight_unit,";
		$sql .= "  p.length,";
		$sql .= "  p.width,";
		$sql .= "  p.height,";
		$sql .= "  p.status,";
		$sql .= "  p.tax_class_id,";
		$sql .= "  p.sort_order,";
		$sql .= "  ua.keyword,";
		$sql .= "  p.stock_status_id, ";
		$sql .= "  mc.unit AS length_unit, ";
		$sql .= "  p.subtract, ";
		$sql .= "  p.minimum, ";
		$sql .= "  GROUP_CONCAT( DISTINCT CAST(pr.related_id AS CHAR(11)) SEPARATOR \",\" ) AS related ";
		$sql .= "FROM `".DB_PREFIX."product` p ";
		$sql .= "LEFT JOIN `".DB_PREFIX."product_to_category` pc ON p.product_id=pc.product_id ";
		$sql .= "LEFT JOIN `".DB_PREFIX."url_alias` ua ON ua.query=CONCAT('product_id=',p.product_id) ";
		$sql .= "LEFT JOIN `".DB_PREFIX."manufacturer` m ON m.manufacturer_id = p.manufacturer_id ";
		$sql .= "LEFT JOIN `".DB_PREFIX."weight_class_description` wc ON wc.weight_class_id = p.weight_class_id ";
		$sql .= "  AND wc.language_id=$default_language_id ";
		$sql .= "LEFT JOIN `".DB_PREFIX."length_class_description` mc ON mc.length_class_id=p.length_class_id ";
		$sql .= "  AND mc.language_id=$default_language_id ";
		$sql .= "LEFT JOIN `".DB_PREFIX."product_related` pr ON pr.product_id=p.product_id ";
		if (isset($min_id) && isset($max_id)) {
			$sql .= "WHERE p.product_id BETWEEN $min_id AND $max_id ";
		}
		$sql .= "GROUP BY p.product_id ";
		$sql .= "ORDER BY p.product_id ";
		if (isset($offset) && isset($rows)) {
			$sql .= "LIMIT $offset,$rows; ";
		} else {
			$sql .= "; ";
		}
		$results = $this->db->query( $sql );
                
		$product_descriptions = $this->getProductDescriptions( $languages, $offset, $rows, $min_id, $max_id );
		foreach ($languages as $language) {
			$language_code = $language['code'];
			foreach ($results->rows as $key=>$row) {
				if (isset($product_descriptions[$language_code][$key])) {
					$results->rows[$key]['name'][$language_code] = $product_descriptions[$language_code][$key]['name'];
					$results->rows[$key]['description'][$language_code] = $product_descriptions[$language_code][$key]['description'];
					if ($exist_meta_title) {
						$results->rows[$key]['meta_title'][$language_code] = $product_descriptions[$language_code][$key]['meta_title'];
					}
					$results->rows[$key]['meta_description'][$language_code] = $product_descriptions[$language_code][$key]['meta_description'];
					$results->rows[$key]['meta_keyword'][$language_code] = $product_descriptions[$language_code][$key]['meta_keyword'];
					$results->rows[$key]['tag'][$language_code] = $product_descriptions[$language_code][$key]['tag'];
				} else {
					$results->rows[$key]['name'][$language_code] = '';
					$results->rows[$key]['description'][$language_code] = '';
					if ($exist_meta_title) {
						$results->rows[$key]['meta_title'][$language_code] = '';
					}
					$results->rows[$key]['meta_description'][$language_code] = '';
					$results->rows[$key]['meta_keyword'][$language_code] = '';
					$results->rows[$key]['tag'][$language_code] = '';
				}
			}
		}
		return $results->rows;
	}
        
	protected function getProductDescriptions( &$languages, $offset=null, $rows=null, $min_id=null, $max_id=null ) {
		// some older versions of OpenCart use the 'product_tag' table
		$exist_table_product_tag = false;
		$query = $this->db->query( "SHOW TABLES LIKE '".DB_PREFIX."product_tag'" );
		$exist_table_product_tag = ($query->num_rows > 0);

		// query the product_description table for each language
		$product_descriptions = array();
		foreach ($languages as $language) {
			$language_id = $language['language_id'];
			$language_code = $language['code'];
			$sql  = "SELECT p.product_id, ".(($exist_table_product_tag) ? "GROUP_CONCAT(pt.tag SEPARATOR \",\") AS tag, " : "")."pd.* ";
			$sql .= "FROM `".DB_PREFIX."product` p ";
			$sql .= "LEFT JOIN `".DB_PREFIX."product_description` pd ON pd.product_id=p.product_id AND pd.language_id='".(int)$language_id."' ";
			if ($exist_table_product_tag) {
				$sql .= "LEFT JOIN `".DB_PREFIX."product_tag` pt ON pt.product_id=p.product_id AND pt.language_id='".(int)$language_id."' ";
			}
			if (isset($min_id) && isset($max_id)) {
				$sql .= "WHERE p.product_id BETWEEN $min_id AND $max_id ";
			}
			$sql .= "GROUP BY p.product_id ";
			$sql .= "ORDER BY p.product_id ";
			if (isset($offset) && isset($rows)) {
				$sql .= "LIMIT $offset,$rows; ";
			} else {
				$sql .= "; ";
			}
			$query = $this->db->query( $sql );
			$product_descriptions[$language_code] = $query->rows;
		}
		return $product_descriptions;
	}
        
	function populateProductsWorksheet2( &$worksheet, &$languages, $default_language_id, &$price_format, &$box_format, &$weight_format, &$text_format, $offset=null, $rows=null, &$min_id=null, &$max_id=null) {
                
		// get list of the field names, some are only available for certain OpenCart versions
		$query = $this->db->query( "DESCRIBE `".DB_PREFIX."product`" );
		$product_fields = array();
		foreach ($query->rows as $row) {
			$product_fields[] = $row['Field'];
		}

		// Set the column widths
		$j = 0;
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('product_id'),4)+1);
//                $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('categories'),12)+1);
		foreach ($languages as $language) {
			$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('name')+4,30)+1);
		}
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('quantity'),4)+1);
                $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('price'),10)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('image_name'),12)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('status'),5)+1);


		// The product headings row and column styles
		$styles = array();
		$data = array();
		$i = 1;
		$j = 0;
		$data[$j++] = 'product_id';
//                $styles[$j] = &$text_format;
//		$data[$j++] = 'categories';
		foreach ($languages as $language) {
			$styles[$j] = &$text_format;
			$data[$j++] = 'name('.$language['code'].')';
		}

		$styles[$j] = &$text_format;
		$data[$j++] = 'quantity';
                $styles[$j] = &$text_format;
		$data[$j++] = 'image_name';
		$styles[$j] = &$price_format;
		$data[$j++] = 'price';
                $styles[$j] = &$text_format;
		$data[$j++] = 'status';

		$worksheet->getRowDimension($i)->setRowHeight(30);
		$this->setCellRow( $worksheet, $i, $data, $box_format );

                $exist_meta_title=0;
                
		// The actual products data
		$i += 1;
		$j = 0;

		$products = $this->getProducts( $languages, $default_language_id, $product_fields, $exist_meta_title, $offset, $rows, $min_id, $max_id );
                
		$len = count($products);
		$min_id = $products[0]['product_id'];
		$max_id = $products[$len-1]['product_id'];
		foreach ($products as $row) {
			$data = array();
			$worksheet->getRowDimension($i)->setRowHeight(26);
			$product_id = $row['product_id'];
                        
			$data[$j++] = $product_id;
//                        $data[$j++] = $row['categories'];
			foreach ($languages as $language) {
				$data[$j++] = html_entity_decode($row['name'][$language['code']],ENT_QUOTES,'UTF-8');
			}
			$data[$j++] = $row['quantity'];
			$data[$j++] = $row['image_name'];
                         $data[$j++] = $row['price'];                       
			$data[$j++] = ($row['status']==0) ? 'false' : 'true';
			
			$this->setCellRow( $worksheet, $i, $data, $this->null_array, $styles );
			$i += 1;
			$j = 0;
		}
	}
        
	public function isProductId($id) {

                $query = $this->db->query("SELECT EXISTS (SELECT * FROM `product` WHERE product_id = ".$id.")" );
		if ($query) {
			$count = 1;
		} else {
			$count = 0;
		}
		return $count;
	}  

          
}
?>