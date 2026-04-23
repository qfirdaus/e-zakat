# e-Base a

e-Base is a web-based internal platform used as a secure and reusable foundation for enterprise applications, administration portals, and operational systems. It provides a structured starting point for building organization-specific solutions with consistent authentication, access control, system configuration, auditability, multilingual support, and modular feature management.

The current version has evolved beyond a simple starter template. It now operates as a practical administration platform with production-ready modules for user governance, group and access management, system configuration, reference content, documentation, and controlled access to external data domains.

## Versioning

- Current version: `1.7.2`
- Release history: [CHANGELOG.md](./CHANGELOG.md)
- Version source of truth: [VERSION](./VERSION)
  Runtime note: Docker mounts this file into the container so the application version label reads from the same source.

## Runtime Baseline

- PHP baseline: `8.3.30`
- Docker PHP image: `php:8.3.30-apache`
- MySQL baseline: `8.0.41`

Current support position:

- required team/server baseline now: `PHP 8.3.30`
- required application database baseline now: `MySQL 8.0.41`
- next planned runtime target under review: `PHP 8.4.x`

Recommended release flow:

1. Update [VERSION](./VERSION) from `*-dev` to the final release version, for example `1.6.0`.
2. Update the fallback value in [public/configuration/settings.php](./public/configuration/settings.php) only if you intentionally want a non-file fallback for non-Docker/public-only runtime paths.
3. Move the matching items from `Unreleased` in [CHANGELOG.md](./CHANGELOG.md) into a dated release section such as `## [1.6.0] - 2026-04-07`.
4. Create a git tag such as `v1.6.0`.
5. Start the next cycle by updating [VERSION](./VERSION) to the next development version, for example `1.6.1-dev` or `1.7.0-dev`.

Manual command format for release support:

- `release X.Y.Z`
  Example: `release 1.6.0`
  Purpose: finalize the release version, update [VERSION](./VERSION), and move `Unreleased` items into a dated release section in [CHANGELOG.md](./CHANGELOG.md).

- `dev X.Y.Z-dev`
  Example: `dev 1.6.1-dev`
  Purpose: start the next development cycle by updating [VERSION](./VERSION) and keeping [CHANGELOG.md](./CHANGELOG.md) ready under `Unreleased`.

- `release X.Y.Z then dev A.B.C-dev`
  Example: `release 1.6.0 then dev 1.6.1-dev`
  Purpose: finalize a release and immediately bump the project back to the next development version.

## System Overview

e-Base is designed to support internal institutional workflows where:

- user access must be controlled centrally
- modules and menus are assigned by group
- administrative actions must be traceable
- runtime settings must be configurable without unsafe code edits
- the system must remain deployable across local, Docker, and server environments

At a high level, the platform combines:

- a MySQL application database for users, groups, settings, audit data, and application metadata
- Sybase domain connectivity for staff and student data integration
- a public web root structure suitable for safer deployment
- configurable branding, language, theme, and operational runtime settings

## Main Features

### Authentication and Session Management

- **Login Portal**  
  Provides a branded login experience with multilingual support, system information, and secure session handling.

- **Hybrid Authentication Flow**  
  Supports standard internal login using user credentials, while also keeping room for SSO-driven authentication flows where user identity may already be trusted upstream.

- **Policy-Driven SSO Auto Provisioning**  
  Supports first-time Staff and Student access through SSO without requiring every account to be pre-created manually in `tbl_m_user`. When enabled through Login Policy, the platform can prepare a full application user record automatically using the same identity mapping approach as synchronized source data together with configurable default group assignment.

- **Controlled Manual Login Readiness**  
  Keeps manual login stricter than SSO auto provisioning. Staff and Student identities that do not yet exist in `tbl_m_user` must complete first access through SSO when policy requires it, while Public users always require an existing administrator-managed application record.

- **Session Hardening and Idle Protection**  
  Uses secure session settings and inactivity timeout handling to reduce the risk of abandoned active sessions.

- **Logout and Session Cleanup**  
  Ensures user sessions are terminated cleanly and can coordinate with SSO-related cookies when applicable.

