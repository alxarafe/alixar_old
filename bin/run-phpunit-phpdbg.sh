#!/bin/bash

# Run PHPUnit code coverage

phpdbg -qrr ./vendor/bin/phpunit

# If seems broken, use this to find where is the problem
#phpdbg -r ./vendor/bin/phpunit
