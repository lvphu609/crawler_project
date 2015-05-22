<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Crawler extends CI_Controller
{
    public function __construct(){
        parent::__construct();
        $this->load->library('cimongo/cimongo');
    }

    public function index(){

        $query = $this->cimongo->get('category');

        foreach ($query->result_array() as $id => $post)
        {
            echo "<pre>";
            var_dump($post);
        }
    }
    
    public function save(){
        //read json file
        $str = file_get_contents('robot/export/data.json');
        $json = json_decode($str, true); 

        //category
        $cat_link = $json['cat_link'];
        $cat_product = array(
            0 => array(
                'name' => $json['name'],
                'product_id' => $json['product_id'],
                'price' => $json['price'],
                'image' => $json['images'][0]
            )
        );

        $count = count($cat_link);
        $brand_name = "";
        $brand_alias = "";
        foreach ($cat_link[$count-1] as $key => $value) {
            $brand_alias = $key;
            $brand_name = $value;
        }

        $brand_name_replace = "";
        foreach ($cat_link[$count-2] as $key => $value) {
            $brand_name_replace = trim($value);
        }

        foreach ($cat_link as $i => $category) {
            foreach ($category as $key => $value) {
                $cat = array(
                    'alias' => $key,
                    'name' => $value,
                    'cat_link' => $cat_link,
                    'product' => $cat_product
                );
                
                //check exist category alias name, add record for category
                $isExist = $this->cimongo->get_where('category',array('alias' => $key));
                if(count($isExist->result_array()) == 0){
                    //insert new category
                    $this->cimongo->insert('category',$cat);                    
                }else{
                    //update category
                    $arrayProd = $isExist->result_array();
                    $arrayProd = $arrayProd[0]['product'];
                    array_push($arrayProd,$cat_product[0]);
                    $this->cimongo->where(array('alias' => $key))->set(array('product' => $arrayProd))->update('category');
                }

                //check brand name
                if($count-1 == $i+1){
                    $breadcrumb_name = $value;
                }  

                //check last category, add record for brand collection, main category collection
                
                $catUnset = $cat;
                unset($catUnset['product']);
                unset($catUnset['cat_link']);

                if($count == $i+1){
                    //check exist category alias name, add record for brand collection
                    $isExistBrand = $this->cimongo->get_where('brand',array('alias' => $key));
                    if(count($isExistBrand->result_array()) == 0){
                        //insert brand
                        $catUnset['name'] = trim(str_replace($breadcrumb_name,"", $catUnset['name']));
                        $this->cimongo->insert('brand',$catUnset);                    
                    }                    
                }

                //check first category, add record for main category collection
                if($i==0){ 
                    //check exist main category  
                    
                    //child
                    $catUnset['child'] = array(0=>$cat_link[1]);                 
                    
                    //brand
                    $brand_name = trim(str_replace($brand_name_replace,"", $brand_name));
                    $brandName = array($brand_alias => $brand_name);
                    $catUnset['brand'] = array(0 => $brandName);

                    $isExistMainCat = $this->cimongo->get_where('main_cat',array('alias' => $key));
                    if(count($isExistMainCat->result_array()) == 0){
                        //insert main category                        
                        $this->cimongo->insert('main_cat',$catUnset);
                    }else{
                        //update category
                        $isExistMainCat = $isExistMainCat->result_array();

                        //check child
                        $mainCatChild = $isExistMainCat[0]['child'];
                        if(!in_array($cat_link[1], $mainCatChild)){
                            array_push($mainCatChild,$cat_link[1]);
                        }
                        //check brand
                        $mainCatBrand = $isExistMainCat[0]['brand'];
                        if(!in_array($brandName, $mainCatBrand)){
                            array_push($mainCatBrand,$brandName);
                        }

                        $arrTemp = array(
                            'child' => $mainCatChild,
                            'brand' => $mainCatBrand
                        );
                        $this->cimongo->where(array('alias' => $key))->set($arrTemp)->update('main_cat');
                    }
                } 

                 //check product coupon
                foreach ($json['price'] as $price) {
                    if($price['old'] != "" && $price['curr'] !=""){
                        //check exist coupon alias name, add record for coupon collection
                        $isExistCoupon = $this->cimongo->get_where('coupon',array('alias' => $key));
                        if(count($isExistCoupon->result_array()) == 0){
                            //insert new coupon
                            $this->cimongo->insert('coupon',$cat);                    
                        }else{
                            //update category
                            $arrayProd = $isExistCoupon->result_array();
                            $arrayProd = $arrayProd[0]['product'];
                            array_push($arrayProd,$cat_product[0]);
                            $this->cimongo->where(array('alias' => $key))->set(array('product' => $arrayProd))->update('coupon');
                        }
                    }
                }

            }
        }
        //product
        $this->cimongo->insert('product',$json);
    }

    public function shell(){
        shell_exec('cd robot/ && casperjs crawler.js');
    }

    public function test(){
        $isExist = $this->cimongo->get_where('category',array('alias' => "dien_thoai_may_tinh_bang"));
        $a = $isExist->result_array();
        var_dump($a[0]['product']);
    }

    public function split_file(){
        shell_exec('cd robot/xml_split && split -b 5242880 sitemap-products.xml');
    }

    public function split(){
        ini_set("memory_limit", "-1");
        set_time_limit(0);

        $source = "robot/xml_split/sitemap-products.xml";
         // load as string
         $xmlstr = file_get_contents($source);
         $xmlcont = new SimpleXMLElement($xmlstr);
         $count = 1;
         $head = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
         $record = "";
         $fileId = 1;
         $countLink = count($xmlcont);
         $xmlFileNames = $head;
         foreach($xmlcont as $key => $item) 
         {
            $row = '<url><loc>'.$item->loc.'</loc></url>';

            if($count <= 500){
                
                if($count == 1 && $fileId == 1){
                    $record .= $head;
                }               

                $record .= $row;

                // the last loop
                if(($fileId*500)+$count ==  $countLink){
                    $filename = "robot/xml_files/sitemap-products".($fileId+1).".xml";
                    $xmlFileNames .= '<url><filename>'.substr($filename,6).'</filename></url>';
                    $file = fopen($filename,"w");
                    fwrite($file,$this->formatXmlString($record));
                    fclose($file);
                }

                $count ++;
            }
            else{
                $count = 1;
                $filename = "robot/xml_files/sitemap-products".$fileId.".xml";
                $xmlFileNames .= '<url><filename>'.substr($filename,6).'</filename></url>';
                $file = fopen($filename,"w");
                fwrite($file,$this->formatXmlString($record));
                fclose($file);

                $fileId++;
                $record = "";
                $record = $head.$row;
            }
         }

        // file xml names
        $filename = "robot/xml_files/sitemap-products-names.xml";
        $file = fopen($filename,"w");
        fwrite($file,$this->formatXmlString($xmlFileNames));
        fclose($file);

    }
    
    function formatXmlString($xml){
        $xml = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $xml);
        $token      = strtok($xml, "\n");
        $result     = '';
        $pad        = 0; 
        $matches    = array();
        while ($token !== false) : 
            if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) : 
              $indent=0;
            elseif (preg_match('/^<\/\w/', $token, $matches)) :
              $pad--;
              $indent = 0;
            elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) :
              $indent=1;
            else :
              $indent = 0; 
            endif;
            $line    = str_pad($token, strlen($token)+$pad, ' ', STR_PAD_LEFT);
            $result .= $line . "\n";
            $token   = strtok("\n");
            $pad    += $indent;
        endwhile; 
        return $result;
    }
}