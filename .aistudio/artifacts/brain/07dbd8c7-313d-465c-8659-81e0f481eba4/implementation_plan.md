# Redesign Admin PIC Accounts & Session Assignment Interface (`/admin/?tab=pic_accounts`)

Completely redesigning the **Penugasan PIC & Akun Lapangan** admin view (`/admin/?tab=pic_accounts`) into a modern, high-craftsmanship split-panel layout with bulk session assignment, clean status badges, and compact PIC operator cards.

---

## User Review & Critical Decisions

> [!IMPORTANT]
> Based on your confirmed choices in the clarification step, the new interface will feature a **Dual Split-Panel Layout** (Session List on the Left, PIC Cards & Batch Assignment on the Right), **Bulk Multi-Select Assignment** (selecting multiple court sessions at once and assigning them to a chosen PIC operator in 1 click), and **Compact PIC Account Cards** with avatar badges, active session counts, and quick credentials access.

- **Confirmed Decision 1 (Split-Panel Architecture)**: Left side displays a filterable list of all court mabar sessions with selection checkboxes and status badges; Right side displays compact PIC account cards and the Bulk Assignment Action Bar.
- **Confirmed Decision 2 (Bulk Session Assignment Workflow)**: Check multiple sessions on the left, pick the target PIC from a quick dropdown on the right/bottom action bar, and assign all selected sessions in a single click.
- **Confirmed Decision 3 (Compact PIC Account Styling)**: Modern cards with avatar initials, role badge, phone/WhatsApp direct link, assigned session badges, 1-Click Demo Generator, and quick edit/reset actions.

---

## 1. Overview & Core Concept

- **What It Does**: Streamlines how community super-admins manage field PIC operators and assign court sessions across branches. Eliminates messy table lists and individual form saves by introducing batch selection, real-time search, and a clear visual breakdown of unassigned vs assigned sessions.
- **Target Audience**: Community Super-Admins managing multiple GOR branches, session hosts, and PIC accounts.
- **Key Value**: Reduces PIC assignment time from minutes to seconds, prevents unassigned session errors with prominent status alerts, and provides a polished, modern dashboard experience.

---

## 2. User Experience & Visual Design

- **Split-Panel Composition (Desktop 1440px / Mobile Stacking)**:
  - **Header & Metric Bar**: Top summary bar displaying 4 KPI metrics (*Total PIC Accounts*, *Total Sessions*, *Assigned Sessions*, *Unassigned Sessions Needing PIC*).
  - **Left Panel (Daftar Sesi Mabar & Bulk Selector)**:
    - Multi-select check-all toggle & status filter tabs (*Semua Sesi*, *Perlu PIC*, *Sudah Ada PIC*, *Sesi Aktif*).
    - Session list cards featuring day badge, calendar date, venue name, branch area, court count, time range, and current assigned PIC chip.
    - Checkbox selection on each session card for instant bulk action.
  - **Right Panel (Manajemen Akun PIC & Toolbar)**:
    - **Bulk Assign Action Bar**: Appears prominently when 1 or more sessions are checked, allowing 1-click assignment to any PIC account.
    - **1-Click Demo PIC Generator**: Prominent emerald button to instantly generate a demo PIC account linked to open sessions.
    - **Compact PIC Cards**: Clean cards with avatar badges, contact links, assigned session chips, and quick action options (Edit, Assign, Delete).
- **Color Palette & Visual Tokens**:
  - Dark Slate `#0b1e36` for brand containers, Amber `#f59e0b` for highlight badges, Emerald `#10b981` for active assignments, and Slate `#f8fafc` for clean background fields.

---

## 3. Key Product Decisions & Trade-Offs

- **Decision 1: Bulk Multi-Select Session Assignment vs Single Dropdown per Row**
  - *Chosen Approach*: Multi-select checkboxes on session cards + bulk assignment panel + inline quick dropdowns.
  - *Why*: Allows admins to assign 4–8 sessions for an entire weekend to a single PIC host in just 2 clicks, while still allowing 1-click single-session updates.
- **Decision 2: Split-Panel View vs Full Page Navigation**
  - *Chosen Approach*: Responsive split-panel layout on desktop (`grid-cols-12`: 7 cols session list, 5 cols PIC accounts) that cleanly stacks on mobile screens.
  - *Why*: Gives admins full context of available PICs while scanning through the schedule without having to navigate between tabs or pages.

---

## 4. Technical Architecture & Data Strategy

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                 /admin/?tab=pic_accounts (Split Panel UI)                   │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
        ┌──────────────────────────────┴──────────────────────────────┐
        ▼                                                             ▼
┌──────────────────────────────────────────┐    ┌─────────────────────────────┐
│ LEFT PANEL (col-span-7)                  │    │ RIGHT PANEL (col-span-5)    │
│ - Metric Bar (Assigned vs Unassigned)    │    │ - Bulk Assign Action Bar    │
│ - Session Search & Status Filter         │    │ - 1-Click Demo PIC Generator│
│ - Multi-Select Checkboxes                │    │ - Compact PIC Account Cards │
│ - Interactive Session Cards              │    │ - Quick Credentials Modal   │
└────────────────────┬─────────────────────┘    └──────────────┬──────────────┘
                     │                                         │
                     └────────────────────┬────────────────────┘
                                          ▼
                      ┌──────────────────────────────────────┐
                      │ PHP Backend Action Handler           │
                      │ - form_action: assign_pic_session    │
                      │ - form_action: bulk_assign_pic       │
                      │ - form_action: create_pic_account    │
                      └──────────────────────────────────────┘
```

- **Database & Handler Operations**:
  - Support `bulk_assign_pic` action in POST request handling to process array of `session_ids[]` and assign target `pic_id` in a single SQL transaction.
  - Support single `assign_pic_session` inline update for fast quick edits.

---

## 5. Execution Steps

1. **Backend POST Handler Update (`/admin/index.php`)**:
   - Add handler for `form_action === 'bulk_assign_pic'` to update `pic_id`, `pic_name`, and `pic_whatsapp` for multiple selected session IDs.

2. **Redesign `tab === 'pic_accounts'` View (`/admin/index.php`)**:
   - Replace old cluttered UI with the new **Dual Split-Panel Architecture**.
   - Build 4 compact KPI metric cards at the top.
   - Build Left Panel: Multi-select session list cards with clear unassigned/assigned visual badges.
   - Build Right Panel: Sticky Bulk Assignment bar, 1-Click Demo PIC button, and Compact PIC Account cards with avatar initials.
   - Implement client-side JavaScript for Check-All selection, dynamic selected count badge, and seamless sub-tab switching.

3. **Verification & Testing**:
   - Verify PHP syntax with `php -l admin/index.php`.
   - Execute `compile_applet` and test HTTP status 200 via dev server.