### Dashboard and User Experience

- **Dashboard**  
  Provides an overview page for the logged-in user, including profile context, quick access, health indicators, and operational visibility.

- **Theme and Interface Personalization**  
  Supports configurable layout and interface theme options so the platform can adapt to branding or user preferences.

- **Multilingual Interface**  
  Supports multiple interface languages and allows active/default language settings to be managed centrally.

### User and Access Management

- **User Directory Management**  
  Supports searching, reviewing, adding, synchronizing, and organizing users in the internal system.

- **Auto-Provision Visibility**  
  Allows administrators to distinguish SSO-created Staff and Student records directly in the user directory through a dedicated visual indicator.

- **Group Management**  
  Allows administrators to manage user groups, define group identity, and structure access at scale.

- **Module and Menu Access Control**  
  Uses a modular access structure where features are organized by module and menu, and access is assigned by user group.

- **Access Matrix View**  
  Provides a read-only access matrix to help administrators review which roles can access which menus.

- **Role Switching / Multi-Role Support**  
  Supports controlled switching of active roles where a user has more than one permitted group context.

### Profile, Audit, and Accountability

- **User Profile Page**  
  Lets users review their profile, preferences, activity history, and account-related details from one place.

- **Login Activity Monitoring**  
  Displays recent login activity and session-related history for user awareness and administrative review.

- **Audit Trail**  
  Records important system actions and access-related events to support accountability, traceability, and troubleshooting.

### System Configuration

- **General System Settings**  
  Allows administrators to manage runtime values such as system identity, branding-related text, support information, and selected operational settings from the UI.

- **Database Runtime Configuration**  
  Supports environment-based and operational-mode-based Sybase runtime selection for staff-only or staff-and-student access models.

- **Email Configuration**  
  Provides UI-based management for mail server settings, sender identity, and connection testing.

- **Theme and Language Settings**  
  Allows centralized control of default interface theme behavior and active/default application languages.

### Knowledge and Support Content

- **FAQ / Knowledge Page**  
  Provides general-purpose guidance for end users in a clean and readable format.

- **Manual Management**  
  Supports uploading and maintaining user manuals by group, helping each audience access the right supporting documentation.

### External Data Access

- **Staff Data Integration**  
  Connects to a Sybase staff domain for staff lookup and related administrative workflows.

- **Student Data Lookup**  
  Provides controlled student search capability using a dedicated Sybase student domain without mixing student records into internal user management.

### BDR Distance API

- **Single-Record Distance API**  
  Provides a secured read-only API endpoint for retrieving staff identity, site context, standardized address, distance result, issue state, and matching metadata for a single staff record based on `staff_no` and optional `site`.

- **Multi-Site BDR Distance Context**
  Supports `upnm_kampus` and `hat_mizan` destination contexts with separate office labels, office addresses, coordinates, cache filtering, exports, email notification context, and API output.

- **BDR Staff-Site Snapshot**
  Uses `tbl_bdr_staff_site` as the local MySQL staff identity and site assignment snapshot for BDR workflows, allowing HAT Mizan staff assignment to be managed locally even when the Sybase staff view has no official site indicator.

- **BDR Distance Notification Workflow**  
  Supports review-driven email notifications for address and distance issues, including dynamic issue selection, notification exclusions, SSO-only self-confirmation links, and localized email guidance.

## Current Application Modules

The current build already includes several working administrative and operational pages. The list below summarizes the main feature surfaces that are actively present in the system. The `bdr-distance.php` module is intentionally excluded from this summary.

### Core User-Facing Pages

- **Dashboard (`pages/dashboard.php`)**  
  Provides the main landing page with user identity context, active role display, quick operational visibility, KPI-oriented dashboard data, health indicators, announcements, and optional system resource monitoring for privileged administrators.

- **Profile (`pages/profile.php`)**  
  Acts as the user self-service account page with profile details, login activity, audit trail visibility, active session awareness, preference-related context, and audit metadata inspection for Super Admin review.

