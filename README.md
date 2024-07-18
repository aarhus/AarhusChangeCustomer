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
