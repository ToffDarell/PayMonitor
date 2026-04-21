# Central Support Management System

## What It Does

The Central Support Management System allows super admins to view, manage, and respond to all support requests submitted by tenants across the entire platform.

### Key Features:

1. **View All Support Requests** - See every support ticket from all tenants in one place
2. **Filter & Search** - Filter by status, category, or search by tenant/subject/email
3. **Status Management** - Update ticket status (Open → In Progress → Resolved)
4. **Statistics Dashboard** - See total, open, in-progress, and resolved ticket counts
5. **Detailed View** - View full ticket details including tenant info, requester, message, and category

## How It Works

### Tenant Side:
1. Tenant submits support request from Settings → Support tab
2. Request is saved to central database (`support_requests` table)
3. Email notification sent to `support@paymonitor.com`

### Central Admin Side:
1. Admin receives email notification
2. Admin logs into central dashboard
3. Admin clicks "Support" in sidebar (shows badge with open ticket count)
4. Admin can:
   - View all tickets in a table
   - Filter by status/category
   - Search by tenant name, subject, or email
   - Click "View Details" to see full ticket
   - Update status from Open → In Progress → Resolved

## How to Test

### Step 1: Submit a Test Ticket (as Tenant)
1. Go to any tenant workspace (e.g., `http://tenant1.paymonitor.test`)
2. Login as tenant user
3. Go to Settings → Support tab
4. Fill out the form:
   - Subject: "Test support request"
   - Category: "Technical"
   - Message: "This is a test ticket"
5. Click "Submit Request"
6. You should see success message

### Step 2: View Ticket (as Central Admin)
1. Go to central domain: `http://paymonitor.test/login`
2. Login as super admin
3. Look at sidebar - you should see "Support" with a badge showing "1" (or more)
4. Click "Support" in the sidebar
5. You should see:
   - Statistics cards showing: Total, Open, In Progress, Resolved counts
   - Filter form (search, status, category)
   - Table with all support requests

### Step 3: View Ticket Details
1. In the support requests table, click "View Details" on any ticket
2. You should see:
   - Full subject and message
   - Tenant information (name, ID)
   - Requester information (name, email)
   - Category badge
   - Current status
   - Submission date/time

### Step 4: Update Ticket Status
1. On the ticket details page, look at the right sidebar
2. Use the "Update Status" dropdown
3. Change status from "Open" to "In Progress"
4. Click "Update Status" button
5. You should see success message
6. Status badge should update
7. Go back to support list - the ticket should now show "In Progress"

### Step 5: Resolve Ticket
1. Open the ticket again
2. Change status to "Resolved"
3. Click "Update Status"
4. You should see "Resolved" timestamp appear
5. Go back to support list - resolved count should increase

### Step 6: Test Filters
1. On support index page, try filtering:
   - Status: Select "Open" - should only show open tickets
   - Category: Select "Technical" - should only show technical tickets
   - Search: Type tenant name - should filter results
2. Click "Clear" to reset filters

## URLs

- **Central Support Dashboard**: `http://paymonitor.test/central/support`
- **Tenant Support Form**: `http://{tenant}.paymonitor.test/settings?tab=support`

## Database

All support requests are stored in the central database in the `support_requests` table with these fields:
- `tenant_id` - Which tenant submitted it
- `tenant_name` - Tenant cooperative name
- `requester_name` - Person who submitted
- `requester_email` - Their email
- `category` - general, technical, billing, account, feature
- `subject` - Ticket subject
- `message` - Full message
- `status` - open, in_progress, resolved
- `resolved_at` - Timestamp when resolved
- `created_at` - When submitted

## What's Next (Optional Future Enhancements)

1. **Reply System** - Allow admins to reply to tickets via email
2. **Assignment** - Assign tickets to specific support staff
3. **Priority Levels** - Add urgent/high/medium/low priority
4. **Internal Notes** - Add private notes visible only to admins
5. **Email Templates** - Auto-reply when status changes
6. **SLA Tracking** - Track response time and resolution time
7. **Attachments** - Allow file uploads with tickets

## Files Created/Modified

### New Files:
- `app/Http/Controllers/Central/SupportController.php` - Controller for support management
- `resources/views/central/support/index.blade.php` - Support requests list view
- `resources/views/central/support/show.blade.php` - Individual ticket detail view

### Modified Files:
- `routes/web.php` - Added support routes
- `resources/views/layouts/central.blade.php` - Added Support navigation link
- `config/app.php` - Added support_email configuration
- `app/Http/Controllers/Tenant/SettingsController.php` - Updated to use SUPPORT_EMAIL

## Configuration

Make sure your `.env` has:
```
SUPPORT_EMAIL=support@paymonitor.com
SUPPORT_PHONE=+63 917 000 0000
SUPPORT_HOURS=Mon-Fri, 8:00 AM - 5:00 PM
```