- **FAQ (`pages/soalan-lazim.php`)**  
  Offers categorized internal guidance for system users, including help on login, navigation, profile usage, user management context, security awareness, and support flow references.

### Administrative Governance Modules

- **User List (`pages/senarai-pengguna.php`)**  
  Serves as the main user governance module for staff, student, and public accounts. It supports listing, searching, adding users, editing user records, assigning groups, updating access flags, managing extra roles, refreshing user data through modal- and AJAX-driven workflows, and identifying SSO auto-provisioned accounts directly from the listing UI.

- **Group Management (`pages/kumpulan-pengguna.php`)**  
  Centralizes management of user groups, module access, menu access, group identity styling, icon choices, ordering, and the structure used by the platform authorization model. The current flow also keeps sidebar navigation state synchronized in the background after relevant access changes, with clearer active-state highlighting for main and child sidebar menus.

- **Access Matrix (`pages/access-matrix.php`)**  
  Provides a read-only matrix view that allows administrators to review which groups or roles have access to which modules and menu paths through the dedicated `access-matrix.php` page.

- **Audit Center (`pages/audit-center.php`)**  
  Functions as a Super Admin audit workspace with AJAX-based navigation across events, requests, sessions, changes, and security views, including filtering, paging, export operations, metadata inspection, and selected administrative actions.

### System Configuration and Content Modules

- **System Settings (`pages/tetapan-sistem.php`)**  
  Provides centralized runtime configuration for general settings, authentication policy, email settings and test delivery, database runtime selection, theme configuration, and language configuration. The authentication policy workspace now also includes dedicated SSO auto-provision controls for Staff and Student identities, together with configurable default group code assignment.

- **Email Template Management (`pages/template-emel.php`)**  
  Provides a structured workspace for maintaining system email templates, including template listing, filters, status management, placeholder usage, seed templates, subject/body content editing, render-oriented template administration, AJAX-based create/update/duplicate/archive/delete actions without page refresh, developer-oriented placeholder guidance, generated integration snippet support, dynamic sample JSON preview data based on placeholders used by the current template, and manual-close SweetAlert feedback flows.

- **System Template Generator (`pages/template-generator.php`)**  
  Provides a scaffold generator for new system pages and related artifacts. It supports a tabbed creation modal for template form input, page icon selection, and access mode definition, together with compact preview output, collision detection, generated artifact tracking, and access policy selection such as `Super Admin Only` or `Group Menu Based`. The current icon picker offers 48 selectable icons for generated pages.

- **Manual Management (`pages/manage-manuals.php`)**  
  Supports upload, replacement, deletion, and group-based assignment of user manuals so that each audience can access role-appropriate documentation.

### External Domain Utility Modules

- **Student Lookup (`pages/carian-pelajar.php`)**  
  Provides controlled lookup of active student records through the Sybase student domain when student mode is enabled, including runtime-mode awareness and searchable results display.

### Prototype and Reference Pages

These pages appear to function as development references or internal UI prototypes rather than primary production-facing business modules:

- **Data Staff Test (`pages/data-staf-test.php`)**  
  Demonstrates an admin-style listing page with DataTables integration, sample add/view/edit/delete modal flows, and SweetAlert-driven feedback.

- **Data Pelajar Tab Test (`pages/data-pelajar-tab-test.php`)**  
  Demonstrates a tabbed workspace pattern with overview content, configuration form layout, history table rendering, and lightweight interaction examples.

- **Tab Management Test (`pages/tab-management-test.php`)**  
  Demonstrates a similar tabbed management layout pattern intended as a reusable interface reference for future modules.

## Key Improvements in Current Version

Compared with older and more tightly coupled approaches, the current version introduces several important improvements:

- **Safer deployment structure with `public/` web root**  
  The deployable web surface is separated from project-level configuration and tooling, which helps reduce accidental exposure of non-public files.

- **Environment-based secret handling with `.env`**  
  Database credentials and sensitive runtime values are no longer intended to live as hardcoded secrets in source files.

