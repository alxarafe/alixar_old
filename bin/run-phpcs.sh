#!/bin/bash

# Check PHP Code Sniffer rules based on phpcs.xml

vendor/bin/phpcs -d memory_limit=2G --standard=phpcs.xml --ignore=*/vendor/* -s