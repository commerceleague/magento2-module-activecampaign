# Changelog

All notable changes to this module are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.2] - 2026-08-11

### Fixed
- **The contact-unsubscribe webhook returned HTTP 500 for any address that is not
  already a Magento newsletter subscriber.** `Controller\Webhook\Contact\Unsubscribe`
  guarded with `$subscriber->getId() === 0`, but `loadByEmail()` on an unknown
  address leaves an empty model whose `getId()` returns **null**, and `null === 0`
  is false. The guard never fired, so execution fell through to `unsubscribe()` on
  an empty subscriber; `sendUnsubscriptionEmail()` then called `addTo(null, null)`
  and Magento's mail layer raised a `TypeError`. Now guards with
  `!$subscriber->getId()`.

  This mattered more than a noisy log line: ActiveCampaign holds guests and
  imported contacts that were never Magento newsletter subscribers, so most
  unsubscribe callbacks failed — and ActiveCampaign disables a webhook that keeps
  failing, which silently ends unsubscribe processing altogether. Found by calling
  the live endpoint; the failure is invisible to a unit suite that never exercises
  an unknown address.

  Adds the first unit test for this controller, covering the unknown address, the
  known address, and a payload carrying no contact email.

## [2.0.1] - 2026-06-22

### Fixed
- **Tombstone relink/self-heal no longer silently no-ops against live ActiveCampaign.**
  `TombstoneRelinker` read `$record['email']` from the single-resource
  `getCustomerApi()->get()`, but the API client returns the wrapped shape
  `{"ecomCustomer": {…}}`, so the email was always null and every record was
  mis-classified as "not a tombstone" — the `activecampaign:relink:tombstones`
  command and the config-gated export self-heal skipped everything and wrote
  nothing. Now reads `$record['ecomCustomer']['email']`. (The unit test had
  mocked the unwrapped shape, mirroring the bug; its fixtures now use the real
  wrapped response so it reproduces and guards the fix.) 2.0.0's core data-loss
  fixes were unaffected.

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
- **Guest export no longer crashes on a null name (poison message).** A guest
  whose order has a null `customer_firstname` (the name lives on the order
  address) threw a `TypeError` before any catch, nacking and re-crashing the
  message. The guest command now reads the name from the billing address, the
  guest repository coalesces null names to `''`, and every export consumer wraps
  `consume()` in a `\Throwable` backstop that records a structured failure instead
  of poisoning the queue.
- **Duplicate-recovery now verifies the resolved record matches.** The guest
  (email + connectionid + email check), order (`externalid`) and abandoned-cart
  (`externalcheckoutid`) duplicate paths reject a non-matching `listPerPage` hit
  (mirroring the customer guard) so an entity can't be linked to the wrong AC id.
- **Order deferral-cap failures are now visible.** When an order's customer never
  syncs, the deferral cap records a `customer_unresolved` failure + saves (was a
  silent permanent `NULL`, invisible to `export:status`); the deferral bound was
  raised from 1 to 3 attempts.
- **`relink:tombstones` single-target lookups fixed.** `--guest-id` no longer
  crashes with an ambiguous `entity_id` (the guest collection qualifies
  `main_table.*`); `--email` for a guest is matched case-insensitively (was
  silently dropped); and `--email` for a registered customer resolves the email
  to a `magento_customer_id` once and constrains the query at the DB level instead
  of scanning the whole registered tombstone set (which appeared to hang).

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
- **`activecampaign:relink:tombstones`** operator command + config-gated export
  self-heal for tombstoned ecomCustomers. When an AC contact is deleted, AC
  anonymizes its ecomCustomer (`email` → `deleted+…`, `subscriberid` null) but
  keeps the record and its orders, so the local mapping calls `update(<dead id>)`
  forever and the order history is orphaned. The command does an id-preserving
  relink (restores the real email via PUT — history kept, contact re-linked),
  single or batch, `--dry-run` by default / `--commit`; it is idempotent (skips
  non-tombstones, never re-fixing corrected records), skips when no live contact
  exists, and skips email conflicts that need a manual merge. The new
  `activecampaign/export/tombstone_selfheal_enabled` flag (default off) makes the
  customer export path self-heal the same way before each update.
