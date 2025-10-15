# PROJECT DIARY - Daily Sales & Expense Management System

**Last Updated:** October 15, 2025
**Current Branch:** `codex/add-manager-pending-card-for-clarity-tv18os`
**Database Schema:** `database/br (4).sql`

---

## 📋 PROJECT OVERVIEW

This is a **multi-outlet daily sales and expense reporting system** designed for a business operating multiple sales channels. The system manages the complete workflow from outlet closing reports to accountant verification and finance approval.

### Business Context
- The company operates multiple outlets
- Each outlet generates income from 4 different streams:
  1. **Berhad Sales** (external system agent ID)
  2. **MP Coba Sales** (external system login ID)
  3. **MP Perdana Sales** (external system login ID)
  4. **Market Sales**
- Managers submit daily closing reports with expenses
- Accountants verify submissions against external system data
- Finance department provides final approval

---

## 👥 USER ROLES & RESPONSIBILITIES

### 1. **Manager** (views/manager/)
- Creates daily outlet closing submissions
- Records income from all 4 sales streams
- Submits expenses with receipts (categorized as MP/Berhad or Market)
- Can batch submit multiple outlets at once
- Decides how much cash to send to HQ vs keep on hand
- Must provide variance reason if keeping cash

**Key Pages:**
- `submit_expenses.php` - Create daily submissions
- `submit_to_hq.php` - Batch submit multiple outlet closings
- `view_history.php` - View submission history and status

### 2. **Accountant** (views/account/)
- Reviews all pending manager submissions
- Verifies income against external sales portal data
- Uploads external sales data for comparison
- System automatically validates if submitted amounts match external data
- Approves or rejects submissions
- Can send back to manager for corrections

**Key Pages:**
- `verify_submission.php` - Main dashboard showing all pending submissions grouped by manager
- `berhad_sales_verification.php` - Verify Berhad sales stream
- `berhad_sales_verification_process.php` - Detailed verification workflow
- `mp_coba_sales_verification.php` - Verify MP Coba sales stream
- `mp_perdana_sales_verification.php` - Verify MP Perdana sales stream
- `outlet_verification.php` - Verify outlet-specific data

### 3. **Finance**
- Final approval of verified submissions
- Reviews submissions that passed accountant verification

### 4. **CEO** (views/ceo/)
- Views reports and dashboards
- High-level overview of all operations

### 5. **Admin** (views/admin/)
- User management
- System configuration
- Manages expense categories

---

## 🔄 WORKFLOW PROCESS

### Phase 1: Manager Submission
```
Manager → Select Outlet → Enter Income (4 streams) → Add Expenses with Receipts → Submit
```

1. Manager selects outlet and submission date
2. Enters income for all 4 streams:
   - Berhad Sales
   - MP Coba Sales
   - MP Perdana Sales
   - Market Sales
3. Adds expenses (MP/Berhad or Market category)
4. Each expense requires:
   - Category selection
   - Amount
   - Description (optional)
   - Receipt/voucher upload (mandatory)
5. System calculates: `Net Amount = Total Income - Total Expenses`
6. Can create multiple submissions (one per outlet/date)
7. Status: `draft` (saved but not submitted)

### Phase 2: Batch Submission to HQ
```
Manager → submit_to_hq.php → Select draft submissions → Batch submit → Status: pending
```

1. Manager reviews all draft submissions
2. Can submit multiple outlets together as a batch
3. For each submission, manager decides:
   - **Amount to HQ:** Cash to send to headquarters
   - **Cash Kept:** Cash manager retains (calculated automatically)
   - **Variance Reason:** Required if amount_to_hq ≠ net_amount
4. System creates batch code for tracking
5. All submissions in batch get status: `pending`
6. Manager's cash on hand balance is updated

### Phase 3: Accountant Verification
```
Accountant → verify_submission.php → Select income stream → Upload external data → Compare → Save & Verify
```

**Overview Page (`verify_submission.php`):**
- Shows all pending submissions grouped by manager
- Displays summary cards for each manager with:
  - Total income by stream
  - Total expenses (MP/Berhad vs Market)
  - Number of outlets and submissions
- Quick links to verify each income stream

**Income Stream Verification (e.g., `berhad_sales_verification.php`):**
1. Accountant pastes external sales data from external portal
2. System automatically parses the data (supports tab/comma/semicolon delimited)
3. Real-time preview shows parsed data in structured table
4. Click "Compare Data" button:
   - System matches outlets by their external agent/login IDs
   - Compares both sales AND expenses (claimed amounts)
   - Shows match/mismatch/not found status for each outlet
5. If all outlets match (sales + expenses match exactly):
   - "Save & Verify" button enabled
   - Data saved to `berhad_external_sales_data` table
   - Accountant can proceed to approval
