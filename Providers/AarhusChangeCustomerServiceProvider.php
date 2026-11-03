<?php

/**
 * Short description for file
 *
 * Long description for file (if any)...
 *
 * PHP version 7
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Original Author <author@example.com>
 * @author     Another Author <another@example.com>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://pear.php.net/package/PackageName
 * @see        NetOther, Net_Sample::Net_Sample()
 * @since      File available since Release 1.2.0
 * @deprecated File deprecated in Release 2.0.0
 */

namespace Modules\AarhusChangeCustomer\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

use App\Customer;



//use Modules\AarhusChangeCustomer\Entities\;

define('AARHUSCHANGECUSTOMER_MODULE', 'aarhuschangecustomer');

/**
 * Please use them in the order they appear here.  phpDocumentor has
 * several other tags available, feel free to use them.
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Original Author <author@example.com>
 * @author     Another Author <another@example.com>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/PackageName
 * @see        NetOther, Net_Sample::Net_Sample()
 * @since      Class available since Release 1.2.0
 * @deprecated Class deprecated in Release 2.0.0
 */
class AarhusChangeCustomerServiceProvider extends ServiceProvider
{
    /**
     Indicates if loading of the provider is deferred.
    
     @var bool
     */
    protected $defer = false;

    /**
     Boot the application events.
    
     @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->hooks();

    }


    /**
     Add all of the hooks.
    
     @return void
     */
    public function hooks()
    {

        \Eventy::addAction(
            'conversation.created_by_customer',
            function ($conversation, $thread, $customer) {

                $settings = \Option::getOptions(
                    [
                    'aarhuschangecustomer.active',
                    'aarhuschangecustomer.ruleset',
                    ]
                );

                if (($settings['aarhuschangecustomer.active'] ?? 'off') !== 'on') {
                    return $conversation;
                }
                try {
                    $rules = $this->normalizeRules($settings['aarhuschangecustomer.ruleset'] ?? '');
                } catch (\InvalidArgumentException $exception) {
                    \Helper::Log('AarhusChangeCustomer', 'Invalid ruleset; customer unchanged.');
                    return $conversation;
                }
                $body = html_entity_decode((string) ($thread->body ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $matches = $this->checkForMatches($conversation->customer_email, $conversation->mailbox_id, $body, $rules);
                if (empty($matches['email']) || !filter_var($matches['email'], FILTER_VALIDATE_EMAIL)) {
                    return $conversation;
                }
                $email = \App\Email::sanitizeEmail($matches['email']);
                if (!$email) {
                    return $conversation;
                }
                $matches['email'] = $email;
                $replacement = Customer::create($email, $matches);
                if ($replacement) {
                    $conversation->changeCustomer($email, $replacement);
                }
                return $conversation;
            },
            20,
            3
        );


        \Eventy::addFilter(
            'settings.sections', function ($sections) {
                $sections['aarhuschangecustomer'] = ['title' => __('ACCustomer'), 'icon' => 'user', 'order' => 700];

                return $sections;
            }, 30
        );


        // Section settings
        \Eventy::addFilter(
            'settings.section_settings', function ($settings, $section) {

                if ($section != 'aarhuschangecustomer') {
                    return $settings;
                }

                $settings = \Option::getOptions(
                    [
                    'aarhuschangecustomer.active',
                    'aarhuschangecustomer.ruleset',
                    ]
                );

                return $settings;
            }, 20, 2
        );

        // Section parameters.
        \Eventy::addFilter(
            'settings.section_params', function ($params, $section) {
                if ($section != 'aarhuschangecustomer') {
                    return $params;
                }

                $params = [
                'template_vars' => [],
                'validator_rules' => [
                    'settings.aarhuschangecustomer\\.ruleset' => [
                        'nullable',
                        function ($attribute, $value, $fail) {
                            try {
                                $this->normalizeRules($value);
                            } catch (\InvalidArgumentException $exception) {
                                $fail(__('Invalid ruleset JSON: ') . $exception->getMessage());
                            }
                        },
                    ],
                ],
                ];

                return $params;
            }, 20, 2
        );

        // Settings view name
        \Eventy::addFilter(
            'settings.view', function ($view, $section) {
                if ($section != 'aarhuschangecustomer') {
                    return $view;
                } else {
                    return 'aarhuschangecustomer::index';
                }
            }, 20, 2
        );



    }

    /**
     Check if message matches any rules.
    
     @param string $from    string of email address
     @param number $mailbox number of mailbox receiving the message
     @param string $body    string of message body
     @param array  $rules   rules to follow
    
     @return array
     */
    public function normalizeRules($value)
    {
        if ($value === '' || $value === null) {
            return [];
        }
        if (is_string($value)) {
            $value = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON syntax.');
            }
        }
        if (!is_array($value)) {
            throw new \InvalidArgumentException('Expected a sender-to-rules object.');
        }
        foreach ($value as $sender => $rules) {
            if (!is_string($sender) || !filter_var($sender, FILTER_VALIDATE_EMAIL) || !is_array($rules)) {
                throw new \InvalidArgumentException('Each sender must be an email address with a rule object.');
            }
            if (array_key_exists('mailboxes', $rules)) {
                if (!is_array($rules['mailboxes'])) {
                    throw new \InvalidArgumentException('mailboxes must be an array of positive mailbox IDs.');
                }
                foreach ($rules['mailboxes'] as $id) {
                    if (!(is_int($id) || (is_string($id) && ctype_digit($id))) || (int) $id < 1) {
                        throw new \InvalidArgumentException('mailboxes must contain positive mailbox IDs.');
                    }
                }
            }
            if (array_key_exists('matches', $rules) && !is_array($rules['matches'])) {
                throw new \InvalidArgumentException('matches must be an array.');
            }
            foreach (($rules['matches'] ?? []) as $rule) {
                if (!is_array($rule)) {
                    throw new \InvalidArgumentException('Each match group must be an object.');
                }
                foreach ($rule as $pattern => $config) {
                    if (!is_string($pattern) || !is_array($config) || $this->matchPattern($pattern, '', $unused) === false) {
                        throw new \InvalidArgumentException('Each match requires a valid regular expression and configuration object.');
                    }
                    if (array_key_exists('fields', $config) && !is_array($config['fields'])) {
                        throw new \InvalidArgumentException('fields must be an object of capture indexes.');
                    }
                    foreach (($config['fields'] ?? []) as $field => $index) {
                        if (!is_string($field) || $field === '' || !is_int($index) || $index < 0 || $index === PHP_INT_MAX) {
                            throw new \InvalidArgumentException('Field capture indexes must be non-negative integers.');
                        }
                    }
                    if (array_key_exists('stop', $config) && !is_string($config['stop'])) {
                        throw new \InvalidArgumentException('stop must be a string.');
                    }
                }
            }
        }
        return $value;
    }

    private function matchPattern($pattern, $body, &$matches)
    {
        // Invalid patterns and PCRE limits must never abort incoming mail processing.
        set_error_handler(function () { return true; }, E_WARNING);
        try {
            return preg_match($pattern, $body, $matches);
        } finally {
            restore_error_handler();
        }
    }

    public function checkForMatches($from, $mailbox, $body, $rules)
    {
        try {
            $rules = $this->normalizeRules($rules);
        } catch (\InvalidArgumentException $exception) {
            return [];
        }
        if (!is_string($from) || !is_string($body) || !isset($rules[$from])) {
            return [];
        }
        if (!in_array($mailbox, $rules[$from]['mailboxes'] ?? [$mailbox])) {
            return [];
        }
        $customer = [];
        foreach (($rules[$from]['matches'] ?? []) as $rule) {
            foreach ($rule as $pattern => $config) {
                $matches = [];
                $result = $this->matchPattern($pattern, $body, $matches);
                if ($result === false) {
                    return [];
                }
                $stop = $config['stop'] ?? '';
                if (!$result) {
                    if ($stop === '_notfound' || $stop === '_always') {
                        return $customer;
                    }
                    continue;
                }
                foreach (($config['fields'] ?? []) as $field => $index) {
                    if (isset($customer[$field])) {
                        continue;
                    }
                    $value = trim($matches[$index + 1] ?? '');
                    if ($value === '') {
                        continue;
                    }
                    if ($field !== 'name') {
                        $customer[$field] = $value;
                        continue;
                    }
                    $parts = preg_split('/\\s+/u', trim($value, " \n\r\t\v\x00\""), 2);
                    if (!empty($parts[0]) && !isset($customer['first_name'])) {
                        $customer['first_name'] = $parts[0];
                    }
                    if (!empty($parts[1]) && !isset($customer['last_name'])) {
                        $customer['last_name'] = $parts[1];
                    }
                }
                if ($stop === '_always' || $stop === '_found') {
                    return $customer;
                }
                if ($stop !== '' && $stop !== '_notfound') {
                    $complete = true;
                    foreach (explode(',', $stop) as $field) {
                        $field = trim($field);
                        if ($field !== '' && !isset($customer[$field])) {
                            $complete = false;
                            break;
                        }
                    }
                    if ($complete) {
                        return $customer;
                    }
                }
            }
        }
        return $customer;
    }

    /**
     Register the service provider.
    
     @return void
     */
    public function register()
    {
        //
    }

    /**
     Register config.
    
     @return void
     */
    protected function registerConfig()
    {
        $this->publishes(
            [
            __DIR__ . '/../Config/config.php' => config_path('aarhuschangecustomer.php'),
            ],
            'config'
        );
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'aarhuschangecustomer'
        );
    }

    /**
     Register views.
    
     @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/aarhuschangecustomer');

        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes(
            [
            $sourcePath => $viewPath
            ],
            'views'
        );

        $this->loadViewsFrom(
            array_merge(
                array_map(
                    function ($path) {
                            return $path . '/modules/aarhuschangecustomer';
                    }, \Config::get('view.paths')
                ),
                [$sourcePath]
            ),
            'aarhuschangecustomer'
        );
    }

    /**
     Register translations.
    
     @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/aarhuschangecustomer');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'aarhuschangecustomer');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'aarhuschangecustomer');
        }
    }

    /**
     Register an additional directory of factories.
    
     @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
    
     @return void
     */
    public function registerFactories()
    {
        if (!app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     Get the services provided by the provider.
    
     @return array
     */
    public function provides()
    {
        return [];
    }


}
