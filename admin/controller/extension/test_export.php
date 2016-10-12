<?php 
class ControllerExtensionTestExport extends Controller { 
	private $error = array();
	
	public function index() {
		$this->load->language('extension/test_export');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('extension/test_export');
		$this->getForm();
	}


	public function download() {
		$this->load->language( 'extension/test_export' );
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('extension/test_export');
                $this->model_extension_test_export->download2();

	}


	public function upload() {
		$this->load->language('extension/test_export');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('extension/test_export');
		if (($this->request->server['REQUEST_METHOD'] == 'POST') ) {
			if ((isset( $this->request->files['upload'] )) && (is_uploaded_file($this->request->files['upload']['tmp_name']))) {
				$file = $this->request->files['upload']['tmp_name'];
				
				if ($this->model_extension_test_export->upload2($file)===true) {
					$this->session->data['success'] = $this->language->get('text_success');
					$this->response->redirect($this->url->link('extension/test_export', 'token=' . $this->session->data['token'], 'SSL'));
				}
				else {
					$this->error['warning'] = $this->language->get('error_upload');
					if (defined('VERSION')) {
						if (version_compare(VERSION,'2.0.0.0') > 0) {
							$this->error['warning'] .= "<br />\n".$this->language->get( 'text_log_details_2_0_x' );
						} else
							$this->error['warning'] .= "<br />\n".$this->language->get( 'text_log_details_1_9_x' );
					} else {
						$this->error['warning'] .= "<br />\n".$this->language->get( 'text_log_details' );
					}
				}
			}
		}

		$this->getForm();
	}


	protected function return_bytes($val)
	{
		$val = trim($val);
	
		switch (strtolower(substr($val, -1)))
		{
			case 'm': $val = (int)substr($val, 0, -1) * 1048576; break;
			case 'k': $val = (int)substr($val, 0, -1) * 1024; break;
			case 'g': $val = (int)substr($val, 0, -1) * 1073741824; break;
			case 'b':
				switch (strtolower(substr($val, -2, 1)))
				{
					case 'm': $val = (int)substr($val, 0, -2) * 1048576; break;
					case 'k': $val = (int)substr($val, 0, -2) * 1024; break;
					case 'g': $val = (int)substr($val, 0, -2) * 1073741824; break;
					default : break;
				} break;
			default: break;
		}
		return $val;
	}

