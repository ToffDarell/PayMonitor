# Central Support Management System

## What It Does

The Central Support Management System allows super admins to view, manage, and respond to all support requests submitted by tenants across the entire platform.

### Key Features:

1. **View All Support Requests** - See every support ticket from all tenants in one place
2. **Filter & Search** - Filter by status, category, or search by tenant/subject/email
3. **Status Management** - Update ticket status (Open → In Progress → Resolved)
4. **Send Responses** - Reply to tickets directly from the dashboard
5. **Email Notifications** - Automatically email responses to tenants
6. **Conversation History** - View full conversation thread for each ticket
7. **Statistics Dashboard** - See total, open, in-progress, and resolved ticket counts
8. **Detailed View** - View full ticket details including tenant info, requester, message, and category

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
   - Send responses to tenants
   - Email responses automatically
   - View conversation history
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

### Step 3: View Ticket Details & Send Response
1. In the support requests table, click "View Details" on any ticket
2. You should see:
   - Full subject and message
   - Tenant information (name, ID)
   - Requester information (name, email)
   - Category badge
   - Current status
   - Submission date/time
   - Response form at the bottom

### Step 4: Send a Response
1. On the ticket details page, scroll to "Send Response" section
2. Type your response in the textarea (e.g., "Thank you for contacting us. We're looking into this issue.")
3. Make sure "Send email notification" checkbox is checked
4. Click "Send Response" button
5. You should see success message
6. The response will appear in "Conversation History" section
7. An email will be sent to the tenant's requester email
8. Status will automatically change from "Open" to "In Progress"

### Step 5: Check Email (as Tenant)
1. Check the tenant's email inbox (requester_email)
2. You should receive an email with:
   - Subject: "Re: [Original Subject]"
   - Original ticket summary
   - Your response message
   - Ticket details (ID, status, category)
   - Link to view support dashboard

### Step 6: Send Multiple Responses
1. Go back to the ticket detail page
2. Send another response
3. Both responses will appear in "Conversation History"
4. Each response shows:
   - Responder name and email
   - Timestamp
   - "Emailed" badge if sent via email
   - Full message content

### Step 7: Update Ticket Status
1. On the ticket details page, look at the right sidebar
2. Use the "Update Status" dropdown
3. Change status from "In Progress" to "Resolved"
4. Click "Update Status" button
5. You should see success message
6. Status badge should update
7. "Resolved" timestamp will appear

### Step 8: Test Filters
1. On support index page, try filtering:
   - Status: Select "Open" - should only show open tickets
   - Category: Select "Technical" - should only show technical tickets
   - Search: Type tenant name - should filter results
2. Click "Clear" to reset filters

## URLs

- **Central Support Dashboard**: `http://paymonitor.test/central/support`
- **Tenant Support Form**: `http://{tenant}.paymonitor.test/settings?tab=support`

## Database

### support_requests table (central database):
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

### support_responses table (central database):
- `support_request_id` - Which ticket this response belongs to
- `responder_name` - Admin who responded
- `responder_email` - Admin's email
- `message` - Response message
- `sent_via_email` - Whether email was sent
- `created_at` - When response was sent

## What's Next (Optional Future Enhancements)

1. ~~**Reply System**~~ ✅ DONE - Allow admins to reply to tickets
2. **Assignment** - Assign tickets to specific support staff
3. **Priority Levels** - Add urgent/high/medium/low priority
4. **Internal Notes** - Add private notes visible only to admins
5. **Email Templates** - Auto-reply when status changes
6. **SLA Tracking** - Track response time and resolution time
7. **Attachments** - Allow file uploads with tickets
8. **Tenant Reply** - Allow tenants to reply to responses

## Files Created/Modified

### New Files:
- `app/Http/Controllers/Central/SupportController.php` - Controller for support management
- `app/Models/SupportResponse.php` - Model for support responses
- `app/Mail/SupportResponseMail.php` - Email notification for responses
- `resources/views/central/support/index.blade.php` - Support requests list view
- `resources/views/central/support/show.blade.php` - Individual ticket detail view with response form
- `resources/views/emails/support-response.blade.php` - Email template for responses
- `database/migrations/2026_04_21_135621_create_support_responses_table.php` - Migration for responses

### Modified Files:
- `routes/web.php` - Added support routes including response route
- `resources/views/layouts/central.blade.php` - Added Support navigation link
- `config/app.php` - Added support_email configuration
- `app/Http/Controllers/Tenant/SettingsController.php` - Updated to use SUPPORT_EMAIL
- `app/Models/SupportRequest.php` - Added responses relationship

## Configuration

Make sure your `.env` has:
```
SUPPORT_EMAIL=support@paymonitor.com
SUPPORT_PHONE=+63 917 000 0000
SUPPORT_HOURS=Mon-Fri, 8:00 AM - 5:00 PM
```

## Email Configuration

Responses are sent using the same mail configuration as support requests:
- **From**: `MAIL_FROM_ADDRESS` (no-reply@paymonitor.com)
- **To**: Tenant's requester email
- **Subject**: "Re: [Original Subject]"
- **Content**: Professional HTML email with original request summary and response
