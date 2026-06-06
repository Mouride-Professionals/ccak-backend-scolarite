.PHONY: check fix

check:
	@echo "==> Pint (dry-run)"
	./vendor/bin/pint --test
	@echo "==> PHPStan"
	vendor/bin/phpstan analyse --memory-limit=512M
	@echo "==> Tests"
	php artisan config:clear --ansi
	php artisan test

fix:
	@echo "==> Pint (auto-fix)"
	./vendor/bin/pint
