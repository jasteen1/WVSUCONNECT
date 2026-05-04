# A Table-by-Table Narrative of the WVSU Connect Database Schema

**Document type:** technical appendix (research-style exposition)  
**Scope:** entities represented in [`diagrams/wvsudb-erd.mmd`](../diagrams/wvsudb-erd.mmd), aligned with the canonical SQL dump [`wvsudb.sql`](../wvsudb.sql) and application behavior described in [`docs/wvsudb-erd.md`](wvsudb-erd.md).

---

## Abstract

This document describes the relational schema underlying **WVSU Connect**, a campus-oriented student marketplace. The schema separates **identity**, **catalog listings** (products and services), **commerce** (transactions and order detail tables), **social proof** (two review mechanisms), **asynchronous messaging**, and **governance** (reports, administrative actions, and audit trails). Each table is summarized with respect to its **purpose**, **identifier**, **salient attributes**, and **principal foreign-key relationships** to adjacent entities. The narrative complements the visual entity–relationship diagram (ERD) by stating constraints and domain semantics that a diagram alone may abbreviate.

---

## 1. Introduction

Entity–relationship modeling supports traceability between user-facing features (listings, checkout, chat, moderation) and persistent storage. The WVSU Connect ERD consolidates approximately twenty domain tables into six functional areas: (i) identity and access, (ii) taxonomy and listings, (iii) commerce, (iv) reviews, (v) messaging, and (vi) moderation and audit. Tables used only for infrastructure testing (`replication_live_test*`, `uploaded_files_test`) are outside the product narrative and are noted only in passing, consistent with [`docs/wvsudb-erd.md`](wvsudb-erd.md).

---

## 2. Materials and conventions

- **Primary source of truth for constraints:** `wvsudb.sql` (column types, indexes, declared foreign keys).  
- **Visual aggregation:** `diagrams/wvsudb-erd.mmd` (Mermaid `erDiagram`). Some logical relationships are drawn as a **single edge with a composite label** when multiple foreign keys reference the same parent (e.g., `conversations` → `users` for both participants).  
- **Runtime schema extensions:** The PHP application may add columns idempotently (for example `conversation_meta.pending_sale_buyer_id` / `pending_sale_listing_id` for post-sale messaging gates, and extended columns on `user_reviews` for photos and seller replies). Where relevant, this narrative states that the **diagram may under-specify** columns that exist only after migrations or `CREATE TABLE IF NOT EXISTS` / `ALTER TABLE` guards in code.

Notation: **PK** = primary key, **FK** = foreign key, **1:1** = one-to-one, **1:N** = one-to-many.

---

## 3. Systematic table descriptions

### 3.1 Identity and access

#### 3.1.1 `roles`

| Aspect | Description |
|--------|-------------|
| **Purpose** | Defines application roles (e.g., administrator, moderator, student) and attaches a machine-readable **permission list** (JSON) for authorization policy. |
| **Primary key** | `role_id`. |
| **Key attributes** | `name` (human-readable role label), `permissions` (JSON array of capability strings). |
| **Relationships** | **1:N** to `users`: each user row references exactly one `role_id`. |

#### 3.1.2 `users`

| Aspect | Description |
|--------|-------------|
| **Purpose** | Stores account credentials, profile presentation, verification and activation flags, and optional social or biography fields for marketplace identity. |
| **Primary key** | `user_id`. |
| **Key attributes** | `full_name`, `email`, `password` (hashed), `role_id`, `profile_pic_url`, `is_active`, `is_verified`, extended profile and social URL fields, timestamps `created_at` / `updated_at`. |
| **Relationships** | **N:1** to `roles`. **1:N** as owner to `listings`. **1:N** as buyer to `transactions`. **1:N** to `user_sessions`, `messages` (as sender), `conversations` (as `participant_a` or `participant_b`), `user_reviews` (as reviewer and/or reviewee), `user_reports` (as reporter, target, or resolver), `admin_actions` (as acting administrator), `audit_logs` (when a user-initiated event is logged), `item_status` (as `changed_by`), and legacy `reviews` (as reviewer). |

#### 3.1.3 `user_sessions`

| Aspect | Description |
|--------|-------------|
| **Purpose** | Persists login sessions for authenticated access: token material, client hints, and activity timestamps support session invalidation and security auditing. |
| **Primary key** | `session_id`. |
| **Key attributes** | `session_token`, `ip_address`, `user_agent`, `login_at`, `logout_at`, `is_active`, `last_activity` (where present in schema). |
| **Relationships** | **N:1** to `users` via `user_id`. |

