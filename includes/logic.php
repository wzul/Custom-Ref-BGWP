<?php

if (!defined('ABSPATH')) {
    exit;
}

class CRBGWP_Logic
{
    private static $instance;

    const KEY = [
      'reference_1' => 'billplz_reference_1', 
      'reference_2' => 'billplz_reference_2',
      'reference_1_label' => 'billplz_reference_1_label',
      'reference_2_label' => 'billplz_reference_2_label',
    ];

    private function __construct()
    {
        add_filter('give_billplz_bill_optional_param', array($this, 'filter_reference'), 10, 2);
    }

    public static function get_instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function filter_reference($post_data, $optional)
    {
      foreach (self::KEY as $key => $value){
        if (isset($post_data[$value]) && !empty($post_data[$value])){
          $optional[$key] = $this->identify_post_data_type($post_data[$value]);
        }
      }
      return $optional;
    }

    private function identify_post_data_type($post_data_value)
    {
      if (is_array($post_data_value)){
        return join("", $post_data_value);
      }
      return $post_data_value;
    }

}
CRBGWP_Logic::get_instance();
