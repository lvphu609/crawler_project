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
        /*$record = $this->input->post();

        // $object = json_encode($record);

        $this->cimongo->insert('category',json_encode($record));

        $myfile = fopen("debug.txt", "w") or die("Unable to open file!");
        fwrite($myfile, json_encode($record));
        fclose($myfile);*/

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

        foreach ($cat_link as $category) {
            foreach ($category as $key => $value) {
                $cat = array(
                    'alias' => $key,
                    'name' => $value,
                    'product' => $cat_product,
                );
                //check exist category alias name
                $isExist = $this->cimongo->get_where('category',array('alias' => $key));
                if(count($isExist->result_array()) == 0){
                    //insert new category
                    $this->cimongo->insert('category',$cat);
                }else{
                    //update category
                    $arrayProd = $isExist->result_array();
                    $arrayProd = $arrayProd[0]['product'];
                    array_push($arrayProd,$cat_product[0]);
                    

                   /* $myfile = fopen("debug.txt", "w") or die("Unable to open file!");
                    fwrite($myfile, json_encode($arrayProd));
                    fclose($myfile);*/

                    $this->cimongo->where(array('alias' => $key))->set(array('product' => $arrayProd))->update('category');
                    // $this->cimongo->update('category',$arrayProd,array('alias' => $key));
                }
            }
        }
        //product
        $this->cimongo->insert('product',$json);
    }

    public function shell(){
        shell_exec('cd robot/ && casperjs crawler.js');
    }

    /**
    *
    * Convert an object to an array
    *
    * @param    object  $object The object to convert
    * @reeturn      array
    *
    */
    function objectToArray( $object )
    {
        if( !is_object( $object ) && !is_array( $object ) )
        {
            return $object;
        }
        if( is_object( $object ) )
        {
            $object = get_object_vars( $object );
        }
        return array_map( 'objectToArray', $object );
    }

    public function test(){
        $isExist = $this->cimongo->get_where('category',array('alias' => "dien_thoai_may_tinh_bang"));
        $a = $isExist->result_array();
        var_dump($a[0]['product']);
    }

    
}