- **Config-gated weekly tombstone-relink cron.** A new
  `activecampaign/export/relink_cron_enabled` flag (default off) schedules a weekly
  `activecampaign:relink:tombstones --commit` (cron `activecampaign_relink_tombstones`,
  `0 3 * * 0`). This is the only path that keeps **guest** tombstones reconciled —
  the guest export self-heal cannot, because a guest tombstone holds the dead AC id
  so the guest consumer (which only runs for guests with no AC id) never touches it.
  The cron and the command share one `Model\Tombstone\TombstoneReconciler` (single
  source of truth for the resolve/relink loop); each per-candidate relink is isolated
  in a `\Throwable` guard so one transient failure (e.g. a 503 from the relinker's
  GET) is counted and logged and the run continues, and a per-run summary
  (relinked / skipped / conflict / no-live-contact / not-found / unresolved / error)
  is logged. **Operational ordering:** run the bulk relink once manually
  (`--dry-run`, operator-reviewed, then `--commit`), THEN enable both
  `relink_cron_enabled` (keeps guests reconciled) and `tombstone_selfheal_enabled`
  (keeps the customer export path from re-accumulating).
- **Sync-health columns on the admin grids.** The Contacts, Customers and Orders
  listings now show `export_status` (with a Pending/Synced/Failed select filter —
  i.e. a "failed only" view), `export_attempts`, `last_error_code` and
  `last_attempted_at`, surfacing the failure-tracking data in the admin instead of
  only via the `activecampaign:export:status` CLI. A one-time data patch
  (`setup:upgrade`) backfills `export_status` = synced for pre-existing rows that
  already have an `activecampaign_id` (idempotent — only rows still at the pending
  default are touched), so the grid is accurate for legacy data too.

### Changed
- **Manual CLI exports are bounded to the cron scope by default.** The
  `--all` / `--omitted` paths of the order, guest-customer, customer, contact and
  abandoned-cart export commands now apply the same date/status/customer-group
  window filter the omitted crons apply (instead of running unbounded), so a
  manual run can no longer silently re-export pre-cutoff historical data. A new
  `--ignore-date-filter` flag opts out for deliberate full historical re-exports;
  when set, the command prints a visible warning with the record count. The
  targeted `--email` / `--order-id` / `--quote-id` paths are unchanged. The
  `retry_all_omitted` crons now also log a warning with the affected record count
  whenever they run unbounded.
- Static analysis raised to **PHPStan level 6** (precise array generics added
  throughout; inline ignores reduced); the test suite was migrated to run green
  standalone on PHPUnit 9.6 with a Magento test-framework bootstrap, and the
  phpcs gate made reliable (vendor excluded; warnings advisory, errors blocking).

### Removed
- Dead `Abandoned` resource model + `AbandonedInterface` and the
  `SchemaInterface::ABANDONED_CART_TABLE` constant (the table never existed).
- The non-functional **Abandoned Carts admin page** (controller, menu entry and
  ACL resource that rendered an empty grid — it had no UI component). Abandoned
  carts are already the Orders-grid rows without a `magento_order_id`.

### Notes
- The full PHP 8.4 / PHPUnit 10.5 / Magento 2.4.8 dependency bump (and the Rector
  8.4 pass) is prepared but must be run and verified in a PHP 8.4 CI toolchain;
  the locked, verified stack for this release is magento/framework 103 +
  PHPUnit 9.6 on PHP 8.3. See `RELEASE-READINESS.md`.
- Schema changes are declarative; run `bin/magento setup:upgrade` in the target
  Magento project to apply the new columns.

[2.0.2]: https://github.com/commerceleague/magento2-module-activecampaign/releases/tag/2.0.2
[2.0.1]: https://github.com/commerceleague/magento2-module-activecampaign/releases/tag/2.0.1
[2.0.0]: https://github.com/commerceleague/magento2-module-activecampaign/releases/tag/2.0.0