	protected function getForm() {
		$data = array();
		$data['heading_title'] = $this->language->get('heading_title');
		
	

//		$data['text_export_type_category'] = ($data['exist_filter']) ? $this->language->get('text_export_type_category') : $this->language->get('text_export_type_category_old');
//		$data['text_export_type_product'] = ($data['exist_filter']) ? $this->language->get('text_export_type_product') : $this->language->get('text_export_type_product_old');
		$data['text_export_type_poa'] = $this->language->get('text_export_type_poa');
		$data['text_export_type_option'] = $this->language->get('text_export_type_option');
		$data['text_export_type_attribute'] = $this->language->get('text_export_type_attribute');
		$data['text_export_type_filter'] = $this->language->get('text_export_type_filter');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_loading_notifications'] = $this->language->get( 'text_loading_notifications' );
		$data['text_retry'] = $this->language->get('text_retry');
                $data['text_ready'] = $this->language->get('text_ready');

		$data['entry_export'] = $this->language->get( 'entry_export' );
		$data['entry_import'] = $this->language->get( 'entry_import' );
		$data['entry_export_type'] = $this->language->get( 'entry_export_type' );
		$data['entry_range_type'] = $this->language->get( 'entry_range_type' );
		$data['entry_start_id'] = $this->language->get( 'entry_start_id' );
		$data['entry_start_index'] = $this->language->get( 'entry_start_index' );
		$data['entry_end_id'] = $this->language->get( 'entry_end_id' );
		$data['entry_end_index'] = $this->language->get( 'entry_end_index' );
		$data['entry_incremental'] = $this->language->get( 'entry_incremental' );
		$data['entry_upload'] = $this->language->get( 'entry_upload' );
		$data['entry_settings_use_option_id'] = $this->language->get( 'entry_settings_use_option_id' );
		$data['entry_settings_use_option_value_id'] = $this->language->get( 'entry_settings_use_option_value_id' );
		$data['entry_settings_use_attribute_group_id'] = $this->language->get( 'entry_settings_use_attribute_group_id' );
		$data['entry_settings_use_attribute_id'] = $this->language->get( 'entry_settings_use_attribute_id' );
		$data['entry_settings_use_filter_group_id'] = $this->language->get( 'entry_settings_use_filter_group_id' );
		$data['entry_settings_use_filter_id'] = $this->language->get( 'entry_settings_use_filter_id' );
		$data['entry_settings_use_export_cache'] = $this->language->get( 'entry_settings_use_export_cache' );
		$data['entry_settings_use_import_cache'] = $this->language->get( 'entry_settings_use_import_cache' );

		$data['tab_export'] = $this->language->get( 'tab_export' );
		$data['tab_import'] = $this->language->get( 'tab_import' );
		$data['tab_settings'] = $this->language->get( 'tab_settings' );

		$data['button_export'] = $this->language->get( 'button_export' );
		$data['button_import'] = $this->language->get( 'button_import' );
		$data['button_settings'] = $this->language->get( 'button_settings' );
		$data['button_export_id'] = $this->language->get( 'button_export_id' );
		$data['button_export_page'] = $this->language->get( 'button_export_page' );

		$data['help_range_type'] = $this->language->get( 'help_range_type' );
		$data['help_incremental_yes'] = $this->language->get( 'help_incremental_yes' );
		$data['help_incremental_no'] = $this->language->get( 'help_incremental_no' );
//		$data['help_import'] = ($data['exist_filter']) ? $this->language->get( 'help_import' ) : $this->language->get( 'help_import_old' );
		$data['help_format'] = $this->language->get( 'help_format' );

		$data['error_select_file'] = $this->language->get('error_select_file');
		$data['error_post_max_size'] = str_replace( '%1', ini_get('post_max_size'), $this->language->get('error_post_max_size') );
		$data['error_upload_max_filesize'] = str_replace( '%1', ini_get('upload_max_filesize'), $this->language->get('error_upload_max_filesize') );
		$data['error_id_no_data'] = $this->language->get('error_id_no_data');
		$data['error_page_no_data'] = $this->language->get('error_page_no_data');
		$data['error_param_not_number'] = $this->language->get('error_param_not_number');
		$data['error_notifications'] = $this->language->get('error_notifications');
		$data['error_no_news'] = $this->language->get('error_no_news');
		$data['error_batch_number'] = $this->language->get('error_batch_number');
		$data['error_min_item_id'] = $this->language->get('error_min_item_id');

		if (!empty($this->session->data['test_export_error']['errstr'])) {
			$this->error['warning'] = $this->session->data['test_export_error']['errstr'];
		}

 		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
			if (!empty($this->session->data['test_export_nochange'])) {
				$data['error_warning'] .= "<br />\n".$this->language->get( 'text_nochange' );
			}
		} else {
			$data['error_warning'] = '';
		}

		unset($this->session->data['test_export_error']);
		unset($this->session->data['test_export_nochange']);

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
		
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/test_export', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['back'] = $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL');
		$data['button_back'] = $this->language->get( 'button_back' );
		$data['import'] = $this->url->link('extension/test_export/upload', 'token=' . $this->session->data['token'], 'SSL');
		$data['export'] = $this->url->link('extension/test_export/download', 'token=' . $this->session->data['token'], 'SSL');
		$data['settings'] = $this->url->link('extension/test_export/settings', 'token=' . $this->session->data['token'], 'SSL');
		$data['post_max_size'] = $this->return_bytes( ini_get('post_max_size') );
		$data['upload_max_filesize'] = $this->return_bytes( ini_get('upload_max_filesize') );



		$data['token'] = $this->session->data['token'];

		$this->document->addStyle('view/stylesheet/test_export.css');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');


		$this->response->setOutput($this->load->view('extension/test_export.tpl', $data));
	}


	public function getNotifications() {
		sleep(1); // give the data some "feel" that its not in our system
                $this->load->language('extension/test_export');
                $json = array();
                $json['message'] = $this->language->get('text_ready');
                $this->response->setOutput(json_encode($json));
                
	}
}
?>