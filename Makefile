### ——————————————————————————————————————————————————————————————————
### —— Toolkit Makefile
### ——————————————————————————————————————————————————————————————————

APP_CONTAINER ?= "toolkit"

-include make/toolkit.mk
include vendor/badpixxel/php-sdk/make/sdk.mk