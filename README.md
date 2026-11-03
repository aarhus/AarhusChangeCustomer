# Automatically update customer based on body of emails

[![Checks](https://github.com/aarhus/AarhusChangeCustomer/actions/workflows/buildchecks.yml/badge.svg)](https://github.com/aarhus/AarhusChangeCustomer/actions/workflows/buildchecks.yml)

Fed up with changing the customer when you receive a form submission or other automated email? This module is for you!

Config is carried out using JSON - here is a simple example - one for a form submitted using Webflow, and another being an unmatched email response from a school MIS system

```

{
    "no-reply-forms@webflow.com": {
        "matches": [
            {
                "\/<p>You just got a form (submission)!\/": {
                    "stop": "_notfound"
                },
                "\/<strong>Submitted content<\\\/strong>[\n\r]+<br>First Name: ([^\r\n]+)\/": {
                    "fields": {
                        "first_name": 0
                    }
                },
                "\/<strong>Submitted content<\\\/strong>.*[\n\r]+<br>Last Name: ([^\r\n]+)\/s": {
                    "fields": {
                        "last_name": 0
                    }
                },
                "\/<strong>Submitted content<\\\/strong>.*[\n\r]+<br>Email Address: ([^\r\n]+)\/s": {
                    "fields": {
                        "email": 0
                    }
                },
                "\/<strong>Submitted content<\\\/strong>.*[\n\r]+<br>Phone Number: ([^\r\n]+)\/s": {
                    "fields": {
                        "phone": 0
                    }
                }
            }
        ]
    },
    "info@schooldomain.uk": {
        "matches": [
            {
                "\/Untracked email received:<br><br>From: ([^<]*) <([^>]+)>\/": {
                    "fields": {
                        "email": 1,
                        "name": 0
                    },
                    "stop": "_found"
                }
            }
        ],
	"mailboxes": [ 1,3 ]
    }
}

Still very much a work in progress - hoping to make the JSON config more user friendly. Have been running in production for some time.

Free to use, but if you end up using it in an organisation with 20 or more users, or provide it as part of another package or service, please consider support me via https://ko-fi.com/aarhus and/or helping with the development/maintenance.

Thanks
```

## PHP 8.5 / FreeScout 1.8.248 compatibility

The local compatibility branch targets PHP 8.5.12 and FreeScout 1.8.248.
Saved rules work both as JSON strings and as arrays decoded by FreeScout.
The settings form validates JSON, mailbox IDs, capture indexes and regular
expressions before saving. Existing malformed configuration leaves the customer
unchanged. Extracted email addresses must pass email validation.

Names use the configured zero-based capture index. The first word becomes the
first name; the remaining words become the last name. An explicit first_name
or last_name already extracted by an earlier rule is retained.

Stop conditions: `_found` stops on a match, `_notfound` stops on a nonmatch,
`_always` stops after the rule whether it matched or not. A comma-separated
list stops when all named fields have been extracted. A PCRE execution error
abandons the entire match result so partially extracted data is not applied.

Run the regression suite with the exact FreeScout source checkout:

```sh
git clone --depth 1 --branch 1.8.248 https://github.com/freescout-helpdesk/freescout.git .compat/freescout-1.8.248
php -d error_reporting=32767 -d display_errors=1 Tests/compatibility.php
```

The compatibility workflow checks out the pinned FreeScout commit and runs
with PHP 8.5.12. Tests use real FreeScout classes and Blade where possible;
see the test files for substituted application services and test boundaries.
A deployment should additionally verify actual incoming mail and persisted
customer changes in an isolated FreeScout installation.
