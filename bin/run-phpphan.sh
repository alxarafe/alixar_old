#!/bin/bash

vendor/phan/phan/phan --allow-polyfill-parser --analyze-twice --minimum-target-php-version --output-mode=checkstyle -o _phan.xml