- **Docker-ready local deployment**  
  The system now includes Docker support for predictable local development and easier environment consistency.

- **Improved modular architecture**  
  Features are organized more clearly across classes, controllers, AJAX endpoints, pages, helpers, and configuration layers.

- **Runtime-configurable system settings**  
  Core business-facing settings can now be maintained through the system UI instead of relying only on static PHP configuration.

- **Multilingual platform behavior**  
  Language support is now more integrated into the user experience and administrative configuration.

- **Theme configuration and personalization**  
  Default theme behavior and user-facing presentation are managed more cleanly than in earlier static approaches.

- **Sybase multi-domain runtime model**  
  The system now supports a clearer separation between staff and student external database domains instead of relying on a single active Sybase model.

- **Legacy configuration cleanup**  
  Older single-active Sybase patterns and legacy runtime assumptions have been reduced or retired in favor of more explicit environment and operational mode handling.

- **Improved deployment hardening**  
  Docker runtime, public-only serving, and environment-based secrets make the platform more suitable for controlled production deployment.

## Technology Stack

### Backend

- PHP 8.3.30
- Apache HTTP Server
- PDO-based database access
- MySQL 8.0.41 for core application data
- Sybase access through `pdo_dblib` and ODBC-based connection options

### Frontend

- Bootstrap 5
- jQuery
- DataTables
- SweetAlert2
- Alpine.js
- ApexCharts
- Tailwind CSS build support for selected surfaces

### Infrastructure and Tooling

- Docker and Docker Compose
- Apache virtual host configuration
- OpenSSL-based local SSL support
- `.env`-based runtime configuration

## High-Level Architecture

The system follows a practical layered PHP application structure.

### Request Flow

1. A request enters through the `public/` web root.
2. Core bootstrap logic in `includes/init.php` prepares the session, environment, configuration, translations, helpers, and security context.
3. Pages call controllers and service-style classes to load data and perform business logic.
4. Data is retrieved from MySQL and, where needed, from staff or student Sybase domains.
5. Views render the UI using shared includes such as head, topbar, sidebar, footer, and script assets.

### Main Application Layers

- **Configuration Layer**  
  Holds static defaults and environment-driven runtime configuration.

- **Bootstrap and Helper Layer**  
  Initializes sessions, language, theme, security behavior, helper functions, and request-level services.

- **Controller Layer**  
  Coordinates page behavior, validation, and data assembly for specific modules.

- **Class / Model Layer**  
  Provides reusable access to database connections, users, groups, configuration data, mail handling, and audit behavior.

- **Page Layer**  
  Renders the user-facing administrative interface.

- **AJAX Layer**  
  Handles asynchronous operations such as lookup, update, synchronization, and modal-driven actions.

## Authentication Overview

Authentication in e-Base follows a hybrid approach.

### Internal Login Logic

- Users can authenticate using internal credentials stored in the application domain.
- Login validation checks account existence, password validity when required, and whether the account is active.
- Successful authentication initializes the user session, role context, language, and theme preferences.

### SSO Compatibility

- The system includes optional hooks for SSO-related behavior.
- The login flow is designed so that, when user identity is already trusted externally, the application can continue with internal user-session setup without relying exclusively on password verification.
- Logout logic also includes compatibility handling for SSO-related cookies or identity-provider coordination where configured.

### Authorization Model

- Authentication determines who the user is.
- Authorization is controlled separately using:
  - group assignment
  - module access
  - menu access
  - active role context

This makes the platform suitable for structured internal administration environments.

## BDR Distance API

The platform includes a secured single-record API for exposing BDR staff identity, site context, address, and distance state to internal developer consumers.

### Endpoint

```text
GET /api/bdr-distance-record.php?staff_no=<staff_no>&site=<site_code>
```

Example:

```text
GET /api/bdr-distance-record.php?staff_no=0696-11&site=upnm_kampus
GET /api/bdr-distance-record.php?staff_no=1295-16&site=hat_mizan
```

If `site` is omitted, the API defaults to `upnm_kampus`. Invalid site values return `422` instead of silently falling back to another site.

