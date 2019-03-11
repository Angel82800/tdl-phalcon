<?php

namespace Thrust\Controllers;

use Thrust\Models\Api\Utm;

class UtmController extends ApiBaseController
{

   /*
	* endpoint: /utm/grains
	* method: GET
	* header: UDID
	*/
	public function grainsAction()
    {
        if ($this->request->isGet()) {
			$utm = new utm;
			$grainsFile = $utm->getGrains($this->UDID);
		//Not GET
        } else {
            $this->sendResponse(400, "Bad Method");
			return;
        }
    }
}
