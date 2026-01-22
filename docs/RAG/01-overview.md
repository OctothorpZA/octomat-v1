# Octomat - Application Overview

## Project Description

Octomat is a modern Laravel 12 application built with React 19, Inertia.js 2, and Tailwind CSS 4. It serves as a comprehensive authentication and user management system with a modern SPA-like experience.

## Core Features

- **User Authentication**: Complete auth system with Laravel Fortify
- **Role-Based Access Control (RBAC)**: Enterprise-grade permission system with 11 hierarchical roles (100-1000 level)
- **Unified Dashboard**: Role-aggregated widget system with dynamic content based on user permissions
- **Admin Role Assignment**: Protected admin interface with advanced search, pagination, and hierarchical validation
- **User Impersonation**: Secure admin impersonation with audit trails and session management
- **Structured User Profiles**: first_name, middle_names, last_name, date_of_birth fields
- **Email Verification**: User email verification workflow
- **Two-Factor Authentication**: QR code-based 2FA with recovery codes
- **Profile Management**: User profile editing and account deletion
- **Password Management**: Secure password updates with rate limiting
- **Appearance Settings**: Dark/light mode theming
- **Modern UI**: React-based frontend with Tailwind CSS styling and accessibility features

## Tech Stack

- **Backend**: Laravel 12.47.0 (PHP 8.5.2)
- **Frontend**: React 19.2.3 with TypeScript
- **Styling**: Tailwind CSS 4.1.18
- **SPA Framework**: Inertia.js 2.0.19
- **Database**: PostgreSQL
- **Authentication**: Laravel Fortify 1.33.0
- **Testing**: Pest 4.3.1, Vitest 4.0.17 (JavaScript)
- **Code Quality**: Laravel Pint 1.27.0, ESLint 9.39.2, Prettier 3.8.0

## Architecture Patterns

- **MVC Architecture**: Traditional Laravel MVC with Inertia.js for SPA experience
- **Form Request Validation**: All form inputs validated through dedicated Form Request classes
- **Service Layer**: Business logic handled through controllers and actions
- **Component-Based UI**: Reusable React components with TypeScript
- **Type-Safe Routing**: Laravel Wayfinder for type-safe route generation

## Development Environment

- **Local Development**: `composer run dev` for full stack development server
- **Database**: PostgreSQL with Laravel migrations
- **Asset Building**: Vite with React and Tailwind CSS
- **Code Quality**: Automated linting and formatting
- **Testing**: Comprehensive test suite with Pest

## Key Conventions

- **PHP**: PSR-12 compliant, strict typing, PHPDoc blocks
- **TypeScript**: Strict mode, organized imports, 4-space indentation
- **Styling**: Utility-first with Tailwind CSS, responsive design
- **Testing**: Feature and unit tests with descriptive names
- **Git**: Conventional commits with linting hooks</content>
  <parameter name="filePath">docs/RAG/overview.md
