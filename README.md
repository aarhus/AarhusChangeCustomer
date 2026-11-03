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

## Native FreeScout updates and releases

Release packages are published by this repository at
https://github.com/aarhus/AarhusChangeCustomer/releases.
Once a release containing this change has been published, download its
`AarhusChangeCustomer.zip` asset. It already contains the correctly named
`AarhusChangeCustomer/` folder; copy that folder into FreeScout's `Modules/`.
Existing installations without the new update URLs need this manual update
once. Thereafter FreeScout checks `version.txt` and downloads the ZIP from
the latest GitHub release through its native module updater.

To publish a release:

1. Set a new version in `module.json` and commit the changes.
2. Push the commit and a matching `X.Y.Z` tag (without a `v` prefix).
3. The Release workflow validates the tag and publishes `version.txt` and
   `AarhusChangeCustomer.zip` together. Check the workflow before updating installations.

The workflow can also run manually against an existing matching version tag.
The tagged commit must contain the new workflow and metadata. The workflow
replaces assets if that release already exists; it does not automatically
publish an existing draft or promote a prerelease to the latest stable release.
No release is created merely by pushing a branch. The version has not been
bumped as part of adding this release mechanism.
