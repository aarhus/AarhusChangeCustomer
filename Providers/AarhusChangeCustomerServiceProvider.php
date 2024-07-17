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
use App\Thread;


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
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
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

    
    public function hooks()
    {

        \Eventy::addAction(
            'conversation.created_by_customer',
            function ($conversation, $thread, $customer) {

            	$settings = \Option::getOptions([
                	'aarhuschangecustomer.active',
                	'aarhuschangecustomer.ruleset',
            	]);

		if ($settings['aarhuschangecustomer.active']!=="on") {
			\Helper::Log("Wibble", "system not active: ");
			return $conversation;
		}

		$rules = [];

		try {
			$rules = json_decode($settings['aarhuschangecustomer.ruleset'], 1);
		}
		catch (Exception $exception) {
			\Helper::Log("Wibble", "Exception when decoding ruleset");
			return $conversation;
		}

		
		$body = html_entity_decode($thread->body);

		$matches = $this->checkForMatches($conversation->customer_email, $conversation->mailbox_id, $body, $rules);
	
		if (!isset($matches["email"])) {
			return $conversation;
		}

		

                $c = Customer::Create($matches["email"], $matches);
                $c->save();
                $conversation->changeCustomer($matches["email"], $c);

		\Helper::Log("Wibble", json_encode($matches));
                return $conversation;
            },
            20,
            3
        );


        \Eventy::addFilter('settings.sections', function($sections) {
            $sections['aarhuschangecustomer'] = ['title' => __('ACCustomer'), 'icon' => 'user', 'order' => 700];

            return $sections;
        }, 30);


	        // Section settings
        \Eventy::addFilter('settings.section_settings', function($settings, $section) {

            if ($section != 'aarhuschangecustomer') {
                return $settings;
            }

            $settings = \Option::getOptions([
                'aarhuschangecustomer.active',
                'aarhuschangecustomer.ruleset',
            ]);

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function($params, $section) {
            if ($section != 'aarhuschangecustomer') {
                return $params;
            }

            $params = [
                'template_vars' => [],
                'validator_rules' => [],
            ];

            return $params;
        }, 20, 2);

        // Settings view name
        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section != 'aarhuschangecustomer') {
                return $view;
            } else {
                return 'aarhuschangecustomer::index';
            }
        }, 20, 2);



}

	public function checkForMatches($from, $mailbox, $body, $rules) {
        if (!isset($rules[$from])) {
                return [];
        }



        if (!in_array($mailbox, $rules[$from]["mailboxes"] ?? [$mailbox])) {
                return [];
        }



        $customer = [];
        foreach (($rules[$from]["matches"] ?? [])  as $rule) {

                $found = false;

                foreach ($rule as $find=>$config) {


                        $matches = [];

                        $r = preg_match($find, $body, $matches);

                        if (!$r) {
                                if (($config["stop"] ?? "_") == "_notfound") {
                                        return $customer;
                                }
                                continue;
                        }


                        foreach (($config["fields"] ?? []) as $key=>$index) {

                                if (isset($customer[$key])) { continue; }


                                $value = trim($matches[$index+1] ?? "");

                                if (strlen($value)==0) { continue; }

                                $found = true;


                                if ($key!="name") {
                                        $customer[$key]=$value;
                                        continue;
                                }


                                $n = array_filter(explode(" ", trim($matches[1], " \n\r\t\v\x00\"")), function ($x) {
                                        return strlen($x)>0;
                                });

                                if (isset($n[0])) {
                                        $customer["first_name"]=$n[0];
                                }

                                if (isset($n[1])) {
                                        $customer["last_name"]=$n[1];
                                }
                        }



                        if (!isset($config["stop"])) {
                                continue;
                        }

                        if ($config["stop"] === "_always") {
                                return $customer;
                        }

                        if ($config["stop"] === "_found" ) {
                                return $customer;
                        }

                        $f = explode(",", $config["stop"]);
                        $ok = true;

                        foreach ($f as $r) {
                                $r=trim($r);
                                if (strlen($r)==0 || isset($customer[$r])) {
                                        continue;
                                }
                                $ok = false;
                                break;
                        }

                        if ($ok) {
                                return $customer;
                        }

                }

        }
        return $customer;




}





    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Register config.
     *
     * @return void
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
     * Register views.
     *
     * @return void
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
     * Register translations.
     *
     * @return void
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
     * Register an additional directory of factories.
     *
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     *
     * @return void
     */
    public function registerFactories()
    {
        if (!app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param string $h string of headers
     *
     * @return Response
     */
    function getMyHeaders($h)
    {
        $h_array = explode("\n", $h);

        foreach ($h_array as $h) {

            // Check if row start with a char
            if (preg_match("/^[A-Z]/i", $h)) {

                $tmp = explode(":", $h, 2);
                $header_name = $tmp[0];
                $header_value = array_reduce(
                    imap_mime_header_decode(trim($tmp[1])),
                    function ($carry, $key) {
                        return $carry . $key->text;
                    },
                    ""
                );

                $headers[$header_name] = $header_value;

            } else {
                // Append row to previous field

                if (!isset($header_name)) {
                    \Helper::Log("wibble", json_encode(["failed_to_find headername", $h, $h_array]));
                } else {

                    $headers[$header_name] = array_reduce(
                        imap_mime_header_decode(trim($h)),
                        function ($carry, $key) {
                            return $carry . $key->text;
                        },
                        $headers[$header_name] ?? []
                    );
                }
            }

        }
        return $headers ?? [];
    }
}
