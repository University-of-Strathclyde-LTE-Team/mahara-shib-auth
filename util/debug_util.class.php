<?php

define('DEBUG', false);

if (!function_exists('debug')) {
    if (DEBUG) {
        function debug($value) {
            DebugUtil::show($value, '', 1);
        }

    } else {
        function debug($value) {
            return false;
        }

    }
}

if (DEBUG) {
    DebugUtil::set_default_error_handler();
}

/**
 * Helper class used for debuging
 * University of Geneva
 * @author nicolas rod
 *
 */
class DebugUtil {

    private static $filters = array();
    private static $instance = null;
    private static $active = true;

    public static function is_active() {
        return self::$active;
    }

    public static function enable() {
        self::$active = true;
    }

    public static function disable() {
        self::$active = false;
    }

    public static function add_filter($regex) {
        self::$filters[] = $regex;
    }

    public static function get_filters() {
        return self::$filters;
    }

    /**
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @param array $errcontext
     */
    public static function default_error_handler($errno, $errstr, $errfile, $errline, $errcontext) {
        if (!self::$active || !DEBUG) {
            return;
        }
        foreach (self::$filters as $filter) {
            if (preg_match($filter, $errfile)) {
                return;
            }
        }

        /* if($errno == E_DEPRECATED ){
          return;
          } */
        $err_name = self::get_error_name($errno);

        echo('<div style="background:#FFFF55;">');
        echo("<b>$err_name</b><br/>");
        echo($errstr);
        echo('<br/>');
        echo('<br/>');
        echo("File: $errfile ");
        echo('<br/>');
        echo("Line: $errline");
        echo('<br/>');
        echo('<br/>');
        echo("<b>Call</b><br/>");
        echo(self::get_call(2));
        echo('<br/>');
        echo('<br/>');
        echo('<b>Trace</b>');
        echo('<br/>');
        echo(self::print_backtrace_html(null, 3));
        echo('<br/>');
        echo("<b>Arguments</b><br/>");
        echo('</div>');
        if (!($errno == E_DEPRECATED || $errno == E_NOTICE || $errno == E_WARNING )) {
            exit;
        }
    }

    /**
     *
     * @param Exception $exception
     */
    public static function default_exception_handler($exception) {
        if (!self::$active || !DEBUG) {
            return;
        }
        $result = '';
        $result .= '<div style="background:#FFFF55;">';
        $result .= '<hr/>';
        $result .= '<b>Exception:</b> ' . $exception->getCode() . '<br/>';
        $result .= $exception->getMessage();
        $result .= '<br/>';
        $result .= '<br/>';
        $result .= 'File: ' . $exception->getFile();
        $result .= '<br/>';
        $result .= 'Line: ' . $exception->getLine();
        $result .= '<br/>';
        $result .= '<br/>';
        $result .= "<b>Call</b><br/>";
        $result .= self::call_to_text(reset($exception->getTrace()));
        $result .= '<br/>';
        $result .= '<br/>';
        $result .= '<b>Trace</b>';
        $result .= '<br/>';
        $result .= self::trace_to_html($exception->getTrace());
        $result .= '<br/>';
        $result .= '<br/>';
        $result .= '<b>Exception handler trace</b>';
        $result .= '<br/>';
        $result .= self::trace_to_html(null, 2);
        $result .= '<hr/>';
        $result .= '</div>';
        $result .= '<br/>';
        echo($result);
        //debug($exception);
    }

    public static function print_exception($exception) {
        self::default_exception_handler($exception);
    }

    public static function get_call($mixed) {
        if (is_int($mixed)) {
            $backtrace = debug_backtrace();
            for ($i = 0; $i < $mixed; $i++) {
                array_shift($backtrace);
            }
            $call = $backtrace[0];
        } else {
            $call = $mixed;
        }
        return self::call_to_text($call);
    }

    public static function call_to_text($call) {
        $args = $call['args'];
        $result = '';
        if (isset($call['class'])) {
            $result .= $call['class'] . '.';
        }
        $result .= $call['function'] . '(';
        foreach ($args as $arg) {
            if (is_object($arg)) {
                $result .= get_class($arg);
            } else {
                $result .= (string) $arg;
            }
            $result .= ', ';
        }
        $result = trim($result, ', ');
        $result .= ')';
        return $result;
    }

