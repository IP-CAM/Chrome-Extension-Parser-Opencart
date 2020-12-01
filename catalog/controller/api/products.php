<?php
require_once(DIR_APPLICATION.'../admin/model/catalog/product.php');
require_once(DIR_APPLICATION.'../admin/model/catalog/manufacturer.php');
require_once(DIR_APPLICATION.'../admin/model/catalog/category.php');

class ControllerApiProducts extends Controller {
	
	const TEST = false;
	
	 private function rus2translit($string)
    {
        $converter = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v',
            'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
            'и' => 'i', 'й' => 'y', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ь' => '', 'ы' => 'y', 'ъ' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V',
            'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
            'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
            'И' => 'I', 'Й' => 'Y', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
            'Ь' => '\'', 'Ы' => 'Y', 'Ъ' => '\'',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        );
        return strtr($string, $converter);
    }
	
	
	
	private function replace_sym($string)
    {
        $data = preg_replace_callback('/[^A-Za-z0-9_]/', function ($matches) {
            return '_';
        }, htmlentities($this->rus2translit($this->clearshar($string))));
        $data = preg_replace_callback('/_{2,}/', function ($matches) {
            return '_';
        }, $data);
        if (strlen($string) > 100) {
            $data = substr($data, 0, 100);
        }
        return $data;
    }

  
    private function clearshar($str)
    {
        $search = array("'<script[^>]*?>.*?</script>'si",
            "'<[\/\!]*?[^<>]*?>'si",
            "'([\r\n])[\s]+'",
            "'&(quot|#34);'i",
            "'&(amp|#38);'i",
            "'&(lt|#60);'i",
            "'&(gt|#62);'i",
            "'&(nbsp|#160);'i",
            "'&(iexcl|#161);'i",
            "'&(cent|#162);'i",
            "'&(pound|#163);'i",
            "'&(copy|#169);'i",
            "'&#(\d+);'i",
            "'novinka'i");
        return preg_replace_callback($search, function ($matches) {
            return '';
        }, $str);
    }
	
	
	public function login() {
		
		
		$this->load->language('api/login');

		$json = array();

		$this->load->model('account/api');

		// Login with API Key
		if(isset($this->request->post['username'])) {
			$api_info = $this->model_account_api->login($this->request->post['username'], $this->request->post['key']);
		} else {
			$api_info = $this->model_account_api->login('Default', $this->request->post['key']);
		}

		if ($api_info) {
			// Check if IP is allowed
			$ip_data = array();
	
			$results = $this->model_account_api->getApiIps($api_info['api_id']);
	
			foreach ($results as $result) {
				$ip_data[] = trim($result['ip']);
			}
	
			if (!in_array($this->request->server['REMOTE_ADDR'], $ip_data)) {
				$json['error']['ip'] = sprintf($this->language->get('error_ip'), $this->request->server['REMOTE_ADDR']);
			}				
				
			if (!$json) {
				$json['success'] = $this->language->get('text_success');
				
				$session = new Session($this->config->get('session_engine'), $this->registry);
				
				$session->start();
				
				$this->model_account_api->addApiSession($api_info['api_id'], $session->getId(), $this->request->server['REMOTE_ADDR']);
				
				$session->data['api_id'] = $api_info['api_id'];
				
				// Create Token
				$json['api_token'] = $session->getId();
			} else {
				$json['error']['key'] = $this->language->get('error_key');
			}
		}
		
		$id_chrome = 'ejfnenbeabchpggofiiidojafemfpmle'; 
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->addHeader('Access-Control-Allow-Origin: chrome-extension://'. $id_chrome);
		$this->response->setOutput(json_encode($json));
	}
	
	
	public function index() {		
		
		/*$this->session->data['success'] = 'Парсинг выполнен';
		var_dump($this->session->data);
		$this->load->model('setting/setting');	
		$settings = array('parserchrome' => array('chrome_app_ad' => 'ejfnenbeabchpggofiiidojafemfpmle'));	
		
		$from = $this->model_setting_setting->getSettingValue('parserchrome', $settings);
		var_dump($from);
			
		$setting_parser = $this->config->get('parserchrome');
		 var_dump($setting_parser);
			
		exit;		
		
		$json = array();
		
		if (!isset($this->session->data['api_id'])) {
			$json['error'] = $this->language->get('error_permission');
		} else {		
			if(isset($this->request->post['product_id'])&&isset($this->request->post['new_price'])) {
				$json['product_id'] = $this->request->post['product_id'];
				
				$product_id = $this->request->post['product_id'];
				$new_price = $this->request->post['new_price'];				
				
				$this->db->query("UPDATE " . DB_PREFIX . "product SET  price = '" . (float)$new_price   ."' WHERE product_id = '" . (int)$product_id . "'");
				
				$json['success'] = 'Данные обновлены';
				
			} else {
				$json['error'] = 'Нет ID товара';
			}		
			
		}*/
		$json['success'] = 'index';
		$this->response->addHeader('Access-Control-Allow-Origin: *');
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));	
	
	}
	
	public function getCategories() {		
		
		/*$this->session->data['success'] = 'Парсинг выполнен';
		var_dump($this->session->data);
		$this->load->model('setting/setting');	
		$settings = array('parserchrome' => array('chrome_app_ad' => 'ejfnenbeabchpggofiiidojafemfpmle'));	
		
		$from = $this->model_setting_setting->getSettingValue('parserchrome', $settings);
		var_dump($from);
			
		$setting_parser = $this->config->get('parserchrome');
		 var_dump($setting_parser);
			
		exit;		
		*/
		
		$json = array();
		
		if (!isset($this->session->data['api_id'])) {
			$json['error'] = $this->language->get('error_permission');
		} else {		
			$model = new ModelCatalogCategory($this->registry);    
			 $category= $model->getCategories(0);
			
			$json['categories'] = $category;
			$json['success'] = 'index';	
				
			
		}
		
		$this->response->addHeader('Access-Control-Allow-Origin: *');
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));	
	
	}
	
	
	
	public function updateAttributes() {
		
		$json = array();
		
		if (!isset($this->session->data['api_id'])) {
			$json['error'] = $this->language->get('error_permission');
		} else {		
			if(isset($this->request->post['product_id'])&&isset($this->request->post['attributes'])) {
				file_put_contents(DIR_DOWNLOAD.'attr.txt', html_entity_decode( $this->request->post['attributes']));
				/*
				$json['product_id'] = $this->request->post['product_id'];
				
				$product_id = $this->request->post['product_id'];
				$new_price = $this->request->post['new_price'];				
				
				$this->db->query("UPDATE " . DB_PREFIX . "product SET  price = '" . (float)$new_price   ."' WHERE product_id = '" . (int)$product_id . "'");
				*/
				$json['success'] = 'Атрибуты обновлены';
				
			} else {
				$json['error'] = 'Нет ID товара';
			}		
			
		}
		$this->response->addHeader('Access-Control-Allow-Origin: *');
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));	
	
	}
	
	
	/*************************/
	/*************************/
	/*************************/
	
	public function addProducts() {
		
		$json = array();		
		
		if (!isset($this->session->data['api_id']) /*OR !self::TEST*/) {
			$json['error'] = $this->language->get('error_permission');
			
		} else {
			
			if(isset($this->request->post['products']) OR self::TEST) {
				
				if(!self::TEST){
					file_put_contents(DIR_DOWNLOAD.'prod.txt', html_entity_decode( $this->request->post['products']));
				}
				
				$product =  json_decode(file_get_contents(DIR_DOWNLOAD.'prod.txt'));
				//$product =  json_decode($this->request->post['products']);

				
			$arr_to_prod1 = [];
			
		foreach($product->attributes as $key => $attributes){
			$array_attribute = [];
			
			foreach($attributes as $item){	
				
				$array_attribute[$item->description] = $item->value;
			}
			
			$arr_to_prod1 = array_merge($arr_to_prod1, $this->addAtributes($key, $array_attribute));
						
		}
		$data = [];
		
		
		$this->load->model('localisation/language');
        $data = array(
            'model' => $product->title,
            'price' => $product->lowPrice,
            'tax_class_id' => 1,
            'quantity' => 1,
            'minimum' => 1,
            'subtract' => 1,
            'stock_status_id' => 5,
            'shipping' => 1,
            'length_class_id' => 0,
            'weight_class_id' => 0,
            'status' => 1,
            'sort_order' => 1,
            'sku' => '',
            'upc' => '',
            'ean' => '',
            'jan' => '',
            'isbn' => '',
            'mpn' => '',
            'location' => '',
            'points' => 0,
            'weight' => 0,
            'length' => 0,
            'width' => 0,
            'height' => 0,
            'product_tag' => array(
                '',
                ''
            ),
            'keyword' => $product->title,
            'product_store' => array(
                0 => '0'
            ),
            'date_available' => date('Y-m-d')
        );
        $data['languages'] = $this->model_localisation_language->getLanguages();
        foreach ($data['languages'] as $language) {
            $data['product_description'][$language['language_id']] = array(
                'name' => $product->title,
                'seo_h1' => $product->title,
                'seo_title' => $product->title,
                'meta_keyword' => $product->title,
                'meta_title' => $product->title,
                'model' => $product->title,
                'description' => $product->description,
                'meta_description' => $product->title,
                'tag' => ''
            );
        }
		   /* if (isset($items['manufacturer_id'])) {
				$data = array_merge($data, array(
					'manufacturer_id' => 0
				));
			}
			
		   if (!empty($items['img_arr'])) {
				$nImages = array();
				foreach ($items['img_arr'] as $dataImage) {
					$nImages[] = array_merge($dataImage, array('video' => ''));
				}
				$data = array_merge($data,
					array('product_image' => $nImages));
			}
			
			*/
			
			if (isset($product->images)&&!empty($product->images)) {
				//  catalog/demo/apple_cinema_30.jpg
				// define('DIR_IMAGE', '/home/vagrant/code/oc3036/image/');
				$i = 0; 
				$k = 0; 
				$images1 = [];
				foreach ($product->images as $image){
					
					$path = 'catalog/demo/test/'.$this->replace_sym($product->title).'_'.$i.'.jpg';
					if(!file_exists(DIR_IMAGE.$path))
						$this->save_image($image, DIR_IMAGE.$path);
					
					if( $i == 0 ){
						$data = array_merge($data, 
						array(
						'image' => $path
						));
					} else { 
						
					//	$this->save_image($image, DIR_IMAGE.$path);
					
						$images1[] = array('image' => $path, 'sort_order' => '10'  );
					
						$k++;
					}
					
					
					$i++;	
				}
				
			
				$data = array_merge($data,
					array('product_image' => $images1));
					
				/*
				
				$data = array_merge($data,
					array('product_image' => $nImages));
				
				 $images1[$k]['image'] = $this->dir . $curl_image;
                    $images1[$k]['sort_order'] = '';
                    $k++;
					
				*/
				
				/*$data = array_merge($data, array(
					'images' => $path
				));*/
				
			}
			
			
			
			$data['product_attribute'] = $arr_to_prod1;
					
			$data['manufacturer_id'] = $this->setManufacture($product->brand);
			
			//$data['product_category'] = array (  '0' => '33' );
			
			$data['product_category'] = $product->productCategory;
			if(self::TEST){
				var_dump($data);
				exit;
			}
			
			$model = new ModelCatalogProduct($this->registry);
			$model->addProduct($data);
			//$json = 'ТОвар добавлен!';
			/*
			$json['product_id'] = $this->request->post['product_id'];
			
			$product_id = $this->request->post['product_id'];
			$new_price = $this->request->post['new_price'];				
			
			$this->db->query("UPDATE " . DB_PREFIX . "product SET  price = '" . (float)$new_price   ."' WHERE product_id = '" . (int)$product_id . "'");
			*/
			$json['success'] = "Товар $product->title добавлен!";
				
			} else {
				$json['error'] = 'Нет ID товара';
			}		
			
		}
		
		
		$this->response->addHeader('Access-Control-Allow-Origin: *');
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));	
	
	}
	
	private function save_image($img, $path){
		$curl = curl_init($img);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_BINARYTRANSFER,1);
		curl_setopt($curl, CURLOPT_PROXY, 'proxy.vtb.rw');
		curl_setopt($curl, CURLOPT_PROXYPORT, '3128');
		curl_setopt($curl, CURLOPT_PROXYUSERPWD,'igyan:rjymzr');
		$content = curl_exec($curl);
		curl_close($curl);
		if (file_exists($path)) :
			unlink($path);
		endif;
		$fp = fopen($path,'x');
		fwrite($fp, $content);
		fclose($fp);
}
 

	
	
	
	public function updateAttributesTest() {		
		$json = array();
		$arr_to_prod1 = [];
		$attributesPost = json_decode(file_get_contents(DIR_DOWNLOAD.'attr.txt'));
		
		foreach($attributesPost as $key => $attributes){
			$array_attribute = [];
			
			foreach($attributes as $item){	
				
				$array_attribute[$item->description] = $item->value;
			}
			
			$arr_to_prod1 = array_merge($arr_to_prod1, $this->addAtributes($key, $array_attribute));
						
		}
		
		$data = [];
		
		$this->load->model('localisation/language');
        $data = array(
            'model' => $product->title,
            'price' => $product->lowPrice,
            'tax_class_id' => 1,
            'quantity' => 1,
            'minimum' => 1,
            'subtract' => 1,
            'stock_status_id' => 5,
            'shipping' => 1,
            'length_class_id' => 0,
            'weight_class_id' => 0,
            'status' => 1,
            'sort_order' => 1,
            'sku' => '',
            'upc' => '',
            'ean' => '',
            'jan' => '',
            'isbn' => '',
            'mpn' => '',
            'location' => '',
            'points' => 0,
            'weight' => 0,
            'length' => 0,
            'width' => 0,
            'height' => 0,
            'product_tag' => array(
                '',
                ''
            ),
            'keyword' => $product->title,
            'product_store' => array(
                0 => '0'
            ),
            'date_available' => date('Y-m-d')
        );
        $data['languages'] = $this->model_localisation_language->getLanguages();
        foreach ($data['languages'] as $language) {
            $data['product_description'][$language['language_id']] = array(
                'name' => $product->title,
                'seo_h1' => $product->title,
                'seo_title' => $product->title,
                'meta_keyword' => $product->title,
                'meta_title' => $product->title,
                'model' => $product->title,
                'description' => $product->title,
                'meta_description' => $product->title,
                'tag' => ''
            );
        }
        if (isset($items['manufacturer_id'])) {
            $data = array_merge($data, array(
                'manufacturer_id' => 0
            ));
        }
       /* if (!empty($items['img_arr'])) {
            $nImages = array();
            foreach ($items['img_arr'] as $dataImage) {
                $nImages[] = array_merge($dataImage, array('video' => ''));
            }
            $data = array_merge($data,
                array('product_image' => $nImages));
        }
        if (!empty($items['attribute'])) {
            $i = 0;
            foreach ($items['attribute'] as $attribute) {
                foreach ($attribute['product_attribute_description'] as $product_attribute_description) {
                    foreach ($data['languages'] as $language) {
                        $items['attribute'][$i]['product_attribute_description'][$language['language_id']]['text'] = htmlentities(html_entity_decode($product_attribute_description['text']), ENT_QUOTES, 'UTF-8');
                    }
                }
                $i++;
            }
            $data = array_merge($data, array(
                'product_attribute' => $items['attribute']
            ));
        }
        if (isset($items['image'])) {
            $data = array_merge($data, array(
                'image' => $items['image']
            ));
        }*/
		
		$data['product_attribute'] = $arr_to_prod1;
		$data['product_categories'] = $product->productCategory;
		$model = new ModelCatalogProduct($this->registry);		
		
		$this->updateProductAttributes('30', $data);	
			
		
	
	}
	
	private function updateProductAttributes($product_id, $data){	
		if (!empty($data['product_attribute'])) {
			foreach ($data['product_attribute'] as $product_attribute) {
				if ($product_attribute['attribute_id']) {
					// Removes duplicates
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

					foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
					}
				}
			}
		}
	}
	
	
	
	private function addAtributes($attribyte_groupe, $attributes)
    {
        $arr_to_prod1 = array();
        $array_attribute = $attributes;
        
        $search_attribyte_groupe = $this->SearchAttribyteGroupe($attribyte_groupe);
		
        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();
				
        if (empty($search_attribyte_groupe)) {
            foreach ($data['languages'] as $language) {
                $data['attribute_group_description'][$language['language_id']]['name'] = $attribyte_groupe;
            }
            $data['sort_order'] = 10;            
            $this->addAttributeGroup($data);
        }
				
        $i = 100;
		$search_attribyte_groupe = $this->SearchAttribyteGroupe($attribyte_groupe);
        foreach ($attributes as $key => $attribute) {
            $searchAttByGrup = array();
            $searchAttByGrup = $this->SearchAttByGrup($key, $search_attribyte_groupe['attribute_group_id']);
            if (empty($searchAttByGrup)) {
                foreach ($data['languages'] as $language) {
                    $d['attribute_description'][$language['language_id']]['name'] = $key;
                }
                $d['attribute_group_id'] = $search_attribyte_groupe['attribute_group_id'];
                $d['sort_order'] = $i;                
                $this->addAttribute($d);
                $i++;
            }
            $searchAttByGrup = $this->SearchAttByGrup($key, $search_attribyte_groupe['attribute_group_id']);
            $arr_to_prod['name'] = $key;
            $arr_to_prod['attribute_id'] = $searchAttByGrup['attribute_id'];
            foreach ($data['languages'] as $language) {
                $arr_to_prod['product_attribute_description'][$language['language_id']] = array(
                    'text' => $attribute
                );
            }
            $arr_to_prod1[] = $arr_to_prod;
        }
        return $arr_to_prod1;
    }
	
	
	private  function SearchAttribyteGroupe($data)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "attribute_group_description WHERE name = '" . $this->db->escape($data) . "'");
        return $query->row;
    }
	
	private function addAttributeGroup($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "attribute_group SET sort_order = '" . (int)$data['sort_order'] . "'");

		$attribute_group_id = $this->db->getLastId();

		foreach ($data['attribute_group_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "attribute_group_description SET attribute_group_id = '" . (int)$attribute_group_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
		}

		return $attribute_group_id;
	}
	
	private function SearchAttByGrup($attr_name, $attr_gr)
    {
        $query = $this->db->query("SELECT  * FROM " . DB_PREFIX . "attribute_description p LEFT JOIN " . DB_PREFIX .
            "attribute pd ON (p.attribute_id = pd.attribute_id) WHERE p.name = '" . $this->db->escape($attr_name) . "' AND pd.attribute_group_id = " . $attr_gr);
        return $query->row;
    }
	
	private function addAttribute($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "attribute SET attribute_group_id = '" . (int)$data['attribute_group_id'] . "', sort_order = '" . (int)$data['sort_order'] . "'");

		$attribute_id = $this->db->getLastId();

		foreach ($data['attribute_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "attribute_description SET attribute_id = '" . (int)$attribute_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
		}

		return $attribute_id;
	}
	
	
	 private function getManufactureName($name) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "manufacturer WHERE name = '" . $this->db->escape($name) . "'");
        return $query->row;
    }
	
	private function setManufacture($manufacture)
    {
		$model = new ModelCatalogManufacturer($this->registry);       
        $searchManufacture = $this->getManufactureName($manufacture);
        if (empty($searchManufacture)) {
            $this->load->model('localisation/language');
            $data['languages'] = $this->model_localisation_language->getLanguages();           
            $manufacture_array = $this->manufacture_array;
            foreach ($data['languages'] as $language) {
                $manufacture_array['manufacturer_description'][$language['language_id']] = array(
                    'seo_h1' => $manufacture,
                    'name' => $manufacture,
                    'meta_title' => $manufacture,
                    'meta_h1' => $manufacture,
                    'seo_title' => $manufacture,
                    'meta_keyword' => $manufacture,
                    'meta_description' => '',
                    'description' => '',
                    
                );
            }
            $manufacture_array['name'] = $manufacture;
            $manufacture_array['sort_order'] = '10';
            $model->addManufacturer($manufacture_array);
            $searchManufacture = $this->getManufactureName($manufacture);
        }
        $manufacture_id = $searchManufacture['manufacturer_id'];
		unset($model);
        return $manufacture_id;
    }
	
	
}