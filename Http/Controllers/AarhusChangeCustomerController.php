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

namespace Modules\AarhusChangeCustomer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Conversation;
use App\Thread;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

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

class AarhusChangeCustomerController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param int $mailbox_id      an integer of the mailbox_id
     * @param int $conversation_id an integer of how many problems happened.
     *
     * @return Response (or redirect)
     **/
    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('aarhuschangecustomer::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request not used
     *
     * @return Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Show the specified resource.
     *
     * @return Response
     */
    public function show()
    {
        return view('aarhuschangecustomer::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit()
    {
        return view('aarhuschangecustomer::edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request not used
     *
     * @return Response
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy()
    {
    }


    /**
     * Wibble oww
     *
     * @param string $h string of headers
     *
     * @return array of strings...
     * @throws exceptionclass [description]
     *
     * @since      Method available since Release 1.2.0
     * @deprecated Method deprecated in Release 2.0.0
     */
    function getMyHeaders($h)
    {
        $h_array = explode("\n", $h);

        foreach ($h_array as $h) {

            print "Processing: $h<br />";
            $headers = [];
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

                if (isset($header_name)) {


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