6. If mismatches found:
   - Shows detailed comparison for each outlet
   - Accountant must resolve discrepancies
   - Can send back to manager for correction

**External Sales Data Format:**
```
Agent ID    Name         Level    Deposit Count    Total Deposit    Withdraw Count    Total Withdraw    Total
pasar888    outlet A     Agent    515              1000             175               0                 10782.60
senadin8    outlet B     Agent    68               2000             13                0                 259
```

- **Total Deposit** = Sales amount (compared against submitted sales)
- **Total Withdraw** = Expenses claimed (compared against BERHAD/MP expenses)

### Phase 4: Finance Approval
```
Finance → Review verified submissions → Approve → Status: approved
```

---

## 🗄️ DATABASE SCHEMA

### Core Tables

#### **users**
- `id`: User ID
- `name`: Full name
- `email`: Email address
- `username`: Login username
- `password_hash`: Argon2id hashed password
- `role`: manager | account | ceo | admin | finance
- `status`: active | inactive | suspended

#### **outlets**
- `id`: Outlet ID
- `outlet_code`: Unique code (e.g., OUT-A)
- `outlet_name`: Display name
- `berhad_agent_id`: External Berhad system agent ID (for comparison)
- `mp_coba_login_id`: External MP Coba system login ID
- `mp_perdana_login_id`: External MP Perdana system login ID
- `manager_id`: FK to users (who manages this outlet)
- `status`: active | inactive

#### **daily_submissions**
The heart of the system - stores all submission data.

**Income Fields:**
- `berhad_sales`: Income from Berhad
- `mp_coba_sales`: Income from MP Coba
- `mp_perdana_sales`: Income from MP Perdana
- `market_sales`: Income from market
- `total_income`: Sum of all income streams

**Expense Fields:**
- `total_expenses`: Total expenses claimed
- `net_amount`: total_income - total_expenses

**Cash Management:**
- `amount_to_hq`: Amount manager chooses to send to HQ
- `cash_kept`: Cash manager keeps (net_amount - amount_to_hq)
- `variance_reason`: Required if amount_to_hq ≠ net_amount
- `variance_supporting_doc`: Optional document

**Status Workflow:**
- `status`: draft | pending | resubmit | submitted_to_finance | recheck | approved | verified | rejected | revised

**Tracking Fields:**
- `submission_code`: Unique code (e.g., SUB-20251014-001-AC63)
- `batch_code`: Groups submissions submitted together
- `submission_date`: Date of the outlet closing
- `verified_by`: Accountant who verified
- `approved_by_finance`: Finance user who approved
- `verified_at`, `approved_by_finance_at`, `returned_to_accountant_at`, `returned_to_manager_at`, `submitted_to_hq_at`, `submitted_to_finance_at`

**Notes:**
- `notes`: Manager notes
- `accountant_notes`: Notes from accountant
- `finance_notes`: Notes from finance
- `rejection_reason`: If rejected

#### **expenses**
Line items for each expense in a submission.

- `submission_id`: FK to daily_submissions
- `expense_category_id`: FK to expense_categories
- `amount`: Expense amount
- `description`: Optional description
- `receipt_file`: Uploaded receipt filename
- `is_categorized`: Whether accountant has categorized (future use)
- `approval_status`: pending | approved | rejected
- `approved_by`: Accountant who approved/rejected
- `rejection_reason`: If rejected

#### **expense_categories**
Predefined categories for expenses.

- `category_name`: Display name
- `category_type`: mp_berhad | market
- `description`: Category description
- `status`: active | inactive

**Default Categories:**
**MP/Berhad Type:**
1. Staff Salary
2. Rent
3. Utilities
4. Transportation
5. Maintenance
6. Supplies
7. Marketing
8. Insurance
9. Miscellaneous
10. MP_COBA (for claimed expenses)
11. BERHAD (for claimed expenses)
12. Uncategorized (default for pending categorization)

**Market Type:**
1. Purchase Goods
2. Vendor Payment
3. Delivery Fees
4. Packaging
5. Market Rent
6. Market Utilities
7. Market Supplies
8. Market Miscellaneous

#### **berhad_external_sales_data**
Stores external sales data from Berhad portal for comparison.

- `submission_id`: FK to daily_submissions
- `row_index`: Order of row in uploaded data
- `agent_identifier`: Agent ID from external system (matches outlet.berhad_agent_id)
- `name`: Outlet name
- `level`: Agent level
- `deposit_count`: Number of deposits
- `total_deposit`: Total deposit amount (this is the SALES figure)
- `withdraw_count`: Number of withdrawals
- `total_withdraw`: Total withdraw amount (this is the CLAIMED/EXPENSES figure)
- `total`: Net total
- `saved_by`: User who uploaded this data
- `created_at`, `updated_at`

