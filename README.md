# e-Base

e-Base is a web-based internal platform used as a secure and reusable foundation for enterprise applications, administration portals, and operational systems. It provides a structured starting point for building organization-specific solutions with consistent authentication, access control, system configuration, auditability, multilingual support, and modular feature management.

The current version has evolved beyond a simple starter template. It now operates as a practical administration platform with production-ready modules for user governance, group and access management, system configuration, reference content, documentation, and controlled access to external data domains.

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

- PHP 8.2
- Apache HTTP Server
- PDO-based database access
- MySQL for core application data
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
