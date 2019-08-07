<?php

namespace App\Common;

class CommonHelper
{
  public static function respond_json($retCode, $message, $data_arr = []){
		$curtime = date("Y-m-d h:i:sa");
		$retval = [
      'status_code' => $retCode,
			'message'  => $message,
			'time' => $curtime,
			'data' => $data_arr
		];

		return $retval;

	}

}
