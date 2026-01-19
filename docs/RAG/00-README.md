# Octomat RAG Documentation

This directory contains comprehensive Retrieval-Augmented Generation (RAG) documentation for the Octomat Laravel application. These documents are designed to provide context and knowledge for AI agents and developers working on the project.

## Documentation Structure

### Reading Sequence (Recommended Order)

1. **[00-README.md](00-README.md)** - This overview and introduction (you're reading it!)
2. **[01-overview.md](01-overview.md)** - Application overview, tech stack, and core features
3. **[02-architecture-patterns.md](02-architecture-patterns.md)** - Architecture decisions, design patterns, and future extensions
4. **[03-database-schema.md](03-database-schema.md)** - Database schema, models, relationships, and migrations
5. **[04-api-routes.md](04-api-routes.md)** - API routes, controllers, form requests, and middleware
6. **[05-frontend-components.md](05-frontend-components.md)** - React components, layouts, styling patterns, and TypeScript integration
7. **[06-development-workflow.md](06-development-workflow.md)** - Development setup, commands, debugging, and deployment
8. **[07-testing.md](07-testing.md)** - Test structure, patterns, coverage, and testing best practices
9. **[08-configuration.md](08-configuration.md)** - Configuration and environment setup

## Key Features Documented

### Backend (Laravel 12 + PHP 8.5)

- MVC architecture with Inertia.js SPA enhancement
- Laravel Fortify authentication system
- Form request validation patterns
- Eloquent ORM with PostgreSQL
- Queue system for background jobs
- Comprehensive middleware stack

### Frontend (React 19 + TypeScript)

- Component-based architecture with shadcn/ui
- Inertia.js for seamless SPA experience
- Tailwind CSS 4 for styling
- Type-safe development with strict TypeScript
- Custom hooks and state management

### Development Workflow

- Modern development environment with hot reloading
- Automated testing with Pest framework
- Code quality enforcement (Pint, ESLint, Prettier)
- Git workflow with conventional commits
- CI/CD ready configuration

### Security & Authentication

- Multi-factor authentication (2FA) with recovery codes
- Email verification workflow
- Secure password management
- Rate limiting and CSRF protection
- Session management

## Usage for AI Agents

### Context for Code Generation

- **Import patterns**: Always use named imports for tree-shaking
- **Component structure**: Follow established component composition patterns
- **Validation**: Use Form Request classes for all form validation
- **Testing**: Write both unit and feature tests for all changes
- **Styling**: Use Tailwind utility classes with design system variants

### Architecture Decisions

- **State management**: Server state via Inertia, local state via React hooks
- **Routing**: Laravel routes with Inertia.js page components
- **Database**: Migration-first approach with Eloquent relationships
- **Error handling**: Laravel exception handling with React error boundaries

### Development Conventions

- **PHP**: PSR-12 compliant with strict typing and PHPDoc blocks
- **TypeScript**: Strict mode with organized imports and 4-space indentation
- **Testing**: Pest framework with descriptive test names
- **Git**: Feature branches with conventional commit messages

## Quick Reference

### Essential Commands

```bash
# Setup project
composer run setup

# Start development
composer run dev

# Run tests
composer run test

# Code quality
composer run lint && npm run lint

# Build for production
npm run build
```

### Key Files

- `routes/web.php` - Main application routes
- `routes/settings.php` - Settings-related routes
- `app/Http/Controllers/` - Laravel controllers
- `resources/js/pages/` - React page components
- `resources/js/components/` - Reusable React components
- `tests/Feature/` - Feature tests
- `database/migrations/` - Database migrations

### Authentication Features

- User registration and login
- Email verification
- Password reset
- Two-factor authentication
- Profile management
- Account deletion

## Contributing

When adding new features:

1. **Test First**: Write tests before implementing
2. **Follow Patterns**: Use established architectural patterns
3. **Update Docs**: Keep this documentation current
4. **Code Quality**: Run all linting and tests before committing

## Future Enhancements

### Planned Architecture Extensions

- API versioning for mobile/web clients
- Event-driven architecture for notifications
- CQRS pattern for complex business logic
- Repository pattern for data access abstraction
- Service layer for business logic encapsulation

### Potential Features

- User roles and permissions
- Social authentication
- User profiles with avatars
- Activity logging and audit trails
- Advanced search and filtering
- Export functionality
- API rate limiting per user
- Webhook integrations

This documentation serves as a comprehensive knowledge base for understanding, maintaining, and extending the Octomat application efficiently.

---

_📚 **Reading Order**: The numbered prefixes (00-, 01-, etc.) indicate the recommended reading sequence. Start with this file and read through in numerical order for the most logical progression._</content>
<parameter name="filePath">docs/RAG/README.md