---

### 3.2 Taxonomy and listings

#### 3.2.1 `categories`

| Aspect | Description |
|--------|-------------|
| **Purpose** | Controlled vocabulary for listing classification; supports **hierarchy** through a self-referential optional parent. |
| **Primary key** | `category_id`. |
| **Key attributes** | `name`, `category_type` (enumerated scope: product-only, service-only, or both). `parent_type` stores the **parent row’s** `category_id` when the category is a subcategory (**NULL** for top-level nodes). |
| **Relationships** | **Self-FK:** `parent_type` → `categories.category_id`. **1:N** to `listings` via `category_id`. The Mermaid ERD may omit drawing the self-edge to avoid layout artifacts in diagram tools; the column remains part of the entity definition. |

#### 3.2.2 `listings`

| Aspect | Description |
|--------|-------------|
| **Purpose** | **Supertype** row for any marketplace offer: shared title, description, cover image, lifecycle **status**, and discrimination by `listing_type` (product vs. service). |
| **Primary key** | `listing_id`. |
| **Key attributes** | `owner_id`, `category_id`, `listing_type`, `title`, `description`, `image_url`, `status` (e.g., active, inactive, sold out, banned), timestamps. |
| **Relationships** | **N:1** to `users` (owner), **N:1** to `categories`. **1:1** (in practice) extension rows in `products` or `services` keyed by `listing_id`. **1:N** to `service_portfolio_items`, `service_pricing_items`, `item_status`, `transactions`, `conversation_listings`, `user_reviews` (optional listing context), `user_reports` (optional). |

#### 3.2.3 `products`

| Aspect | Description |
|--------|-------------|
| **Purpose** | **Subtype** detail for product listings: unit price and inventory **stock**. |
| **Primary key** | `product_id` (with unique association to one `listing_id` in the product domain). |
| **Key attributes** | `listing_id`, `price`, `stock`. |
| **Relationships** | **N:1** to `listings`. |

#### 3.2.4 `services`

| Aspect | Description |
|--------|-------------|
| **Purpose** | **Subtype** detail for service listings: baseline **rate** and **rate_type** (e.g., per hour, fixed, negotiable). |
| **Primary key** | `service_id` (with unique association to one `listing_id`). |
| **Key attributes** | `listing_id`, `rate`, `rate_type`. |
| **Relationships** | **N:1** to `listings`. |

#### 3.2.5 `service_portfolio_items`

| Aspect | Description |
|--------|-------------|
| **Purpose** | Ordered gallery of media (images or clips) attached to a **service** listing for portfolio presentation. |
| **Primary key** | `portfolio_id`. |
| **Key attributes** | `listing_id`, `media_type`, `file_path`, `grid_span`, `sort_order`, `created_at`. |
| **Relationships** | **N:1** to `listings`. |

#### 3.2.6 `service_pricing_items`

| Aspect | Description |
|--------|-------------|
| **Purpose** | Optional **line-item price list** for services (named packages or add-ons), with display order. |
| **Primary key** | `price_item_id`. |
| **Key attributes** | `listing_id`, `item_name`, `amount`, `sort_order`, `created_at`. |
| **Relationships** | **N:1** to `listings`. |

#### 3.2.7 `item_status`

| Aspect | Description |
|--------|-------------|
| **Purpose** | **Append-only or historical log** of listing status transitions (old vs. new status), supporting accountability and trigger-driven auditing. |
| **Primary key** | `status_id`. |
| **Key attributes** | `listing_id`, `old_status`, `new_status`, `changed_by` (nullable user), `reason`, `changed_at`. |
| **Relationships** | **N:1** to `listings`, **N:1** to `users` when `changed_by` is set. |

---

### 3.3 Commerce

#### 3.3.1 `transactions`

| Aspect | Description |
|--------|-------------|
| **Purpose** | Represents a **commercial intent or order** from a buyer toward a listing: monetary total, quantity, type (product vs. service), and workflow **status** (pending through completed or cancelled). The seller is **not** denormalized here; it is obtained via `listings.owner_id`. |
| **Primary key** | `transaction_id`. |
| **Key attributes** | `buyer_id`, `listing_id`, `transaction_type`, `quantity`, `total_price`, `status`, timestamps. |
| **Relationships** | **N:1** to `users` (buyer), **N:1** to `listings`. **1:1** optional extension in `product_orders` or `service_bookings`. **1:N** or optional link to legacy `reviews` by `transaction_id`. |

