# Rental Return Process Plan

## 1. Overview
The goal is to establish a secure, transparent, and efficient process for returning rented items to their owners. This process must handle logistics (pickup vs. drop-off), condition verification, and financial settlement (security deposits).

## 2. Detailed Workflow

### Stage 1: Return Initiation (Renter)
*   **Trigger**: Renter goes to "My Rentals" and selects "Return Item" (available X days before due date).
*   **Action**: Renter fills out a **Return Request Form**:
    *   **Method**: Select "Self Drop-off" or "Request Pickup" (if available in area).
    *   **Schedule**: Proposed date and time window.
    *   **Condition Report**:
        *   Self-assessment (Same as Received / Minor Wear / Damaged).
        *   **Photo Evidence**: Mandatory upload of current item condition (Front, Back, Serial No.).
*   **Outcome**: Rental status updates to `return_initiated`. Owner is notified.

### Stage 2: Logistics Handling

#### Scenario A: Self Drop-off
*   **Process**: Renter travels to Owner's location at the agreed time.
*   **Security**: Renter scans a QR code on Owner's app (or provides a unique code) to prove arrival.

#### Scenario B: Delivery Partner Pickup
*   **Process**: System creates a `delivery` task aimed at the Owner.
*   **Driver Assignment**: Nearby drivers receive the request.
*   **Pickup**: Driver arrives at Renter's location. Driver validates item condition (basic visual check) against uploaded photos. Driver marks "Picked Up".
*   **Transit**: Item interacts with `deliveries` table tracking.
*   **Delivery**: Driver arrives at Owner's location.

### Stage 3: Owner Inspection & Handover
*   **Action**: Owner receives the item (from Renter or Driver).
*   **Inspection Period**: Owner has a limited window (e.g., 24 hours) to inspect the item thoroughly.
*   **Decision**: Owner logs into dashboard and selects one of two options:
    1.  **Accept & Complete**: Item is in good condition.
    2.  **Report Issue / Dispute**: Item is damaged, late, or missing parts.

### Stage 4: Settlement & Completion
*   **If Accepted**:
    *   Rental status updates to `completed`.
    *   Item availability resets to `available`.
    *   **Security Deposit**: Automatically initiated for refund to Renter.
    *   **Reviews**: Both parties prompted to leave reviews.
*   **If Disputed**:
    *   Rental status updates to `disputed`.
    *   Owner uploads proof of damage.
    *   Admin is notified to mediate.
    *   Security deposit is frozen until resolution.

## 3. Database Schema Updates

### `rentals` Table
We need to track the return lifecycle more granularly than just `returned`.

| Column Name | Type | Description |
| :--- | :--- | :--- |
| `return_status` | ENUM | `none`, `scheduled`, `in_transit`, `pending_inspection`, `completed`, `disputed` |
| `return_method` | ENUM | `dropoff`, `pickup` |
| `return_scheduled_at` | DATETIME | When the return is booked for. |
| `condition_notes` | TEXT | Renter's notes on condition. |
| `condition_images` | JSON | Paths to images uploaded by Renter. |
| `owner_confirm_at` | DATETIME | When the owner confirmed receipt. |
| `damage_fee` | DECIMAL | Amount deducted from deposit (if any). |

### `deliveries` Table
Existing table is sufficient but needs to distinguish between `forward` (delivery to renter) and `return` (back to owner) types.
*   Add `type` ENUM('delivery', 'return') DEFAULT 'delivery'.

## 4. UI/UX Requirements

### 1. Return Request Page (Renter)
*   Already partially implemented in `return-rental.php`.
*   **Upgrade**: Add "Submit Request" button instead of immediate "Return". Add image upload handling.

### 2. Owner Dashboard - "Pending Returns"
*   New section displaying items coming back.
*   "Inspect & Approve" button.
*   Dispute form with photo upload.

### 3. Driver App (for Pickup)
*   New task type: "Return Pickup".
*   Instructions: "Pick up from Renter X, Deliver to Owner Y".

## 5. Implementation Roadmap
1.  **Database Migration**: Update `rentals` and `deliveries` tables.
2.  **Backend Logic**: Create `process_return.php`, update `admin_dashboard` for disputes.
3.  **Frontend**: Enhancing `return-rental.php` and creating `owner_verify_return.php`.
4.  **Notifications**: Email/SMS alerts at each stage (Return Requested -> Picked Up -> Verified -> Refunded).