#### **mp_coba_external_sales_data**
Similar structure to berhad_external_sales_data but for MP Coba portal.

- `login_id`: Login ID from external system (matches outlet.mp_coba_login_id)
- Contains detailed commission/sales breakdown columns
- Used for MP Coba verification workflow

#### **mp_perdana_external_sales_data** (structure pending)
Will follow similar pattern for MP Perdana verification.

#### **manager_cash_on_hand**
Tracks cumulative cash balance each manager is holding.

- `manager_id`: FK to users
- `current_balance`: Current accumulated cash
- `last_updated`: Last balance update timestamp

---

## 🔧 KEY FEATURES

### 1. **Automated External Data Comparison**
- Accountant pastes raw data from external portals
- System automatically:
  - Detects delimiter (tab/comma/semicolon)
  - Parses into structured format
  - Matches by outlet external IDs
  - Compares BOTH sales and claimed expenses
  - Shows match/mismatch/missing status
  - Only allows save if ALL outlets match

### 2. **Cash Variance Tracking**
- Managers can choose to keep cash vs send to HQ
- Must provide reason if keeping cash
- System tracks manager's cumulative cash balance
- Prevents negative balances

### 3. **Batch Submission**
- Submit multiple outlets together
- Single batch code for tracking
- All submissions move through workflow together

### 4. **Expense Management** ✨ UPDATED
- **Manager Side:**
  - Simplified submission: Enter total expense amount only
  - Upload multiple receipt files (JPG, PNG, PDF)
  - No category selection required (accountant will review)
- **Accountant Side:**
  - Dedicated expenses verification page
  - Visual receipt preview (images inline, PDFs with icon)
  - Modal for enlarging images
  - Approve or Request Resubmit with reason
  - All receipts stored as JSON in database
- **Workflow:**
  - All manager expenses marked as "Uncategorized"
  - Accountant reviews receipts manually
  - Approve → expense status = approved
  - Reject → submission status = resubmit (sent back to manager)

### 5. **Status Workflow**
Complex multi-stage workflow:
- `draft` → Manager saved but not submitted
- `pending` → Submitted to accountant, awaiting verification
- `submitted_to_finance` → Accountant verified, sent to finance
- `approved` → Finance approved
- `resubmit` → Sent back to manager for corrections
- `recheck` → Sent back to accountant from finance
- `rejected` → Rejected with reason
- `verified` → Verified by accountant (legacy status)

### 6. **Duplicate Detection**
- System warns if submission already exists for outlet+date
- If draft exists, offers link to edit instead of creating new

---

## 📁 PROJECT STRUCTURE

```
my_site/
├── includes/
│   ├── config.php              # App configuration
│   ├── db_connect.php          # Database connection
│   ├── db.php                  # Database helper functions
│   ├── init.php                # Initialization (loads all includes)
│   ├── auth.php                # Authentication functions
│   ├── csrf.php                # CSRF protection
│   ├── submission_handler.php  # Core submission processing logic
│   ├── check_duplicate_submission.php
│   ├── update_submission.php
│   └── account/
│       ├── save_berhad_external_sales.php          # Save Berhad external data
│       ├── save_berhad_external_sales_batch.php    # Batch save with validation
│       ├── save_mp_coba_external_sales_batch.php
│       ├── save_mp_perdana_external_sales_batch.php
│       ├── approve_expenses.php                    # ✨ NEW: Approve uncategorized expenses
│       └── reject_expenses.php                     # ✨ NEW: Reject expenses & request resubmit
│
├── views/
│   ├── manager/
│   │   ├── dashboard.php           # Manager home
│   │   ├── submit_expenses.php     # Create daily submissions
│   │   ├── submit_to_hq.php        # Batch submit to HQ
│   │   ├── view_history.php        # Submission history
│   │   └── edit_submission.php     # Edit draft submissions
│   │
│   ├── account/
│   │   ├── dashboard.php                              # Accountant home
│   │   ├── verify_submission.php                      # Main pending submissions view
│   │   ├── expenses_verification.php                  # ✨ NEW: Review & approve/reject expenses
│   │   ├── berhad_sales_verification.php              # Berhad verification landing
│   │   ├── berhad_sales_verification_process.php      # Detailed Berhad workflow (per outlet)
│   │   ├── mp_coba_sales_verification.php
│   │   ├── mp_coba_sales_verification_process.php
│   │   ├── mp_perdana_sales_verification.php
│   │   ├── mp_perdana_sales_verification_process.php
│   │   └── outlet_verification.php
│   │
│   ├── ceo/
│   │   ├── dashboard.php
│   │   └── reports.php
│   │
│   └── admin/
│       ├── dashboard.php
│       └── manage_users.php
│
├── database/
│   ├── br (4).sql                    # LATEST database schema ⭐
│   ├── br (1).sql                    # Old schema
│   ├── 004_create_mp_coba_external_sales_table.sql
│   ├── 005_add_outlet_external_ids.sql
│   └── queries/
│       └── create_mp_coba_external_sales_data_table.sql
│
├── uploads/
│   └── receipts/                     # Uploaded receipt files
│
├── auth/
│   ├── login.php
│   └── logout.php
│
├── index.php                         # Entry point (redirects to dashboards)
└── PROJECT_DIARY.md                 # This file
```

