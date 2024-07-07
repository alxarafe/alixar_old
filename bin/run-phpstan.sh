#!/bin/bash

vendor/phpstan/phpstan/phpstan -vvv analyse --error-format=checkstyle --memory-limit 4G -a build/phpstan/bootstrap_action.php -c phpstan.neon