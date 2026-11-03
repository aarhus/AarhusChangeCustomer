<?php
require __DIR__.'/bootstrap.php';
$rules = ['forms@example.com' => ['mailboxes' => [4], 'matches' => [[
    '/Email: ([^<]+)/' => ['fields' => ['email' => 0]],
    '/Name: ([^<]+)/' => ['fields' => ['name' => 0]],
]]]];
$json = json_encode($rules);
$body = 'Email: person@example.com<br>Name: Sir John Doe<br>';
$conv = (object) ['customer_email' => 'forms@example.com', 'mailbox_id' => 4];
$thread = (object) ['body' => 'Nothing matches'];
echo 'PHP '.PHP_VERSION.PHP_EOL;
check('Exact FreeScout 1.8.248', function () use ($core) { $c = require $core.'/config/app.php'; same('1.8.248', $c['version']); });
check('Normalize array, JSON and whitespace JSON', function () use ($provider, $rules, $json) {
    foreach ([$rules, $json, " \n".$json] as $input) same($rules, $provider->normalizeRules($input));
});
check('Core Option decoded rules safely reach hook', function () use ($json, $hook, $conv, $thread) {
    Option::$cache = ['aarhuschangecustomer.active' => 'on', 'aarhuschangecustomer.ruleset' => App\Option::maybeUnserialize($json)];
    same($conv, $hook($conv, $thread, null));
});
check('Actual compiled Blade renders decoded JSON', function () use ($rules) {
    $html = renderSettings($rules); same(true, strpos($html, 'forms@example.com') !== false);
});
check('Blade escapes closing textarea and script', function () {
    $html = renderSettings(['x' => '</textarea><script>alert(1)</script>']);
    same(false, strpos($html, '<script>') !== false);
    same(1, substr_count($html, '</textarea>'));
});
check('Blade preserves submitted invalid JSON', function () {
    $GLOBALS['oldInput'] = ['settings' => ['aarhuschangecustomer.ruleset' => '{ invalid submitted JSON']];
    try { same(true, strpos(renderSettings([]), '{ invalid submitted JSON') !== false); }
    finally { $GLOBALS['oldInput'] = []; }
});
check('Inactive module safely returns', function () use ($hook, $conv, $thread) {
    Option::$cache = ['aarhuschangecustomer.active' => 'off', 'aarhuschangecustomer.ruleset' => '']; same($conv, $hook($conv, $thread, null));
});
check('Email and complete multipart name', function () use ($provider, $rules, $body) {
    $data = $provider->checkForMatches('forms@example.com', 4, $body, $rules);
    same('person@example.com', $data['email']); same('Sir', $data['first_name']); same('John Doe', $data['last_name']);
});
check('Name respects selected capture group and repeated whitespace', function () use ($provider) {
    $r = ['forms@example.com' => ['matches' => [['/(Label): (.+)/' => ['fields' => ['name' => 1]]]]]];
    $v = $provider->checkForMatches('forms@example.com', 4, 'Label: Jane   Mary Smith', $r);
    same('Jane', $v['first_name']); same('Mary Smith', $v['last_name']);
});
check('Unknown sender and excluded mailbox do not match', function () use ($provider, $rules, $body) {
    same([], $provider->checkForMatches('unknown@example.com', 4, $body, $rules)); same([], $provider->checkForMatches('forms@example.com', 5, $body, $rules));
});
check('Invalid email does not dereference null Customer', function () use ($json, $hook, $conv) {
    Option::$cache = ['aarhuschangecustomer.active' => 'on', 'aarhuschangecustomer.ruleset' => $json];
    same($conv, $hook($conv, (object) ['body' => 'Email: not-an-email<br>'], null));
});
check('Invalid stored JSON safely skips incoming message', function () use ($hook, $conv, $thread) {
    Option::$cache = ['aarhuschangecustomer.active' => 'on', 'aarhuschangecustomer.ruleset' => '{broken']; same($conv, $hook($conv, $thread, null));
});
check('Malformed schema and regex are rejected without PHP warnings', function () use ($provider, $rules, $body) {
    $bad = $rules; $bad['forms@example.com']['mailboxes'] = 4;
    $regex = ['forms@example.com' => ['matches' => [['/unterminated' => ['fields' => ['email' => 0]]]]]];
    foreach (['{broken', 42, $bad, $regex] as $input) {
        expectInvalid(function () use ($provider, $input) { $provider->normalizeRules($input); });
        same([], $provider->checkForMatches('forms@example.com', 4, $body, $input));
    }
});
check('Missing optional capture is ignored', function () use ($provider) {
    $r = ['forms@example.com' => ['matches' => [['/Hello(?: (world))?/' => ['fields' => ['first_name' => 0]]]]]];
    same([], $provider->checkForMatches('forms@example.com', 4, 'Hello', $r));
});
check('Stop notfound prevents later extraction', function () use ($provider) {
    $r = ['forms@example.com' => ['matches' => [[ '/required/' => ['stop' => '_notfound'], '/Email: (.+)/' => ['fields' => ['email' => 0]] ]]]];
    same([], $provider->checkForMatches('forms@example.com', 4, 'Email: a@example.com', $r));
});
check('Stop found prevents later overwrite', function () use ($provider) {
    $r = ['forms@example.com' => ['matches' => [[ '/(Jane)/' => ['fields' => ['first_name' => 0], 'stop' => '_found'], '/(Smith)/' => ['fields' => ['last_name' => 0]] ]]]];
    same(['first_name' => 'Jane'], $provider->checkForMatches('forms@example.com', 4, 'Jane Smith', $r));
});
check('Unchecked old checkbox remains unchecked', function () {
    $GLOBALS['oldInput'] = ['settings' => ['aarhuschangecustomer.active' => 'off']];
    try { same(false, strpos(renderSettings([]), 'checked="checked"') !== false); }
    finally { $GLOBALS['oldInput'] = []; }
});
check('Real Laravel validator handles literal dotted option name', function () use ($json) {
    $translator = new Illuminate\Translation\Translator(new Illuminate\Translation\ArrayLoader(), 'en');
    $factory = new Illuminate\Validation\Factory($translator);
    $params = Eventy::$filters['settings.section_params']([], 'aarhuschangecustomer');
    foreach ([$json => false, '{broken' => true, '' => false] as $value => $fails) {
        $validator = $factory->make(['settings' => ['aarhuschangecustomer.ruleset' => $value]], $params['validator_rules']);
        same($fails, $validator->fails());
    }
});
check('Explicit null matches is rejected', function () use ($provider) {
    expectInvalid(function () use ($provider) { $provider->normalizeRules(['forms@example.com' => ['matches' => null]]); });
});
check('Successful incoming hook with explicit persistence doubles', function () {
    $output = []; $status = 0;
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/hook.php').' 2>&1', $output, $status);
    if ($status !== 0) throw new RuntimeException(implode("\n", $output));
});
check('Stop always also stops when pattern does not match', function () use ($provider) {
    $r = ['forms@example.com' => ['matches' => [[ '/absent/' => ['stop' => '_always'], '/(Jane)/' => ['fields' => ['first_name' => 0]] ]]]];
    same([], $provider->checkForMatches('forms@example.com', 4, 'Jane', $r));
});
check('Previously extracted field is not overwritten', function () use ($provider) {
    $r = ['forms@example.com' => ['matches' => [[ '/(Jane)/' => ['fields' => ['first_name' => 0]], '/(Mary)/' => ['fields' => ['first_name' => 0]] ]]]];
    same(['first_name' => 'Jane'], $provider->checkForMatches('forms@example.com', 4, 'Jane Mary', $r));
});
finishTests();