Supported sites:

- `upnm_kampus`
- `hat_mizan`

### Authentication

The API is protected and does not allow anonymous direct access.

- Send the API key using `X-API-Key`
- `Authorization: Bearer <key>` is also accepted
- Optional IP allowlisting is supported through environment configuration

Required environment variables:

- `BDR_DISTANCE_API_KEY`
- `BDR_DISTANCE_API_ALLOWED_IPS`  
  Optional comma-separated allowlist such as `127.0.0.1,10.10.10.25`

### Purpose

This endpoint is intended for internal system-to-system consumption where a downstream team needs:

- staff identity data from the local BDR staff-site snapshot
- site context, office label, office address, and office coordinates
- the latest standardized mailing address
- the latest cached distance result
- issue classification such as `review_location` or `address_changed`
- matching and provider metadata already derived by the application

The endpoint does not trigger a fresh distance calculation. It returns the latest normalized state already available in the local MySQL distance cache, or staff snapshot data with `distance.state = not_yet_calculated` when the staff exists in `tbl_bdr_staff_site` but no distance cache record exists yet.

Single-record lookup is resolved from `tbl_m_staff_distance_cache` using `staff_no` mapped to `f_stafID` and `site` mapped to `f_siteCode`. Staff identity fields such as name, email, department, department code, and position are hydrated from `tbl_bdr_staff_site`. The API does not depend on a live Sybase lookup during the request.

### Request Example

```bash
curl -H "X-API-Key: your-secret-key" "https://ebase.dev/api/bdr-distance-record.php?staff_no=0696-11&site=upnm_kampus"
```

### Response Example

```json
{
  "success": true,
  "data": {
    "staff_no": "0696-11",
    "name": "Nama Staf",
    "department": "Bahagian Teknologi Maklumat dan Komunikasi",
    "department_code": "4100",
    "position": "Pegawai Teknologi Maklumat",
    "email": "staf@upnm.edu.my",
    "site": {
      "site_code": "upnm_kampus",
      "office_label": "Kampus UPNM",
      "office_address": "UNIVERSITI PERTAHANAN NASIONAL MALAYSIA, KEM SUNGAI BESI, 57000 KUALA LUMPUR, WILAYAH PERSEKUTUAN KUALA LUMPUR, MALAYSIA",
      "office_coords": {
        "lat": 3.052805,
        "lon": 101.7233
      }
    },
    "address": {
      "alamat1": "No 15 Jalan Bunga Cempaka 6",
      "alamat2": "Taman Muda",
      "alamat3": "Cheras",
      "poskod": "56100",
      "negeri": "Kuala Lumpur",
      "negara": "Malaysia",
      "standardized_address": "No 15 Jalan Bunga Cempaka 6, Taman Muda, Cheras, 56100, Kuala Lumpur, Malaysia",
      "status": "VALID"
    },
    "distance": {
      "state": "calculated",
      "km": 12.42,
      "label": "12.42 KM",
      "source": "route",
      "route_provider": "google",
      "direction_url": "https://www.google.com/maps/dir/?api=1&destination=Universiti+Pertahanan+Nasional+Malaysia",
      "route_points": []
    },
    "matching": {
      "match_quality": "HIGH",
      "matched_query": "No 15 Jalan Bunga Cempaka 6, Taman Muda, Cheras, 56100, Kuala Lumpur, Malaysia",
      "provider_display_name": "No 15, Jalan Bunga Cempaka 6, Taman Muda, 56100 Cheras, Kuala Lumpur, Malaysia",
      "home_coords_label": "3.123456, 101.765432",
      "office_coords_label": "3.052805, 101.723300"
    },
    "issue": {
      "issue_type": "none",
      "issue_label": "No Issue",
      "is_review_location": false,
      "is_address_changed": false,
      "debug_reason": "loaded_from_cache"
    },
    "cache": {
      "cache_origin": "db",
      "distance_attempted": true
    },
    "generated_at": "2026-04-09T10:15:00+08:00"
  }
}
```

### Error Behavior

