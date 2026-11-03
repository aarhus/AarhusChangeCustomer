<?php
// Uses FreeScout's real autoloader, ServiceProvider, Option and Blade compiler.
// Event registration, logs and request helpers are isolated from web/mail/DB.
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) {
    if (error_reporting() & $level) { throw new ErrorException($message, 0, $level, $file, $line); }
    return false;
});
$root = dirname(__DIR__);
$core = getenv('FREESCOUT_PATH') ?: $root.'/.compat/freescout-1.8.248';
require $core.'/vendor/autoload.php';
class_alias(App\Option::class, 'Option');
class Eventy {
    public static $actions = [];
    public static $filters = [];
    public static function addAction($name, $callback, $priority = 10, $args = 1) { self::$actions[$name] = $callback; }
    public static function addFilter($name, $callback, $priority = 10, $args = 1) { self::$filters[$name] = $callback; }
}
class Helper { public static function Log($category, $message) {} }
$GLOBALS['oldInput'] = [];
function old($key, $default = null) { return array_key_exists($key, $GLOBALS['oldInput']) ? $GLOBALS['oldInput'][$key] : $default; }
function csrf_field() { return ''; }
function __($text, $replace = [], $locale = null) { return $text; }
spl_autoload_register(function ($class) use ($root) {
    $prefix = 'Modules\\AarhusChangeCustomer\\';
    if (strpos($class, $prefix) === 0) { $file = $root.'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php'; if (is_file($file)) require_once $file; }
});
$provider = new Modules\AarhusChangeCustomer\Providers\AarhusChangeCustomerServiceProvider(new Illuminate\Container\Container());
$provider->hooks();
$hook = Eventy::$actions['conversation.created_by_customer'];
$failures = 0;
$count = 0;
function check($name, $run) {
    global $failures, $count;
    $count++;
    try { $run(); echo 'PASS '.$name.PHP_EOL; }
    catch (Throwable $e) { $failures++; echo 'FAIL '.$name.': '.get_class($e).' '.$e->getMessage().PHP_EOL; }
}
function same($expected, $actual) {
    if ($expected !== $actual) throw new RuntimeException('expected '.json_encode($expected).', got '.json_encode($actual));
}
function expectInvalid($run) {
    try { $run(); } catch (InvalidArgumentException $e) { return; }
    throw new RuntimeException('Expected InvalidArgumentException');
}
function renderSettings($value) {
    global $root;
    $compiler = new Illuminate\View\Compilers\BladeCompiler(new Illuminate\Filesystem\Filesystem(), sys_get_temp_dir());
    $compiled = $compiler->compileString(file_get_contents($root.'/Resources/views/index.blade.php'));
    $settings = ['aarhuschangecustomer.active' => 'on', 'aarhuschangecustomer.ruleset' => $value];
    $errors = new class { public function has($key) { return false; } public function first($key) { return ''; } };
    ob_start(); try { eval('?>'.$compiled); return ob_get_contents(); } finally { ob_end_clean(); }
}
function finishTests() { global $count, $failures; echo "$count checks, $failures failures".PHP_EOL; exit($failures ? 1 : 0); }