---

## 🚀 RECENT WORK & CURRENT FOCUS

### Latest Commits (as of Oct 15, 2025)

1. **4da1aad** - Latest db scheme
2. **f3ce600** - Add automated comparison gating for Berhad external sales
3. **cfee692** - Fix external sales template header count

### Current Branch: `codex/add-manager-pending-card-for-clarity-tv18os`
**Work in Progress:**
- Adding manager pending card for better visibility
- Improving clarity in the accountant's verification workflow

### Recent Features Added

#### 1. **Automated Comparison Gating (Oct 2025)**
**File:** `views/account/berhad_sales_verification.php`

**What was added:**
- Accountant uploads external sales data once per manager
- System automatically compares ALL outlets for that manager
- Compares both:
  - **Sales**: Berhad Sales (submitted) vs Total Deposit (external)
  - **Expenses**: Berhad Claimed (submitted) vs Total Withdraw (external)
- Visual feedback:
  - ✓ Match (green) - Both sales AND expenses match
  - ✗ Mismatch (red) - Sales or expenses don't match
  - ⚠ Not Found (orange) - Outlet's agent ID not in external data
- **Gating logic**: Save button only enabled if ALL outlets match
- Shows detailed comparison breakdown for each outlet
- Real-time preview as accountant pastes data

**Why this matters:**
- Prevents data entry errors
- Ensures external data matches before accountant can proceed
- Saves time by verifying all outlets at once
- Reduces manual comparison errors

#### 2. **External Sales Template Tables**
- Added visual template tables showing expected data format
- Real-time preview populates as data is pasted
- Helps accountants understand data structure
- Supports multiple delimiters (tab, comma, semicolon)

#### 3. **Manager Cash Management**
- Added `manager_cash_on_hand` table
- Tracks cumulative cash balance
- Updated in `submit_to_hq.php` when batch submitted
- Shows managers their current cash balance

---

## 🐛 KNOWN ISSUES & TODO

### Outstanding Work

1. **MP Perdana Verification Workflow**
   - Files exist but workflow incomplete
   - Need to implement similar comparison logic to Berhad

2. **Market Sales Verification**
   - Currently marked as "Coming Soon"
   - No external system to compare against yet
   - Workflow design pending

3. **Finance Approval Workflow**
   - Finance role defined but dashboard minimal
   - Need finance-specific verification pages
   - Need finance approval action pages

4. **Expense Categorization by Accountant**
   - Database fields exist (`is_categorized`, `categorized_by`)
   - UI not implemented yet
   - Currently expenses go directly to categories chosen by manager

5. **Manager Cash Balance UI**
   - Backend tracking works
   - Need to show cash balance in manager dashboard
   - Need cash history/ledger view

### Current Branch Tasks

**Branch:** `codex/add-manager-pending-card-for-clarity-tv18os`

Tasks likely in scope:
- [ ] Add visual card showing pending count for managers
- [ ] Improve clarity in verify_submission.php display
- [ ] Better visual hierarchy for income stream cards
- [ ] Possibly add filters/search to pending submissions

---

## 🔑 CRITICAL INFORMATION

### Default User Credentials
**From database schema (password: 'password' for all):**

| Username   | Role      | Email                  |
|------------|-----------|------------------------|
| manager    | manager   | manager@mysite.com     |
| accountant | account   | account@mysite.com     |
| ceo        | ceo       | ceo@mysite.com         |
| admin      | admin     | admin@mysite.com       |
| finance    | finance   | finance@mysite.com     |

### Test Data in DB
**Outlets:**
- OUT-A (Outlet A) - Agent: pasar888, MP Coba: suncity, MP Perdana: petronas
- OUT-B (Outlet B) - Agent: senadin8, MP Coba: br1, MP Perdana: br1
- OUT-C (Outlet C) - Agent: pasar81, MP Coba: chrisbr333, MP Perdana: chrisbr2
- OUT-D (Outlet D) - No external IDs configured

**Submissions:**
- SUB-20251014-001-AC63 - Outlet A, pending, RM 1000 Berhad
- SUB-20251014-002-6D71 - Outlet B, pending, RM 2000 Berhad
- Both in batch BATCH-20251014-1-5E84A8