    public static function trace_to_html($trace = null, $skip=0) {
        if (empty($trace)) {
            $trace = debug_backtrace();
        }
        $result = '';
        for ($i = 0; $i < $skip; $i++) {
            array_shift($trace);
        }
        $result .= '<table cellspacing="5px"><tbody>';
        foreach ($trace as $call) {

            $args = $call['args'];
            $arg_result = '(';
            foreach ($args as $arg) {
                if (is_object($arg)) {
                    $arg_result .= get_class($arg);
                } else {
                    $arg_result .= (string) $arg;
                }
                $arg_result .= ', ';
            }
            $arg_result = trim($arg_result, ', ');
            $arg_result .= ')';

            $result .= '<tr>';
            $result .= '<td>';
            if (isset($call['class'])) {
                $result .= $call['class'];
            }
            $result .= '<td>';
            $result .= '</td><td>';
            $result .= $call['function'];
            $result .= '</td><td>';
            $result .= $arg_result;
            $result .= '</td><td>';
            $result .= isset($call['file']) ? basename($call['file'], '.php') : '';
            $result .= '</td><td>';
            $result .= isset($call['line']) ? $call['line'] : '';
            $result .= '</td><td>';
            $result .= isset($call['file']) ? basename(dirname($call['file'])) : '';
            $result .= '</td>';
            $result .= '</tr>';
        }
        $result .= '</table></tbody>';
        return $result;
    }

    public static function print_backtrace_html($trace = null, $skip=1) {
        return self::trace_to_html($trace, $skip);
    }

    public static function set_default_error_handler() {
        if (!DEBUG) {
            return false;
        }
        $filter = '#C:\wamp\www\chamilo\repository\lib\content_object.class.php.*#';
        $filter = str_replace('\\', '\\\\', $filter);
        self::$filters[] = $filter;

        $filter = '#C:\wamp\www\chamilo\common\.*#';
        $filter = str_replace('\\', '\\\\', $filter);
        self::$filters[] = $filter;

        $flag = (E_ALL) & ~(E_DEPRECATED);
        $result = set_error_handler(array(__CLASS__, 'default_error_handler'), $flag);
        $result = set_exception_handler(array(__CLASS__, 'default_exception_handler'));
        return $result;
    }

    public static function get_error_name($error_number) {
        switch ($error_number) {
            case E_USER_ERROR:
                return 'User Error';

            case E_USER_WARNING:
                return 'User Warning';

            case E_USER_NOTICE:
                return 'User Notice';

            case E_NOTICE:
                return 'Notice';

            case E_ERROR:
            case E_CORE_ERROR:
                return 'Error';

            case E_PARSE:
            case E_COMPILE_ERROR:
                return 'Compile';

            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
                return 'Warning';

            case E_STRICT:
                return 'Strict';

            case E_DEPRECATED:
                return 'Deprecated';

            default:
                return "Unknown error type ($error_number)";
        }
    }

    public static function show($object, $title = null, $backtrace_index = 0) {
        $f = array($object, 'debug');
        if (is_callable($f)) {
            $object = call_user_func($f);
        }

        echo '<div class="debug">';

        $calledFrom = debug_backtrace();
        echo '<strong>' . $calledFrom[$backtrace_index]['file'] . '</strong>';
        echo ' (line <strong>' . $calledFrom[$backtrace_index]['line'] . '</strong>)';

        if (isset($title)) {
            echo '<h3>' . $title . '</h3>';
        }

        echo ('<pre>');
        if (is_array($object)) {
            print_r($object);
        } elseif (is_a($object, 'DOMDocument')) {
            echo 'DOMDocument:<br/><br/>';

            $object->formatOutput = true;
            $xml_string = $object->saveXML();
            echo htmlentities($xml_string);
        } elseif (is_a($object, 'DOMNodeList') || is_a($object, 'DOMElement')) {
            $dom = new DOMDocument();
            $debugElement = $dom->createElement('debug');
            $dom->appendChild($debugElement);

            if (is_a($object, 'DOMNodeList')) {
                echo 'DOMNodeList:<br/><br/>';

                foreach ($object as $node) {
                    $node = $dom->importNode($node, true);
                    $debugElement->appendChild($node);
                }
            } elseif (is_a($object, 'DOMElement')) {
                echo 'DOMElement:<br/><br/>';

                $node = $dom->importNode($object, true);
                $debugElement->appendChild($node);
            }

            $dom->formatOutput = true;
            $xml_string = $dom->saveXML();
            echo htmlentities($xml_string);
        } elseif (is_object($object)) {
            echo print_r($object);
        } else {
            echo $object;
        }

        echo ('</pre>');
        echo '</div>';
    }

}

?>