- `401 Unauthorized` when API key is missing or invalid
- `403 Forbidden` when IP allowlisting is configured and the client IP is not allowed
- `404 Not Found` when `staff_no` is not found in either `tbl_m_staff_distance_cache` or `tbl_bdr_staff_site` for the requested site
- `422 Unprocessable Entity` when `staff_no` is missing or `site` is invalid
- `503 Service Unavailable` when the local cache backend is temporarily unavailable

## BDR Distance Notification Workflow

The BDR distance page includes an email notification workflow for staff address and distance records that require follow-up.

The page supports site-aware BDR operations for Kampus UPNM and HAT Tuanku Mizan. Site tabs keep destination-specific cache lookup, issue handling, export filename context, email text, and self-confirm exclusions separate.

### Issue Categories

The workflow can identify and label these notification issues:

- `address_changed`: the current address no longer matches the stored calculated address state
- `review_location`: the provider match quality requires manual review
- `state_mismatch`: the state selected in the staff address record does not match the state detected from the address text
- `too_close`: the calculated distance is below the configured near-distance review threshold
- `too_far`: the calculated distance is above the configured far-distance review threshold, currently 100 KM
- `outside_allowed_region`: the detected address state is outside the normal Selangor, Kuala Lumpur, or Putrajaya review region

### Bulk Email Selection

Bulk email notification supports dynamic issue filtering. The modal only shows issue categories that exist in the current result set, so administrators do not need to send reminders for issue types that are already resolved or absent.

Each email uses point-form reason text and one primary action. If all active issues for the address are self-confirmable, the primary action is the self-confirmation link. Otherwise, the email directs the user to update the address through iMAP.

### Self-Confirmation

Self-confirmation is available only for `outside_allowed_region`, `too_far`, and `too_close`.

The confirmation link routes to `pages/bdr-notification-confirm.php` and requires SSO login. Manual login is intentionally not used for this page so the confirmation can be matched against the authenticated staff identity.

The user declaration shown before confirmation is:

```text
Saya mengesahkan alamat ini ialah kediaman semasa saya dan digunakan untuk berulang-alik bekerja. Sebarang maklumat palsu boleh dikenakan tindakan.
```

Confirmed records are stored as active notification exclusions so the same staff/address/issue combination will not keep receiving the same alert.

### Database Objects

The notification workflow uses these MySQL objects:

- `tbl_m_staff_distance_cache` stores the latest local BDR distance cache used by the API and page hydration.
- `tbl_bdr_staff_site` stores local staff identity and site assignment data used by BDR page scoping and API hydration.
- `tbl_bdr_email_bulk_job` stores bulk email job headers and processing totals.
- `tbl_bdr_email_bulk_job_item` stores bulk email recipients and rendered issue/action content, including `f_selfConfirmHtml` and `f_selfConfirmText`.
- `tbl_bdr_email_notification_exclusion` stores admin-created and self-confirmed notification exclusions per `f_siteCode`, `f_staffNo`, `f_addressHash`, and `f_issueType`.

### Debug Logging

Temporary BDR/API/access/SSO debug logs are disabled by default in production. Enable them only during troubleshooting with these environment flags:

- `BDR_DEBUG_LOGS=1`
- `BDR_DISTANCE_API_LOG_ENABLED=1`
- `ACCESS_TRACE_LOG_ENABLED=1`
- `SSO_DEBUG_LOG_ENABLED=1`
- `TETAPAN_AJAX_DEBUG_LOG_ENABLED=1`

The `public/log` directory includes an `.htaccess` deny rule so browser access to log files is blocked on Apache deployments.

### Email Testing

For controlled testing, set `BDR_EMAIL_TEST_RECIPIENT` in the environment. When configured, BDR notification delivery can be routed to the test mailbox while retaining the original staff recipient context in the job/debug data.

### Alert Behavior

Standard SweetAlert modal alerts require a user click to close. Toast alerts remain as short-lived top-right notifications and continue to auto-close.

## Simplified Folder Structure