### Important Constants
**From includes/config.php:**
```php
APP_NAME = "BR Management System" // Update if different
CSRF_TOKEN_NAME = "csrf_token"
MAX_FILE_SIZE = 5MB // For receipt uploads
UPLOAD_DIR = "/uploads/receipts/"
```

### Database Connection
**Expected setup (XAMPP):**
```
Host: 127.0.0.1
Database: br
User: root
Password: (empty for XAMPP default)
```

---

## 🎯 UNDERSTANDING THE BUSINESS FLOW

### Real-World Example

**Scenario:** Manager closes Outlet A for October 14, 2025

1. **End of Day - Manager Records:**
   - Berhad Sales: RM 10,000
   - MP Coba Sales: RM 5,000
   - MP Perdana Sales: RM 3,000
   - Market Sales: RM 2,000
   - **Total Income:** RM 20,000

   - Staff Salary: RM 2,000
   - Rent: RM 1,500
   - Utilities: RM 500
   - Berhad Claimed (player withdrawals): RM 8,000
   - **Total Expenses:** RM 12,000

   - **Net Amount:** RM 8,000

2. **Cash Decision - Manager:**
   - Net is RM 8,000
   - Manager decides to send RM 5,000 to HQ
   - Keep RM 3,000 cash on hand
   - Variance reason: "Need petty cash for next week operations"

3. **Verification - Accountant:**
   - Logs into external Berhad portal
   - Exports data showing:
     - Agent: pasar888 (Outlet A)
     - Total Deposit: RM 10,000 ✓ matches submitted sales
     - Total Withdraw: RM 8,000 ✓ matches claimed expenses
   - Pastes into verification page
   - System shows: ✓ Match - All good!
   - Accountant clicks "Save & Verify"

4. **Approval - Finance:**
   - Reviews verified submission
   - Sees all data matches external sources
   - Approves submission
   - Manager gets notification
   - RM 5,000 recorded as receivable from manager

5. **Next Submission:**
   - Manager's cash on hand: RM 3,000
   - Can use this for next submission

### Why This System Exists

**Pain Points Solved:**
1. **Manual Comparison Errors**: Used to manually compare Excel sheets from external portals - error-prone
2. **Cash Tracking**: Managers often keep cash, hard to track who has what
3. **Audit Trail**: Complete history of who approved what and when
4. **Receipt Management**: All receipts digitally stored and linked
5. **Multi-Stream Complexity**: 4 different income streams, each with own external portal
6. **Batch Processing**: Can submit 10 outlets at once instead of one by one

---

## 📞 WHERE WE LEFT OFF & NEXT STEPS

### Current Status (October 15, 2025 - Afternoon)

**✅ Completed:**
- Core manager submission workflow
- Batch submission to HQ
- Cash variance tracking
- Berhad sales verification with automated comparison
- External data parsing and validation
- Real-time comparison feedback
- Gating logic (must match to proceed)
- **✨ NEW: Simplified expense submission (managers enter total only)**
- **✨ NEW: Multiple receipt file uploads**
- **✨ NEW: Expenses verification page for accountants**
- **✨ NEW: Approve/Reject expense functionality**
- **✨ NEW: Receipt preview with image modal**

**🚧 In Progress (Current Branch):**
- Adding manager pending card for visibility
- Improving verify_submission.php clarity

**⏸️ Temporarily Disabled:**
- "Save & Verify" buttons on all sales verification pages (will re-enable later)

**📋 Next Up:**
- Re-enable Save & Verify buttons with proper workflow integration
- Complete MP Perdana verification workflow
- Build out Finance approval pages
- Add manager cash balance display
- Add expense categorization UI for accountant (beyond approve/reject)
- Market sales verification (pending external system)
- Test and refine new expense review workflow

### When You Return to This Project

**Quick Start:**
1. Check current branch: `git branch` (should be codex/add-manager-pending-card-for-clarity-tv18os)
2. Database: Import `database/br (4).sql` if needed
3. Test users: manager/accountant/finance (password: "password")
4. Start with: http://localhost/my_site/
5. Test workflow:
   - Login as manager → submit_expenses.php → Create submission
   - submit_to_hq.php → Batch submit
   - Logout → Login as accountant
   - verify_submission.php → See pending submissions
   - Click Berhad Sales card → Upload external data → Verify

**Key Files to Check:**
- `views/account/berhad_sales_verification.php` - Main verification logic
- `includes/account/save_berhad_external_sales_batch.php` - Backend comparison
- `database/br (4).sql` - Latest schema
- Git history: `git log --oneline` to see recent work

### Questions to Ask When Resuming

1. Is the manager pending card feature complete?
2. Has MP Perdana verification been implemented?
3. What's the status of the Finance approval workflow?
4. Are there new test cases or edge cases discovered?
5. Any user feedback on the comparison feature?

---

## 🎓 TECHNICAL NOTES