#### 3.3.2 `product_orders`

| Aspect | Description |
|--------|-------------|
| **Purpose** | **Product-side** fulfillment attributes: delivery address and delivery timestamp. |
| **Primary key** | `order_id`. |
| **Key attributes** | `transaction_id` (unique in practice for 1:1 with a product transaction), `delivery_address`, `delivered_at`. |
| **Relationships** | **N:1** to `transactions` (one detail row per product transaction when used). |

#### 3.3.3 `service_bookings`

| Aspect | Description |
|--------|-------------|
| **Purpose** | **Service-side** fulfillment: client requirements, agreed price, scheduled time. |
| **Primary key** | `booking_id`. |
| **Key attributes** | `transaction_id` (unique for 1:1 with a service transaction when used), `requirements`, `agreed_price`, `scheduled_at`. |
| **Relationships** | **N:1** to `transactions`. |

---

### 3.4 Reviews and reputation

#### 3.4.1 `reviews` (transaction-centric, legacy-oriented)

| Aspect | Description |
|--------|-------------|
| **Purpose** | Binds a star rating and comment to a **specific transaction**, modeling “review the purchase event.” |
| **Primary key** | `review_id`. |
| **Key attributes** | `reviewer_id`, `transaction_id`, `rating`, `comment`, `created_at`. |
| **Relationships** | **N:1** to `users` (reviewer), **N:1** to `transactions`. The application may favor `user_reviews` for newer flows; this table remains in the schema as a transaction-anchored alternative. |

#### 3.4.2 `user_reviews` (peer- and listing-centric)

| Aspect | Description |
|--------|-------------|
| **Purpose** | Stores **reputation feedback** from one user toward another, optionally scoped to a **listing** (typical for “buyer reviews seller after sale” and profile-visible feedback). |
| **Primary key** | `review_id`. |
| **Key attributes** | `reviewer_id`, `reviewee_id`, optional `listing_id`, `rating`, `comment`, timestamps. Deployed systems may add `photo_url`, seller reply fields, and adjusted unique indexes via application migrations—the ERD lists the core columns. |
| **Relationships** | **N:1** to `users` twice (reviewer and reviewee), **N:1** optionally to `listings`. |

---

### 3.5 Messaging

#### 3.5.1 `conversations`

| Aspect | Description |
|--------|-------------|
| **Purpose** | One **thread** between two participants, identified by ordered pair (`participant_a`, `participant_b`) with uniqueness enforced so the dyad maps to at most one conversation row. |
| **Primary key** | `conversation_id`. |
| **Key attributes** | `participant_a`, `participant_b`, `created_at`, `last_message_at` (for inbox ordering). |
| **Relationships** | **N:1** to `users` for each participant column. **1:1** optional row in `conversation_meta`. **1:N** to `messages`, `conversation_listings`, and optionally `user_reports` (logical context). |

#### 3.5.2 `conversation_meta`

| Aspect | Description |
|--------|-------------|
| **Purpose** | Per-conversation **flags**; notably whether the thread is treated as **closed** after a sale or moderation workflow. Additional columns may exist at runtime for **pending post-sale feedback** (buyer must submit review before general messaging resumes). |
| **Primary key** | `conversation_id` (same value as parent conversation). |
| **Key attributes** | `is_closed` (and application-added pending-sale columns where deployed). |
| **Relationships** | **1:1** with `conversations`. |

#### 3.5.3 `conversation_listings`

| Aspect | Description |
|--------|-------------|
| **Purpose** | **Associative** table linking a conversation to one or more **listings** the participants are discussing (e.g., “contact seller” from a listing page). Supports multiple inserts over time so new contact actions can create fresh context rows, depending on application policy. |
| **Primary key** | `id`. |
| **Key attributes** | `conversation_id`, `listing_id`, `created_at`. |
| **Relationships** | **N:1** logically to `conversations` and `listings`. The canonical dump may **not declare foreign-key constraints** on this table; integrity is maintained by application logic—see [`docs/wvsudb-erd.md`](wvsudb-erd.md). |

#### 3.5.4 `messages`

