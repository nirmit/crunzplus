<?php

namespace Crunz;

class Invoker {
    public function call( $closure, array $parameters = [], $buffer = false ) {
        if ( $buffer ) {
            ob_start();
        }
        $rslt = call_user_func_array( $closure, $parameters );
        if ( $buffer ) {
            return ob_get_clean();
        }

        return $rslt;
    }
}