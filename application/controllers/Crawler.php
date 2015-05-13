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
            'name' => $json['name'],
            'product_id' => $json['product_id']
        );

        foreach ($cat_link as $category) {
            foreach ($category as $key => $value) {
                $cat = array(
                    'alias' => $key,
                    'name' => $value,
                    'product' => $cat_product
                );
                //check exist category alias name
                /*$isExist = $this->cimongo->get('category')->where(array('alias' => $key));
                if(count($isExist) == 0){
                    $this->cimongo->insert('category',$json);
                }*/
                $this->cimongo->insert('category',$cat);
                $myfile = fopen("debug.txt", "w") or die("Unable to open file!");
                fwrite($myfile, json_encode($cat));
                fclose($myfile);
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

    
}