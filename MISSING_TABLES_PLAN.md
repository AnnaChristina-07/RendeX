# Database Schema Gap Analysis

Based on the review of your current database structure and standard requirements for the RendeX platform, here is a plan identifying potential missing tables that were likely in your design PDF.

## 1. Current Implemented Tables
The following tables are currently active in your database:
- `users`: Stores all user accounts (Renters, Owners, Drivers, Admin).
- `items`: Stores products/listings.
- `rentals`: Stores booking transactions.
- `deliveries`: Stores delivery logic and driver assignments.
- `driver_applications`: Stores pending driver approvals.
- `reviews`: Stores ratings and feedback.
- `notifications`: Stores user alerts.
- `admin_notifications`: Stores admin alerts.
- `password_resets`: Stores reset tokens.

## 2. Missing Tables (Proposed Plan)
These tables are likely part of your comprehensive "Database Table Design PDF" but are not yet implemented in the code.

### A. Communication
- **`messages`**: To store chat history between Renters and Owners.
  - *Columns*: `id`, `sender_id`, `receiver_id`, `item_id`, `message`, `is_read`, `created_at`.

### B. User Engagement
- **`wishlist`** (or `favorites`): To allow users to save items they like.
  - *Columns*: `id`, `user_id`, `item_id`, `created_at`.

### C. Financials
- **`transactions`** (or `payments`): To handle detailed payment logs, distinct from the rental logic.
  - *Columns*: `id`, `rental_id`, `user_id`, `amount`, `transaction_type` (debit/credit), `status`, `payment_gateway_ref`.

### D. Data Management
- **`categories`**: To manage categories dynamically instead of hardcoding them in `items`.
  - *Columns*: `id`, `name`, `slug`, `icon`, `parent_id`.

### E. Trust & Safety
- **`disputes`** (or `reports`): For users to report items or issues with rentals.
  - *Columns*: `id`, `reporter_id`, `reported_item_id` or `rental_id`, `reason`, `status`.

## 3. Next Steps
- **Review**: Please cross-check this list with your PDF.
- **Action**: If you confirm these are needed, we can create a migration plan to add them one by one without breaking the existing site.
