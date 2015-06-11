<?php

namespace Iplox\Mvc;
use Iplox\HttpStatus;

class RestController extends Controller {

    public function ok($data, $status = HttpStatus::OK) {
      if(!empty($data)){
        header('Content-type: application/json');
        echo json_encode($data);
      }
    }

    public function error($error, $status) {

    }
}
