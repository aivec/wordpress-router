<?php
namespace Aivec\WordPress\Routing;

/**
 * This class is a general request handler for POST and AJAX requests
 * and should be extended by any controller.
 */
class Dispatcher {
   
    /**
     * Calls model post request handler method.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string     $request  the name of the model handler method
     * @param mixed|null $obj  the model object
     * @param array|null $payload
     * @return void
     */
    protected function processPOST($request, $obj = null, $payload = null) {
        if ($obj === null) {
            call_user_func($request, $payload);
        } else {
            $obj->{$request}($payload);
        }
    }

    /**
     * Calls model ajax request handler method and then dies with the response.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string     $request  the name of the model handler method
     * @param mixed|null $obj  the model object
     * @param array|null $payload
     * @return void
     */
    protected function processAJAX($request, $obj = null, $payload = null) {
        $res = null;
        if ($obj === null) {
            $res = call_user_func($request, $payload);
        } else {
            $res = $obj->{$request}($payload);
        }
        if (empty($res)) {
            die(0);
        }
        die($res);
    }
}
