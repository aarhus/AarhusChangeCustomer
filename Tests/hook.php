<?php
// Isolated persistence doubles: verifies the hook contract, not database writes.
namespace App {
    class Customer {
        public static $created = [];
        public static $returnNull = false;
        public static function create($email, $data = []) {
            self::$created[] = [$email, $data];
            return self::$returnNull ? null : new self();
        }
    }
}
namespace {
    require __DIR__.'/bootstrap.php';
    $rules = ['forms@example.com' => ['matches' => [[ '/Email: ([^<]+)/' => ['fields' => ['email' => 0]], '/Name: ([^<]+)/' => ['fields' => ['name' => 0]] ]]]];
    foreach ([$rules, json_encode($rules)] as $input) {
        check('Hook changes customer with '.gettype($input).' rules', function () use ($hook, $input) {
            Option::$cache = ['aarhuschangecustomer.active' => 'on', 'aarhuschangecustomer.ruleset' => $input];
            $conversation = new class {
                public $customer_email = 'forms@example.com'; public $mailbox_id = 4; public $changes = [];
                public function changeCustomer($email, $customer) { $this->changes[] = [$email, $customer]; }
            };
            $thread = (object) ['body' => 'Email: person@example.com<br>Name: Jane Mary Smith<br>'];
            same($conversation, $hook($conversation, $thread, null));
            same(1, count($conversation->changes)); same('person@example.com', $conversation->changes[0][0]);
            $created = end(App\Customer::$created); same('Jane', $created[1]['first_name']); same('Mary Smith', $created[1]['last_name']);
        });
    }
    check('Null customer return safely skips reassignment', function () use ($hook, $rules) {
        App\Customer::$returnNull = true;
        Option::$cache = ['aarhuschangecustomer.active' => 'on', 'aarhuschangecustomer.ruleset' => $rules];
        $c = (object) ['customer_email' => 'forms@example.com', 'mailbox_id' => 4];
        same($c, $hook($c, (object) ['body' => 'Email: person@example.com<br>'], null));
    });
    finishTests();
}
