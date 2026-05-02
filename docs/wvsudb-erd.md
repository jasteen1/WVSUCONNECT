# WVSU Connect database ERD

**Broader context (architecture, flows, setup):** see [`WVSU-Connect-Documentation.md`](WVSU-Connect-Documentation.md).

This document explains the **Entity Relationship Diagram** for the WVSU Connect MariaDB schema. The live diagram source is the project SQL dump and migrations; the visual diagram is maintained as Mermaid for easy import into **diagrams.net (draw.io)** and other tools.

## Files

| File | Purpose |
|------|---------|
| [`diagrams/wvsudb-erd.mmd`](../diagrams/wvsudb-erd.mmd) | Mermaid `erDiagram`: entities first, then relationships (helps connectors render). **Paste the whole file** into draw.io → **Arrange → Insert → Advanced → Mermaid** (file starts at `erDiagram`; no leading `%` comment block — draw.io’s Mermaid bundle does not accept `%%…` lines before `erDiagram`). The **`categories`** self-link (`parent_type` → parent row) is **not drawn as an edge**, because diagrams.net lays out same-table loops as huge broken curves; draw that link by hand there if you need it—the column is still shown on the **`categories`** box. |
| `wvsudb.sql` | Canonical dump of table definitions, indexes, and foreign keys. |

## How to read the ERD

- **Boxes (entities)** are **database tables**. Names match SQL (`snake_case`).
- **Lines** are **relationships**. Cardinality uses Mermaid notation (for example `||--o{` = one-to-many from left to right).
- **Labels on lines** are short names for the relationship (not always equal to the SQL column name). Where several foreign keys link the same two tables, the diagram may use **one line with a combined label** so tools like draw.io still draw a single clear connector; see the table reference below for exact columns.
- **`PK`** / **`FK`** in attribute lists mark primary and foreign keys at a glance.

## Subsystems (domains)

The schema groups naturally into these areas:

### 1. Identity and access

- **`roles`** — Application roles (admin, moderator, student) and JSON permission lists.
- **`users`** — Accounts, profile fields, social links, verification flags.
- **`user_sessions`** — Login sessions (token, IP, activity).

**Core link:** `users.role_id` → `roles.role_id`.

### 2. Catalog and listings

- **`categories`** — Hierarchical taxonomy; optional parent via `parent_type` → `categories.category_id`.
- **`listings`** — Shared header for anything offered on the marketplace (`listing_type` = product or service).
- **`products`** — Product-specific columns (price, stock); **one row per product listing** (`listing_id` unique).
- **`services`** — Service-specific columns (rate, rate type); **one row per service listing**.
- **`service_portfolio_items`** — Gallery / portfolio media for a service listing.
- **`service_pricing_items`** — Optional line-item pricing for a service listing.
- **`item_status`** — History of listing status changes (who changed it, when).

**Core links:** `listings` → `users` (owner), `listings` → `categories`. Detail tables hang off `listings`.

### 3. Commerce (orders)

- **`transactions`** — One row per purchase/booking attempt (`buyer_id`, `listing_id`, totals, workflow status).
- **`product_orders`** — Delivery fields for **product** transactions; **one-to-one** with `transactions` (unique `transaction_id`).
- **`service_bookings`** — Scheduling / agreed price for **service** transactions; **one-to-one** with `transactions` (unique `transaction_id`).

**Note:** A listing’s seller is **not** duplicated on `transactions`; the seller is derived via `listings.owner_id`.

### 4. Reviews (two mechanisms)

- **`reviews`** — Reviews tied to a **completed transaction** (`transaction_id` unique → at most one review per transaction). Legacy / transaction-centric flow.
- **`user_reviews`** — Peer-style reviews (`reviewer_id`, `reviewee_id`, optional `listing_id`) for profiles and reputation outside a strict order row.

### 5. Messaging

- **`conversations`** — One row per pair of participants (`participant_a`, `participant_b`); enforced uniqueness on the pair.
- **`messages`** — Chat rows per conversation and sender.
- **`conversation_meta`** — Optional flags (e.g. closed) keyed by `conversation_id` (one-to-one with `conversations`).
- **`conversation_listings`** — Associates conversations with listings (which items the thread is about). **Indexes exist in SQL; there are no foreign key constraints in the dump** — treat as a logical link maintained by the app.

### 6. Moderation, audit, and reports

- **`user_reports`** — User-generated reports (reporter, reported user, optional listing, optional conversation context, resolution).
- **`admin_actions`** — Record of moderator/admin actions.
- **`audit_logs`** — Event stream (optional `user_id` when the app inserts a row; **`NULL`** when the row is written by a **database trigger**—see [`WVSU-Connect-Documentation.md`](WVSU-Connect-Documentation.md) §12).

**Logical link:** `user_reports.conversation_id` references a conversation in the app but **may not have a declared FK** in `wvsudb.sql`; the ERD still shows an edge for clarity.

## Tables omitted from the diagram

These appear in some dumps but are **not** part of the product domain:

- `replication_live_test`, `replication_live_test_2` — replication testing.
- `uploaded_files_test` — ad hoc upload testing.

## Exact foreign keys (when the diagram uses one combined line)

| Diagram label | SQL columns / meaning |
|---------------|------------------------|
| `participant_a_and_b` | `conversations.participant_a`, `conversations.participant_b` → `users.user_id` |
| `reviewer_and_reviewee` | `user_reviews.reviewer_id`, `user_reviews.reviewee_id` → `users.user_id` |
| `reporter_target_resolver` | `user_reports.reporter_id`, `target_user_id`, `resolved_by` → `users.user_id` |

All other relationships follow the `*_id` columns listed on the child entity in the `.mmd` file.

## Editing the diagram cleanly

1. **In draw.io:** After Mermaid insert, select the result → **ungroup** if you need to drag tables individually. Align using **Arrange → Layout** or a grid (**View → Grid**).
2. **In source:** Edit [`diagrams/wvsudb-erd.mmd`](../diagrams/wvsudb-erd.mmd) — keep **all `entity { ... }` blocks before the relationship section** so imports stay connected.
3. **Schema changes:** When you add tables or FKs in SQL, add the entity block (alphabetically with the others), then add relationship lines in the appropriate domain section at the bottom.

## Versioning

Regenerate or reconcile this documentation when **foreign keys** or **major tables** change in `wvsudb.sql` or under `migrations/`.
