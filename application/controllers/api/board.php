<?php
/**
 * Board API Controller
 * Sends response according to client requests
 * 
 * @package Cubet Board
 * @subpackage WebServices 
 * @category API
 * @copyright (c) 2007 - 2013, Cubet Technologies (http://cubettechnologies.com)
 * @since 29-05-2013
 * @author Robin <robin@cubettech.com>
 */

require APPPATH.'/libraries/REST_Controller.php';

class Board extends REST_Controller    {

    function __construct() {
        parent::__construct();
        //$this->sitelogin->entryCheck();
        $this->load->library('AuthAPI');
        $this->load->helper('url');
        $this->load->helper('pinterest_helper');
        $this->load->model('board_model');
        $this->load->model('api/apiaction_model');
        $this->load->model('api/apiaccount_model');
        define('XML_HEADER', 'board');
    }
    
    /**
     * List all pins
     * @since 29 May 2013
     * @author Robin <robin@cubettech.com>
     */
    public function index_get(){       
        $key = $this->get('key');
        $token = $this->get('token');
        
        $is_authenticated = $this->authapi->authenticate($key, $token);
            
        //Check if user is authenticated, if not, return error response
        if($is_authenticated == 0) 
        {
            $this->response(array('error' =>  'Authentication Failed'), 401);
        }
        
        //$config['base_url'] = site_url().'test/getAllpins';
        //$config['uri_segment']          = $this->uri->segment(3,2);

        $filter = $this->get('filter');
        $user_id = $this->get('user_id') ? $this->get('user_id') : false;
        $board_id = $this->get('board_id') ? $this->get('board_id') : false;
        $limit = $this->get('limit') ? $this->get('limit') : 20;
        $offset = $this->get('offset') ? $this->get('offset') : 0;

        if(! $filter || $filter == 'all') {
            $result = $this->apiaction_model->get_pins($user_id, $board_id, $order, $limit, $offset);
            if (empty($result)) {
                $this->response(array('error' => 'Sorry No results here!'), 200);
            }
        } else if ($filter == 'liked') {
            $result = $this->apiaction_model->get_most_liked($limit, $nextOffset);
            if (empty($result)) {
                $this->response(array('error' => 'Sorry No results here!'), 200);
            }
        } else if ($filter == 'repin') {
            $result = $this->apiaction_model->get_most_repinned($limit, $nextOffset);
            if (empty($result)) {
                $this->response(array('error' => 'Sorry No results here!'), 200);
            }
        } else {
            $this->response(array('error' => 'Invalid Filter!'), 200);
        }
        //send response
        define('XML_KEY', 'item');
        foreach($result as $key => $pin){
            $owner = $this->apiaccount_model->get_user($pin['user_id']);
            $board = $this->apiaction_model->get_board($pin['board_id']);
            $repin = $this->apiaction_model->get_repin_source($pin['id']);
            if($repin) {
                 $result[$key]['repined'] = true;
                 $result[$key]['repined_from'] = $repin['from_pin_id'];
            } else {
                 $result[$key]['repined'] = false;
            }
            $result[$key]['owner_name'] = $owner['first_name'].' '.$owner['last_name'];
            $result[$key]['owner_img'] = $owner['image'];
            $result[$key]['board_name'] = $board['board_name'];
        }
        
        $this->response($result, 200);
    }
    
    /**
     * Create New board
     * @since 31 May 2013
     * @author Robin <robin@cubettech.com>
     */
    public function createBoard_get(){
        $key = $this->get('key');
        $token = $this->get('token');
        
        $is_authenticated = $this->authapi->authenticate($key, $token);
            
        //Check if user is authenticated, if not, return error response
        if($is_authenticated == 0) 
        {
            $this->response(array('error' =>  'Authentication Failed'), 401);
        }
        
        $user_id = $this->get('user_id');
        $board_name = $this->get('board_name');
        $desc = $this->get('description');
        $category = $this->get('category');
        
        if(!$user_id || !$board_name || !$category) {
           $this->response(array('error' =>  'Give me some inputs !'), 401); 
        }
        
        $board = array( 'board_name' => $board_name,
                        'board_title' => $board_name,
                        'category' => $category,
                        'description' => $desc,
                        'who_can_tag' => 'me',
                        'user_id' => $user_id,
        );
        
        if($this->apiaction_model->create_board($board)) {
            $this->response(array('success' => 'Board Created!'), 200);
        } else {
            $this->response(array('error' => 'Something wrong!'), 200);
        }
    }
    
    /**
     * Create New board
     * @since 31 May 2013
     * @author Robin <robin@cubettech.com>
     */
    public function getBoard_get(){
        $key = $this->get('key');
        $token = $this->get('token');
        
        $is_authenticated = $this->authapi->authenticate($key, $token);
            
        //Check if user is authenticated, if not, return error response
        if($is_authenticated == 0) 
        {
            $this->response(array('error' =>  'Authentication Failed'), 401);
        }
        
        $board_id = $this->get('board_id');
        
        if(!$board_id) {
           $this->response(array('error' =>  'Give me some inputs !'), 401); 
        }
        
        if($result = $this->board_model->getBoardDetails($board_id)) {
            $this->response($result, 200);
        } else {
            $this->response(array('error' => 'Something wrong!'), 200);
        }
    }
    
    
     /**
     * Delete board
     * @since 31 May 2013
     * @author Robin <robin@cubettech.com>
     */
    public function deleteBoard_get(){
        $key = $this->get('key');
        $token = $this->get('token');
        
        $is_authenticated = $this->authapi->authenticate($key, $token);
            
        //Check if user is authenticated, if not, return error response
        if($is_authenticated == 0) 
        {
            $this->response(array('error' =>  'Authentication Failed'), 401);
        }
        
        $board_id = $this->get('board_id');
        
        if(!$board_id) {
           $this->response(array('error' =>  'Give me some inputs !'), 401); 
        }
        
        if($this->board_model->deleteBoard($board_id)) {
            $this->response(array('success' => 'Board Deleted!'), 200);
        } else {
            $this->response(array('error' => 'Something wrong!'), 200);
        }
    }
}

/* End of file board.php */ 
/* Location: ./application/controllers/api/board.php */