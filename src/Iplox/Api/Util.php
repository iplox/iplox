<?php

namespace Iplox\Api {
		class Utils {
		  public $config;
		  public function __construct($config){
		      $this->config = $config;
		  }
		  public static function addSuffixe($current, $suffixe){
		      $a = preg_split("/\./", $current);
		      if(count($a) > 0){
		          $ext = $a[count($a) - 1];
		          $regex = "/(\.".$ext. ")$/";
		          $name = preg_replace($regex, '', $current);
		          return $name.$suffixe.".".$ext;
		      }
		      else {
		          return $current;
		      }
		  }
		  
		  public static function getExt($file){
		      $a = preg_split("/\./", $file);
		      $ext = $a[count($a) - 1];
		      return $ext;
		  }
		  
		  public static function getQSArray($qs){            
		      $arr = explode('&', $qs);
		      foreach($arr as $param){
		          $param = explode('=', $param);
		          $data[$param[0]]= $param[1];
		      }
		      return $data;
		  }
		  public function getDir(){
		      return ($this->config['ssl'] ? 'https://' : 'http://') .$this->config['site'].$this->config['dir'];
		  }
		  
		  public function getSite(){
		      return ($this->config['ssl'] ? 'https://' : 'http://') .$this->config['site'];
		  }
		          
		  public function getAPIDir(){
		      return ($this->config['ssl'] ? 'https://' : 'http://').$this->config['site'].$this->config['local_api'];
	  	}
		}
	}

?>