### Technology Stack
- **Backend:** PHP 8.2+ (procedural, no framework)
- **Database:** MariaDB 10.4
- **Frontend:** Vanilla JavaScript, no framework
- **Server:** Apache (XAMPP on Windows)
- **CSS:** Inline styles (no external framework)

### Security Features
- CSRF protection on all forms
- Argon2id password hashing
- Role-based access control (requireRole function)
- File upload validation
- SQL injection protection (prepared statements)
- XSS protection (htmlspecialchars everywhere)

### Database Helper Functions
**Located in:** `includes/db.php`
```php
dbFetchAll($sql, $params) // Fetch multiple rows
dbFetchOne($sql, $params) // Fetch single row
dbExecute($sql, $params)  // Execute query
dbLastInsertId()          // Get last insert ID
```

### Code Conventions
- No external dependencies (everything self-contained)
- Inline JavaScript (no separate JS files yet)
- Security functions in includes/auth.php and includes/csrf.php
- All database queries use prepared statements
- Date format: Y-m-d (2025-10-15)
- Currency: Malaysian Ringgit (RM)
- File uploads: /uploads/receipts/

---

## 📚 USEFUL QUERIES

### Check Pending Submissions
```sql
SELECT ds.submission_code, u.name as manager, o.outlet_name, ds.status, ds.created_at
FROM daily_submissions ds
JOIN users u ON ds.manager_id = u.id
JOIN outlets o ON ds.outlet_id = o.id
WHERE ds.status = 'pending'
ORDER BY ds.created_at DESC;
```

### Manager Cash Balances
```sql
SELECT u.name as manager, mch.current_balance, mch.last_updated
FROM manager_cash_on_hand mch
JOIN users u ON mch.manager_id = u.id
ORDER BY mch.current_balance DESC;
```

### Submissions Needing Verification
```sql
SELECT COUNT(*) as pending_count,
       SUM(berhad_sales) as total_berhad,
       SUM(total_income) as total_income
FROM daily_submissions
WHERE status = 'pending';
```

### External Data Match Status
```sql
SELECT ds.submission_code, o.outlet_name,
       ds.berhad_sales as submitted_sales,
       besd.total_deposit as external_sales,
       (ds.berhad_sales - besd.total_deposit) as difference
FROM daily_submissions ds
JOIN outlets o ON ds.outlet_id = o.id
LEFT JOIN berhad_external_sales_data besd ON ds.id = besd.submission_id
WHERE ds.status = 'pending'
  AND ds.berhad_sales > 0;
```

---

## 🔗 RELATED DOCUMENTATION

**External Systems Referenced:**
- Berhad Sales Portal (agent-based)
- MP Coba Portal (login-based)
- MP Perdana Portal (login-based)

**GitHub Repository:** https://github.com/renogerry55-ui/my_site

**Latest Schema:** `database/br (4).sql`

---

## 💡 TIPS FOR FUTURE DEVELOPMENT

1. **When Adding New Income Stream Verification:**
   - Copy `berhad_sales_verification.php` as template
   - Create corresponding external_sales_data table
   - Update verification workflow in verify_submission.php
   - Add save handler in includes/account/

2. **When Modifying Comparison Logic:**
   - Main logic in JavaScript within berhad_sales_verification.php (line ~850-1014)
   - Backend validation in save_berhad_external_sales_batch.php
   - Test with mismatched data first
   - Consider tolerance for rounding (currently 0.01)

3. **When Adding New Status:**
   - Update daily_submissions.status enum
   - Update status checks in all verification pages
   - Update workflow display in view_history.php
   - Document new status in this diary

4. **Testing Workflow:**
   - Create test manager submissions
   - Use test data from database
   - Agent IDs: pasar888, senadin8, pasar81
   - Test both matching and mismatching scenarios

---

## 📝 DEVELOPMENT DIARY ENTRIES

### Entry 1 - October 15, 2025 (Morning)
**By:** Claude Code
**Status:** Initial project documentation

Created comprehensive project diary after analyzing:
- Database schema (br (4).sql)
- Recent commits and git history
- Key workflow files
- User roles and responsibilities
- Business logic and external system integration

**Key Findings:**
- System is well-structured with clear separation of concerns
- Recent work focused on automated comparison gating
- Most critical workflow (Berhad verification) is complete
- MP Perdana and Market verification still need implementation
- Finance workflow needs attention

**Next Developer Notes:**
- Start with completing current branch work (manager pending card)
- Then focus on MP Perdana verification (copy Berhad pattern)
- Consider refactoring comparison logic into reusable function
- Finance dashboard needs design and implementation

---

### Entry 2 - October 15, 2025 (Afternoon)
**By:** Claude Code
**Status:** Major workflow change - Simplified expense submission & accountant review

**MAJOR CHANGES: Expense Workflow Redesign**

