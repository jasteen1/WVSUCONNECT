# WVSU Connect — IMRaD documentation

This document describes the **WVSU Connect** system using the **IMRaD** structure common in scholarly and technical reporting: **Introduction**, **Methods**, **Results**, and **Discussion**. It complements the implementation-oriented handbook [`WVSU-Connect-Documentation.md`](WVSU-Connect-Documentation.md).

**Audience:** Stakeholders, reviewers, or developers who want a concise narrative of purpose, approach, delivered capabilities, and limitations rather than a file-by-file manual.

---

## Introduction

### Context and problem

Campus communities need a trusted way for students to **discover**, **offer**, and **exchange** goods and services without relying solely on informal channels (group chats, bulletin boards) that scale poorly, lack structure, and offer little support for **moderation**, **transaction history**, or **reputation**.

### Objectives

**WVSU Connect** addresses that gap as a **campus-oriented student marketplace**. Its objectives are to:

1. Support **two listing types**—**products** (priced inventory) and **services** (rates, portfolios, structured pricing)—under one catalog model.
2. Enable **discovery** through a shared **category** taxonomy that applies to both listing types (for example, *Electronics* or *Academic Help*), rather than duplicating navigation along product/service lines only.
3. Provide **messaging** between participants, **transactions** (orders and bookings), **reviews**, **user reports**, and an **admin/moderator** surface for governance.
4. Maintain **operational traceability** through **audit logs**, **admin actions**, and **database triggers** for critical catalog and status changes.

### Scope

This repository implements a **traditional server-rendered web application**: PHP pages query **MariaDB/MySQL** and return HTML. There is **no** separate REST API or single-page application layer documented in the codebase; scope is the marketplace, messaging, commerce flows, moderation, and supporting schema as shipped in `wvsudb.sql` and `migrations/`.

---

## Methods

### Design approach

- **Listing-centric data model:** A single **`listings`** row represents any offer, distinguished by **`listing_type`** (product or service). Type-specific attributes live in **`products`**, **`services`**, and related tables (**`service_portfolio_items`**, **`service_pricing_items`**), avoiding duplicate listing headers across types.
- **Session-based authentication:** Identity and authorization state are carried in **`$_SESSION`** (for example `user_id`, `role_id`), with shared bootstrap via **`db_conn.php`** and navigation via **`navbar.php`** where applicable.
- **Read/write database split (logical):** Application code distinguishes **master** connections for writes and **slave** (or read) connections for reads, with **fallback to the master** when a replica is unavailable—supporting local single-server setups (for example XAMPP) without code forks.
- **Defense in depth for redirects:** **`wvsu_login_redirect_destination()`** (in `wvsu_auth_redirect.inc.php`) restricts post-login destinations to **relative, same-site** paths on an allowlist, mitigating open redirect vulnerabilities.
- **Auditability:** **`audit_logs`** captures events with optional **`user_id`** and JSON **`metadata`** (validated where constraints apply). **MariaDB/MySQL triggers** on **`listings`** (status changes), **`products`**, and **`services`** append rows when catalog rows change, so history is preserved even when changes occur outside PHP.

### Materials (technology stack)

| Layer | Implementation |
|--------|----------------|
| Application | PHP (strict typing in newer files), page-per-feature scripts |
| Data access | `mysqli`; helper functions **`fetch`**, **`fetchAll`**, **`fetch_master`** |
| Database | MariaDB/MySQL; canonical schema in **`wvsudb.sql`**; incremental changes in **`migrations/`** |
| Presentation | HTML, Bootstrap 5, Bootstrap Icons, shared theme CSS and motion helpers |
| Documentation of schema | Mermaid ER diagram **`diagrams/wvsudb-erd.mmd`**, narrative in **`docs/wvsudb-erd.md`** |

### Operational methodology (runtime)

Typical page bootstrap:

1. **`db_conn.php`** — database connections, `session_start()`, query helpers, and idempotent helper DDL where applicable.
2. **`head_assets.php`** — styles, icons, favicon, cache-busting query strings.
3. **`navbar.php`** — when used, session-aware navigation, unread message indicators, admin entry points for privileged roles.

Commerce is modeled with **`transactions`** as the header row; **`product_orders`** and **`service_bookings`** attach **one-to-one** by **`transaction_id`** for product-specific and service-specific fulfillment fields, respectively. Seller identity is resolved via **`listings.owner_id`**, not necessarily duplicated on every transaction row.