```text
e-base/
├─ public/
│  ├─ actions/          # action handlers and task-specific workflows
│  ├─ ajax/             # asynchronous endpoints
│  ├─ assets/           # CSS, JS, images, vendor libraries
│  ├─ cache/            # runtime cache and temp storage
│  ├─ classes/          # reusable core classes and models
│  ├─ configuration/    # application and database configuration
│  ├─ controllers/      # page-level controllers
│  ├─ includes/         # bootstrap, layout includes, shared scripts
│  ├─ lang/             # translation files
│  ├─ logs/             # application logs
│  ├─ pages/            # main application pages
│  ├─ setting/          # helper and settings-related utilities
│  ├─ templates/        # reusable content templates
│  └─ uploads/          # uploaded files and manuals
├─ docker/              # Docker, Apache, SSL, and PHP runtime config
├─ docs/                # project notes and migration documents
├─ .env.example         # environment template
├─ docker-compose.yml   # Docker orchestration
├─ Dockerfile           # PHP/Apache image build
└─ README.md
```

## Setup and Installation

### Prerequisites

For local non-Docker setup:

- PHP 8.2 or compatible
- Apache or another web server configured to serve the `public/` directory
- MySQL database
- Sybase connectivity support where staff or student external integration is needed

For Docker setup:

- Docker
- Docker Compose

### 1. Clone the Project

```bash
git clone <your-repository-url>
cd e-base
```

### 2. Prepare Environment Variables

Copy the environment template:

```bash
cp .env.example .env
```

Update `.env` with your actual values, especially:

- `APP_ENV`
- MySQL connection details
- Sybase staff production/development connection details
- Sybase student production/development connection details
- DSN names where ODBC is used

### 3. Docker Setup

Build and run:

```bash
docker compose up -d --build
```

Notes:

- Docker is configured to serve the `public/` directory as the web root.
- The root `.env` file is provided to the container through Docker environment loading.
- Apache and SSL-related configuration are stored in the `docker/` folder.

### 4. Local Setup Without Docker

If running directly on a local web stack:

- point your web server document root to `public/`
- ensure `.env` is available at the project root
- create the MySQL database and required application tables
- ensure Sybase drivers, DSNs, and network access are available if those integrations are required
- verify write permissions for runtime directories such as cache, logs, and uploads

### 5. Verify Startup

After setup:

- open the login page
- confirm MySQL connectivity is working
- sign in with a valid account
- review dashboard access
- confirm role-based menus load correctly
- if required, verify staff and student Sybase connectivity through the relevant administrative features

## Security Practices

The current implementation reflects several practical security improvements.

### Environment-Based Secrets

- Sensitive database credentials are read from `.env`
- Secrets are not intended to be stored directly in committed application source files
- `.env.example` is provided for collaborator onboarding without exposing real secrets

### Public Web Root

- The deployable web surface is served from `public/`
- This reduces the chance of exposing non-public project files directly through the web server

### Session Protection

- Session cookies are configured with secure defaults such as `HttpOnly`
- SameSite behavior is explicitly configured
- Invalid session identifiers are sanitized early during bootstrap

### CSRF Protection

- CSRF tokens are generated and used across form-based workflows and AJAX operations

### Authentication and Authorization Separation

- Login establishes identity
- Access rights are enforced separately through groups, modules, menus, and active role context

### Audit Logging

- Request-level and action-level audit logging helps track critical events such as login activity and administrative operations

### Safe Runtime Configuration

- Business-facing settings can be maintained through the system instead of editing source code directly
- Static defaults remain available as safe fallbacks

### Production-Safe Error Behavior

- Environment-aware bootstrap logic is used to reduce debug exposure outside development contexts

## Recommended Operational Notes

- Treat `.env` as sensitive and never commit it
- Serve only `public/` through the web server
- Keep role, module, and menu assignments tightly controlled
- Review audit logs regularly for administrative systems
- Validate Sybase connectivity separately for staff and student domains after deployment
- Use Docker for consistent local setup when possible

## Developer Information

Name: Ts. Norfirdaus Harun  
Email: norfirdaus@upnm.edu.my
