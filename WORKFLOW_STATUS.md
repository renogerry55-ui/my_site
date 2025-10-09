# Daily Closing Workflow - Status Flow

## Overview
This document explains the complete workflow for daily outlet closing submissions.

## Status Flow

```
DRAFT → PENDING → VERIFIED/REJECTED
```

### 1. **DRAFT Status**
- **Meaning:** "Pending to Send to HQ"
- **When:** Manager completes outlet closing for individual outlets
- **What Happens:**
  - Each outlet closing is saved with status = 'draft'
  - Submission appears in "Submit to HQ" page for batch submission
  - Manager can submit multiple outlet closings throughout the day

### 2. **PENDING Status**
- **Meaning:** "Pending Account Approval"
- **When:** Manager submits all draft closings to HQ via "Submit to HQ" page
- **What Happens:**
  - All draft closings for the selected date are batch-submitted
  - All submissions get the same batch_code
  - Status changes from 'draft' to 'pending'
  - submitted_to_hq_at timestamp is recorded
  - Manager's name is associated with the batch via manager_id

### 3. **VERIFIED Status**
- **Meaning:** Account has approved the submissions
- **When:** Account/HQ reviews and approves the submissions
- **What Happens:**
  - Status changes to 'verified'
  - Submissions are officially recorded

### 4. **REJECTED Status**
- **Meaning:** Account has rejected the submissions
- **When:** Account/HQ finds issues and rejects
- **What Happens:**
  - Status changes to 'rejected'
  - Rejection reason is recorded
  - Manager may need to revise and resubmit

## Manager Workflow

1. **Throughout the day:**
   - Submit closing for Outlet A → Status: DRAFT
   - Submit closing for Outlet B → Status: DRAFT
   - Submit closing for Outlet C → Status: DRAFT

2. **End of day:**
   - Go to "Submit to HQ" page
   - Review all draft closings (A, B, C)
   - See totals: Total Sales, Total Expenses, Net Amount
   - Click "Submit All Closings to HQ"
   - All closings (A, B, C) → Status: PENDING
   - All get same batch code (e.g., BATCH-20251009-123-ABC123)

3. **Wait for Account:**
   - Account reviews all submissions in the batch
   - Account either approves → VERIFIED or rejects → REJECTED

## Database Schema

### daily_submissions table
Key columns for workflow:
- `status` ENUM('draft', 'submitted', 'pending', 'verified', 'rejected', 'revised')
- `batch_code` VARCHAR(50) - Groups submissions sent together
- `submitted_to_hq_at` DATETIME - When batch was submitted to HQ
- `manager_id` - Which manager submitted the batch

## Files Modified

1. **views/manager/submit_to_hq.php** (line 44)
   - Changed: `status = 'submitted'` → `status = 'pending'`
   - Updated UI labels to match terminology

2. **views/manager/view_history.php** (line 334-336)
   - Updated status labels:
     - 'draft' → "Pending to Send to HQ"
     - 'pending' → "Pending Account Approval"

## Key Features

✅ Manager can do individual outlet closings throughout the day (all save as DRAFT)
✅ "Submit to HQ" page shows all pending closings for review
✅ System calculates total sales and expenses across all outlets
✅ Batch submission sends all closings at once under manager's name
✅ After submission, all closings are "Pending Account Approval"
✅ Clear status labels throughout the system

## Notes

- The 'submitted' status exists in the database but is no longer used in the workflow
- We skip directly from 'draft' to 'pending' after batch submission
- All submissions in a batch get the same batch_code for tracking
- Manager can only submit drafts; cannot modify pending/verified submissions
