# Defence

[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/badges/StandWithUkraine.svg)](https://stand-with-ukraine.pp.ua)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/danbettles/defence/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/danbettles/defence/?branch=main) [![Code Coverage](https://scrutinizer-ci.com/g/danbettles/defence/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/danbettles/defence/?branch=main) [![Build Status](https://scrutinizer-ci.com/g/danbettles/defence/badges/build.png?b=main)](https://scrutinizer-ci.com/g/danbettles/defence/build-status/main)

A simple intrusion detection/prevention system framework written in PHP.

Defence is used principally to (1) prevent a suspicious-looking request getting deeper into an application's code, and potentially exploiting vulnerabilities, and (2) avoid wasting further system resources on unfriendly user activity.

**:bangbang: Defence does not eliminate the need to filter user-input in your application.**  While some of the included filters do indeed validate user input, they take a very high-level view.  Their aim is to detect _obviously_ suspect values given a very basic understanding of what they're looking at.  For example, the included ID-parameter filter knows only that certain parameters must contain only digits or a blank; the filter is useful because it can quickly and easily prevent SQL injection, but the value may still be invalid as far as your app is concerned.

We recommend you read [Architecture](doc/architecture.md) before [Getting Started](doc/getting-started.md).  Otherwise, [the documentation starts here](doc/README.md).
