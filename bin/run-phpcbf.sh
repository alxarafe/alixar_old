#!/bin/bash

# Check PHP Code Beautifier and Fixer rules based on phpcs.xml

vendor/bin/phpcbf -d memory_limit=2G --standard=phpcs.xml --ignore=*/vendor/* -s