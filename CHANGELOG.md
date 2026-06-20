# Changelog

All notable changes to this module are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-06-20

First stable release of the 2.x line: a hardening release that stops the
ActiveCampaign export pipeline losing data (rows stuck with
`activecampaign_id = NULL`), makes failures diagnosable and bounded, and adds
recovery tooling. All new behaviour is config-gated and defaults to the previous
behaviour; schema changes are additive and backward-compatible.

### Fixed
- **Duplicate-recovery id is now persisted.** On a 422 "duplicate" from
  ActiveCampaign, the order, guest-customer, customer and abandoned-cart
  consumers now resolve the existing AC id and persist it
  (`setActiveCampaignId` + save) instead of discarding it — the core cause of
  permanently `NULL` rows. The four duplicate paths are symmetric.
- **Abandoned-cart duplicate lookup** now filters by `externalcheckoutid`
  (the key the abandoned-cart builder actually sends) instead of `externalid`,
  so abandoned-cart duplicates are actually recovered.
- **Order → customer dependency.** An order whose customer/guest has no AC id no
  longer sends `customerid: null` (which AC rejected with `field_missing`); the
  customer/guest export is published first and the order is re-queued, bounded by
  a deferral counter so it cannot loop.
- **Response-shape guards.** Consumers no longer fatally dereference
  `$apiResponse[...]['id']` / `getItems()[0]` on an empty or malformed 2xx body;
  a missing id is treated as a failed attempt rather than stranding a bare row.
  Non-positive ids (`0`/`"0"`) are rejected instead of being saved.
- **Null-safe builders.** Deleted products (null `getProduct()`) and guest carts
  (null `customer_id`) no longer fatal the order/abandoned-cart builders; builder
  failures are caught and logged without stranding the row.
- **Robust 422 handler.** The handler no longer dereferences an empty errors
  array; it returns a typed outcome (duplicate / validation / unknown) instead of
  an implicit `null` that callers silently dropped.
- Renamed the misspelled `Helper\Contants` to `Helper\Constants`; removed a
  redundant shadowed logger property; standardised consumer return types.

### Added
- **Diagnosable failure logging.** Every export failure now logs a single
  structured line with entity type, local id, magento id, HTTP status and the AC
  error code/message (replacing the opaque `Unprocessable Entity [] []`).
- **Per-row failure tracking** (additive columns on all four mapping tables):
  `export_attempts`, `last_error_code`, `last_error_message`,
  `last_attempted_at`, `export_status` (0=pending, 1=synced, 2=failed).
- **Config-gated resilience** (all default off / today's behaviour):
  `activecampaign/export/max_attempts` (0 = unlimited), `dead_letter_enabled`,
  `backoff_enabled` + `backoff_threshold`, and `retry_all_omitted`. With a retry
  ceiling set, exhausted rows are dead-lettered and excluded from the omitted
  crons; 5xx/transient failures never dead-letter; repeated 503s back off.
- **`activecampaign:export:backfill`** console command: re-processes every
  `activecampaign_id IS NULL` row directly, bypassing the date/status/group
  window filters, with `--type`, `--limit`, `--dry-run`, `--include-failed` and
  `--flag-unrecoverable` (dead-letters known-unrecoverable `email_invalid` /
  `field_missing` rows). Emits a re-queued / flagged / per-error-code summary.
- **`activecampaign:export:status`** console command: per-table NULL,
  `export_status` and `last_error_code` breakdowns for diagnosis.
- **Abandoned-cart deletion lifecycle.** When Magento's quote-cleanup cron
  deletes an expired, never-converted cart, a new observer + topic + consumer
  delete the stale abandoned cart from ActiveCampaign (404 treated as success);
  a converted order only has its stale local row removed.
- Supplementary nullable `magento_order_id` column on `activecampaign_order`
  (with `getByMagentoOrderId`), recorded alongside the AC id on a converted
  order. The intentional `magento_quote_id` cart→order lifecycle key is preserved.
- `renovate.json` for ongoing dependency maintenance; `rector.php` targeted at
  PHP 8.4.

### Changed
- Static analysis raised to **PHPStan level 6** (precise array generics added
  throughout; inline ignores reduced); the test suite was migrated to run green
  standalone on PHPUnit 9.6 with a Magento test-framework bootstrap, and the
  phpcs gate made reliable (vendor excluded; warnings advisory, errors blocking).

### Removed
- Dead `Abandoned` resource model + `AbandonedInterface` and the
  `SchemaInterface::ABANDONED_CART_TABLE` constant (the table never existed).

### Notes
- The full PHP 8.4 / PHPUnit 13 / Magento 2.4.8 dependency bump (and the Rector
  8.4 pass) is prepared but must be run and verified in a PHP 8.4 CI toolchain;
  the locked, verified stack for this release is magento/framework 103 +
  PHPUnit 9.6 on PHP 8.3. See `RELEASE-READINESS.md`.
- Schema changes are declarative; run `bin/magento setup:upgrade` in the target
  Magento project to apply the new columns.

[2.0.0]: https://github.com/commerceleague/magento2-module-activecampaign/releases/tag/2.0.0