Implemented a complete overhaul of the expense submission and verification workflow based on user requirements.

#### Change Summary

**Manager Side:**
- ❌ Removed: Category selection for expenses
- ❌ Removed: MP/BERHAD vs Market expense type selection
- ✅ Added: Single total expenses amount input
- ✅ Added: Multiple file upload support for receipts (PDFs and images)
- **Result:** Managers now simply enter total expense amount and upload receipts. Accountant will categorize later.

**Accountant Side:**
- ✅ Added: "Expenses Review" card in verify_submission.php
- ✅ Added: New expenses_verification.php page for reviewing uncategorized expenses
- ✅ Added: Receipt preview functionality (images display inline, PDFs show icon)
- ✅ Added: Modal for enlarging receipt images
- ✅ Added: Approve button (marks expenses as approved)
- ✅ Added: Request Resubmit button (rejects expenses and sends back to manager)
- ⏸️ Temporarily disabled: "Save & Verify" buttons on all sales verification pages

#### Files Modified

**1. views/manager/submit_expenses.php**
- Removed complex expense categorization form (MP/Berhad, Market categories)
- Replaced with simple total expenses input field
- Added multiple file upload for receipts: `<input type="file" name="expense_receipts[]" multiple>`
- Updated JavaScript validation to use single expense input
- Updated `calculateTotals()` function

**2. includes/submission_handler.php**
- Modified to process `total_expenses_amount` from POST
- Handles multiple receipt files via `expense_receipts[]` array
- Creates single expense entry with "Uncategorized" category (ID 20)
- Stores all receipt filenames as JSON array in `receipt_file` column
- Added loop to handle multiple file uploads with validation

**3. views/account/verify_submission.php**
- Added new "Expenses Review" card (orange-themed) to manager income cards
- Links to `expenses_verification.php?manager_id={id}`
- Shows total expenses amount and submission count
- Card appears alongside other income stream cards

**4. views/account/expenses_verification.php** ✨ NEW FILE
- Complete new page for accountant to review uncategorized expenses
- Features:
  - Lists all pending submissions with uncategorized expenses by manager
  - Parses JSON receipt files and displays them
  - Image receipts: Inline preview with modal enlargement
  - PDF receipts: Shows PDF icon with "Open in new tab" link
  - Shows expense amount submitted by manager
  - "Approve" button per submission
  - "Request Resubmit" button with rejection reason input
  - Styled consistently with other account pages

**5. includes/account/approve_expenses.php** ✨ NEW FILE
- Backend API endpoint for approving expenses
- Updates expenses with `UPPER(category_name) = 'UNCATEGORIZED'`
- Sets `approval_status = 'approved'`
- Records accountant ID and timestamp
- Returns JSON response

**6. includes/account/reject_expenses.php** ✨ NEW FILE
- Backend API endpoint for rejecting expenses
- Validates rejection reason is provided
- Updates expenses to `approval_status = 'rejected'`
- Changes submission status to `'resubmit'`
- Adds accountant notes with timestamp and reason
- Updates `returned_to_manager_at` timestamp
- Uses database transaction for data integrity
- Returns JSON response

**7. views/account/berhad_sales_verification.php**
- Commented out "Save & Verify" button (lines 685-686)
- Commented out save button event listener (lines 1081-1125)
- Commented out `saveBtn.disabled` state update (line 1012)
- Added comments explaining temporary disablement

**8. views/account/mp_coba_sales_verification.php**
- Commented out "Save & Verify" button (lines 681-682)
- Commented out save button event listener (lines 1107-1148)
- Commented out `saveBtn.disabled` state update (line 1037)

**9. views/account/mp_perdana_sales_verification.php**
- Commented out "Save & Verify" button (lines 668-669)
- Commented out save button event listener (lines 1059-1100)
- Commented out `saveBtn.disabled` state update (line 989)

#### Technical Implementation Details

**JSON Receipt Storage:**
```php
// In submission_handler.php
$uploadedReceipts = ['receipt1.jpg', 'receipt2.pdf', 'receipt3.png'];
$receiptFilesJson = json_encode($uploadedReceipts);
// Stored in expenses.receipt_file column

// In expenses_verification.php
$receiptFiles = json_decode($expense['receipt_file'], true) ?? [];
// Parsed back to array for display
```

**File Upload Handling:**
```php
// Multiple file upload loop
for ($i = 0; $i < $fileCount; $i++) {
    $uploadResult = handleReceiptUpload(
        $filesData['expense_receipts']['name'][$i],
        $filesData['expense_receipts']['tmp_name'][$i],
        $filesData['expense_receipts']['size'][$i],
        $filesData['expense_receipts']['error'][$i],
        $submissionCode
    );
    $uploadedReceipts[] = $uploadResult['filename'];
}
```

