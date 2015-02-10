<?php

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

define('MEMCACHED_SERVER', '50.115.38.249'); //memcached服务器地址
define('MEMCACHED_PORT', '11211'); //memcached服务器端口号

class cls_memcached
{
    public $enable;
    public $obj;

    public function init($config = array()) {  
        //if(!empty($config['server'])) {
            $this->obj = new Memcached();
            $connect = $this->obj->addServer(MEMCACHED_SERVER, MEMCACHED_PORT);
            $this->enable = $connect ? true : false;
        //}
    }

    public function get($key) {
        return $this->obj->get($key);
    }

    public function getMulti($keys) {
        return $this->obj->get($keys);
    }
    public function set($key, $value, $ttl = 0) {
        return $this->obj->set($key, $value, $ttl);
    }

}

?>