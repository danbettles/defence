# Defence

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/danbettles/defence/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/danbettles/defence/?branch=main) [![Code Coverage](https://scrutinizer-ci.com/g/danbettles/defence/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/danbettles/defence/?branch=main) [![Build Status](https://scrutinizer-ci.com/g/danbettles/defence/badges/build.png?b=main)](https://scrutinizer-ci.com/g/danbettles/defence/build-status/main)

A simple intrusion detection/prevention system framework written in PHP.

Defence is used principally to:

1. :shield: Prevent a suspicious-looking request getting deeper into an application's code and potentially exploiting vulnerabilities.
1. :seedling: Avoid wasting further system resources on unfriendly user activity.

Recently, we have found Defence to be been extremely helpful in quickly mitigating the effects of *overzealous* bots, particularly those involved in information-gathering for AI.  Excessive and/or faulty requests can negatively affect the experience of other users of the system and waste resources&mdash;including electricity.

> [!WARNING]
> **Defence does not eliminate the need to filter user-input in your application.**  While some of the included filters do indeed validate user input, they take a very high-level view.  Their aim is to detect *obviously* suspect values given a very basic understanding of what they're looking at.  For example, the included ID-parameter filter knows only that certain parameters must contain only digits or a blank; the filter is useful because it can quickly and easily prevent SQL injection, but the value may still be invalid as far as your app is concerned.

We recommend you read [Architecture](doc/architecture.md) before [Getting Started](doc/getting-started.md).  Otherwise, [the documentation starts here](doc/README.md).
