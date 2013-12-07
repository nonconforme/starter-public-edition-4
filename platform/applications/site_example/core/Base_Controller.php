<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Base_Controller extends Core_Controller {

    public function __construct() {

        parent::__construct();

        $this->load->library('settings');

        $this->load->helper('language');
        $this->load->helper('url');
        $this->load->library('template');

        $this->language = config_item('language');

        $this->template->set('language', $this->language);

        //$default_title = config_item('default_title');
        $default_title = 'Application Starter 4 Public Edition';
        //

        $default_description = config_item('default_description');
        $default_keywords = config_item('default_keywords');

        if ($default_title != '') {
             $this->template->title($default_title);
        }

        if ($default_description != '') {
            $this->template->set_metadata('description', $default_description);
        }

        if ($default_keywords != '') {
            $this->template->set_metadata('keywords', $default_keywords);
        }

        $this->template
            ->inject_partial('css', css('welcome.css'))
        ;

    }

}