| Aspect | Description |
|--------|-------------|
| **Purpose** | Individual **chat messages** within a conversation, including read state and optional image attachments. |
| **Primary key** | `message_id`. |
| **Key attributes** | `conversation_id`, `sender_id`, `content`, `sent_at`, `is_read`, `message_type`, `image_url` (where present). |
| **Relationships** | **N:1** to `conversations`, **N:1** to `users` (sender). |

---

### 3.6 Moderation, governance, and audit

#### 3.6.1 `user_reports`

| Aspect | Description |
|--------|-------------|
| **Purpose** | Captures **user-generated reports** of policy violations or safety concerns, with categorization, free-text details, lifecycle status, resolution metadata, and optional linkage to a listing or conversation. |
| **Primary key** | `report_id`. |
| **Key attributes** | `reporter_id`, `target_user_id`, optional `listing_id`, optional `conversation_id`, `reason_type`, `details`, `status`, timestamps, `resolved_by`, `resolution_notes`. |
| **Relationships** | **N:1** to `users` for reporter, target, and resolver; optional **N:1** to `listings` and logical reference to `conversations`. |

#### 3.6.2 `admin_actions`

| Aspect | Description |
|--------|-------------|
| **Purpose** | Structured log of **privileged actions** (bans, listing removals, report resolutions, etc.) performed by staff accounts. |
| **Primary key** | `action_id`. |
| **Key attributes** | `admin_id`, `action_type`, `target_entity_id`, `entity_type`, `notes`, `performed_at`. |
| **Relationships** | **N:1** to `users` (administrator who performed the action). |

#### 3.6.3 `audit_logs`

| Aspect | Description |
|--------|-------------|
| **Purpose** | General-purpose **event stream** for security and operational forensics: who did what, on which entity, with optional JSON or text metadata. Rows may be inserted by **application code** or by **database triggers** (in which case `user_id` may be null). |
| **Primary key** | `log_id`. |
| **Key attributes** | `user_id`, `event_type`, `entity_type`, `entity_id`, `metadata`, `logged_at`. |
| **Relationships** | Optional **N:1** to `users`. |

---

### 3.7 Tables excluded from the ERD (non-domain)

| Table | Rationale for exclusion from product ERD |
|-------|------------------------------------------|
| `replication_live_test`, `replication_live_test_2` | Infrastructure validation for database replication—not marketplace data. |
| `uploaded_files_test` | Ad hoc file upload testing. |

---

## 4. Discussion

**Supertype–subtype listing model.** Centralizing shared attributes in `listings` while isolating `products` vs. `services` attributes avoids null-heavy wide tables and keeps browse queries join-friendly at the cost of mandatory application checks that each listing has the correct child row.

**Dual review pathways.** `reviews` (transaction-keyed) and `user_reviews` (peer-keyed, optional listing) reflect an evolution toward profile- and listing-visible reputation while retaining a transaction-anchored pattern suitable for strict “one review per order” semantics.

**Messaging and commerce coupling.** `conversation_listings` bridges informal negotiation with catalog identity; `conversation_meta` encodes thread-level policy (closure, post-sale gating). These design choices favor **flexible human workflows** over rigid 1:1 mapping between messages and transactions.

**Diagram vs. database.** Readers should reconcile the Mermaid file with `wvsudb.sql` whenever columns or foreign keys change; the narrative above is descriptive, not a substitute for migration version control.

---

## 5. Conclusion

The WVSU Connect schema decomposes the marketplace into **normative entities** for users and roles, **catalog** tables centered on `listings`, **commerce** detail in `transactions` and subtype tables, **bilateral communication** in `conversations` / `messages` / `conversation_listings`, **reputation** in `user_reviews` (and legacy `reviews`), and **governance** in `user_reports`, `admin_actions`, and `audit_logs`. Table-by-table reading of the ERD therefore maps cleanly onto feature areas: identity, listing management, orders, chat, reviews, and moderation.

---

## References

1. WVSU Connect project: `wvsudb.sql` — table definitions, indexes, foreign keys.  
2. WVSU Connect project: `diagrams/wvsudb-erd.mmd` — Mermaid ERD source.  
3. WVSU Connect project: `docs/wvsudb-erd.md` — diagram usage, composite relationship labels, and omitted entities.  
4. WVSU Connect project: `docs/WVSU-Connect-Documentation.md` — broader architecture and trigger-driven audit behavior.