**Uncategorized Expense Query:**
```sql
UPDATE expenses e
INNER JOIN expense_categories ec ON e.expense_category_id = ec.id
SET e.approval_status = 'approved',
    e.approved_by = :account_id,
    e.approved_at = NOW()
WHERE e.submission_id = :submission_id
  AND UPPER(ec.category_name) = 'UNCATEGORIZED'
```

#### User Impact

**Manager Benefits:**
- ✅ Faster submission process (no category selection needed)
- ✅ Simpler form with less fields
- ✅ Can upload multiple receipts at once
- ✅ Less room for categorization errors

**Accountant Benefits:**
- ✅ Dedicated page for expense review
- ✅ Visual receipt preview (no need to download)
- ✅ Can approve/reject with clear feedback
- ✅ Rejection reason captured for audit trail
- ✅ All manager's expenses in one view

#### Workflow Changes

**OLD FLOW:**
```
Manager submits → Selects category → Accountant verifies sales only
```

**NEW FLOW:**
```
Manager submits → No category selected → Accountant:
  1. Verifies sales (existing workflow)
  2. Reviews expenses → Preview receipts → Approve or Reject
  3. If rejected → Submission goes back to manager (status: resubmit)
```

#### Database Status Flow

```
Expense Approval:
  pending → approved (via approve_expenses.php)
  pending → rejected (via reject_expenses.php)

Submission Status:
  pending → resubmit (when expenses rejected)
  - Updates returned_to_manager_at timestamp
  - Adds accountant_notes with rejection details
```

#### Why "Save & Verify" Disabled

Per user request: The "Save & Verify" button on sales verification pages has been temporarily disabled. This will be re-enabled in a future update once the complete verification workflow is finalized. The comparison functionality still works - accountants can compare data, but cannot save/verify at this time.

**Files Affected:**
- berhad_sales_verification.php
- mp_coba_sales_verification.php
- mp_perdana_sales_verification.php

All buttons and event listeners are commented out (not deleted) for easy re-enablement.

#### Testing Notes

To test the new workflow:

1. **Manager Side:**
   ```
   Login: manager / password
   Go to: submit_expenses.php
   - Enter outlet, date, income streams
   - Enter total expenses amount (e.g., RM 1500)
   - Upload multiple receipts (jpg/png/pdf)
   - Submit
   - Go to submit_to_hq.php and batch submit
   ```

2. **Accountant Side:**
   ```
   Login: accountant / password
   Go to: verify_submission.php
   - Look for "Expenses Review" card (orange)
   - Click to go to expenses_verification.php
   - View receipts (images preview, PDFs show icon)
   - Click Approve or Request Resubmit
   ```

3. **Expected Results:**
   - Approve: Expense status → approved
   - Reject: Submission status → resubmit, manager notified via accountant_notes

#### Known Considerations

1. **Receipt File Types:** Currently supports JPG, PNG, PDF (validated in handleReceiptUpload)
2. **File Size Limit:** 5MB per file (defined in config)
3. **Storage:** All receipts stored in `/uploads/receipts/`
4. **JSON Storage:** Using JSON to store multiple filenames in single database column
5. **Category ID:** Hardcoded to look for "UNCATEGORIZED" (should be ID 20 per schema)

#### Future Enhancements

Potential improvements for next iteration:
- [ ] Bulk approve/reject for multiple submissions
- [ ] Add expense categorization UI for accountant
- [ ] Filter expenses by date range
- [ ] Search functionality for receipts
- [ ] Thumbnail generation for large images
- [ ] PDF preview inline (using PDF.js or similar)
- [ ] Re-enable Save & Verify with proper workflow integration
- [ ] Add notifications to manager when expenses rejected

#### Files Summary

**Created:**
- `views/account/expenses_verification.php`
- `includes/account/approve_expenses.php`
- `includes/account/reject_expenses.php`

**Modified:**
- `views/manager/submit_expenses.php`
- `includes/submission_handler.php`
- `views/account/verify_submission.php`
- `views/account/berhad_sales_verification.php`
- `views/account/mp_coba_sales_verification.php`
- `views/account/mp_perdana_sales_verification.php`

**Git Status:**
```
Modified files waiting to be committed:
- views/account/berhad_sales_verification.php
- views/account/berhad_sales_verification_process.php
- views/account/verify_submission.php

New untracked files:
- views/account/expenses_verification.php
- includes/account/approve_expenses.php
- includes/account/reject_expenses.php
```

---

*End of Project Diary*

---

**Remember:** This document should be updated with each significant change or milestone. Keep it as a living document that future developers (including yourself) can reference to quickly understand where things stand.

**Last Commit Before This:** 4da1aad - latest db scheme
**Database Version:** br (4).sql (October 15, 2025 04:58 AM)