---

## Results

### Delivered system capabilities

The implemented system provides the following **observable outcomes** (as reflected in application scripts and schema):

| Capability area | Outcome |
|-----------------|--------|
| **Catalog** | Browse and filter listings; product and service detail views; seller management of “your” listings and stock updates where applicable. |
| **Commerce** | Transaction lifecycle with distinct product order and service booking extensions. |
| **Communication** | Two-participant **conversations**, **messages**, optional association of threads to listings via **`conversation_listings`**, and **conversation_meta** for thread-level flags. |
| **Trust and safety** | Transaction-linked **reviews**, peer-style **user_reviews**, **user_reports**, and **admin** workflows (`admin_dashboard.php`, processing scripts). |
| **Governance data** | **`admin_actions`** for structured moderator events; **`audit_logs`** for a broader event stream; **`item_status`** history when listing **status** changes (including trigger-driven inserts alongside audit entries). |
| **Documentation assets** | Full SQL snapshot, migration history, ER diagram, and developer handbook for onboarding and schema reasoning. |

### Structural results (subsystems)

The database and application align around these **subsystems**: identity (**`roles`**, **`users`**, **`user_sessions`**); catalog (**`categories`**, **`listings`**, extensions); commerce (**`transactions`**, **`product_orders`**, **`service_bookings`**); reviews; messaging; and governance (**`user_reports`**, **`admin_actions`**, **`audit_logs`**).

### Trigger catalog (as implemented in schema)

Automated **`audit_logs`** inserts (without **`user_id`**) are defined for product and service lifecycle events and for **listing status** changes, with **`LISTING_STATUS_CHANGED`** also writing **`item_status`** history—documented in detail in [`WVSU-Connect-Documentation.md`](WVSU-Connect-Documentation.md) §12.

---

## Discussion

### Interpretation

WVSU Connect’s design favors **a single listing abstraction** with **typed extensions**, which simplifies navigation and reporting across products and services while keeping **commerce** and **messaging** orthogonal concerns. Splitting reads and writes at the connection level anticipates **read scaling** without forcing small deployments to run multiple database instances.

Combining **PHP-inserted** audit rows (when the actor is known) with **trigger-inserted** rows (when only row-level facts are known) improves **forensic completeness** at the cost of **uniform attribution**: trigger rows may omit **`user_id`**, which analysts should interpret as a limitation of trigger context, not missing application logic.

### Limitations

- **No standalone API layer:** Integration with external clients would require new interfaces or scraping server-rendered pages—not supported as a first-class pattern in this repo.
- **Application-enforced integrity in places:** Some relationships (for example **`conversation_listings`**, optional report linkage to conversations) may rely on **indexes and application logic** where foreign keys are not declared in the dump; deployers should treat the **SQL file and ERD companion** as the source of truth for strictness.
- **Institutional policy:** Pages such as **`safety.php`** communicate **campus-appropriate** guidance; they do not replace formal institutional rules or incident response processes.
- **Replica lag:** Read paths use the slave when configured; UIs that must show **immediate consistency** after a write should use **`fetch_master`** where already applied—misuse can still show stale data in edge cases.

### Future work and maintenance

Schema evolution should continue through **`migrations/`** with **`wvsudb.sql`** refreshed or documented as the **full install** baseline. Changes to **triggers** or **`audit_logs`** shape should be applied via controlled **`DROP TRIGGER` / `CREATE TRIGGER`** migrations and tested on copies first, preserving **`json_valid(metadata)`** expectations where enforced.

---

## Related documents

| Document | Role |
|----------|------|
| [`WVSU-Connect-Documentation.md`](WVSU-Connect-Documentation.md) | Full system and database handbook (architecture, files, triggers, setup). |
| [`wvsudb-erd.md`](wvsudb-erd.md) | ERD conventions and domain grouping. |
| [`../diagrams/wvsudb-erd.mmd`](../diagrams/wvsudb-erd.mmd) | Editable Mermaid ER source. |
| [`../wvsudb.sql`](../wvsudb.sql) | Canonical schema, constraints, and triggers. |

---

*IMRaD narrative aligned with the repository’s documented architecture and schema. Update this document when product goals, major features, or governance model change materially.*
