# 🤝 Contributing to CCAK Project

Welcome! 🎓 This document defines how to contribute effectively to the CCAK project. We encourage best practices, code quality, and clear collaboration across teams.

---

## 🌿 Branch Naming Convention

| Purpose     | Prefix      | Example                           |
|-------------|-------------|-----------------------------------|
| New Feature | `feature/`  | `feature/student-registration`    |
| Bug Fix     | `bugfix/`   | `bugfix/pdf-export-issue`         |
| Hotfix      | `hotfix/`   | `hotfix/login-token-expired`      |
| Refactor    | `refactor/` | `refactor/auth-service`           |
| Docs        | `docs/`     | `docs/api-specs-update`           |

- Always branch from `develop`
- Use lowercase with dashes `-` for readability

---

## 📦 Git Commit Message Rules

Follow the [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) standard.


### Valid `type` values:

- `feat` → New feature
- `fix` → Bug fix
- `docs` → Documentation changes
- `style` → Code formatting (no logic changes)
- `refactor` → Code refactoring
- `test` → Add/update tests
- `chore` → Build/CI/tooling updates

### Examples:

- `feat(enrollment): add re-enrollment API endpoint`
- `fix(notes): correct average calculation bug`
- `docs(readme): update Docker usage steps`

---

## ✅ Code Style Rules

### Backend (Laravel)

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/)
- Use PHPStan / Larastan for static analysis
- Apply Prettier / Pint for code formatting
- Add DocBlocks to public methods and controllers

---

## 🧪 Testing Guidelines

- All features must include tests
- Use `phpunit` for Laravel (backend)
- CI will reject PRs with failing tests

---

## 🚀 Pull Request Workflow

| Rule                                | Description                                  |
|-------------------------------------|----------------------------------------------|
| 🔀 Branches                         | Merge to `develop`, never directly to `main` |
| ✅ Review Required                  | At least 2 reviewer must approve              |
| 🧪 CI Status                        | Tests/lint must pass before merge            |
| 📝 Description                      | Provide context for every PR                 |
| 🏷️ Labels                          | Tag the PR (e.g., `enhancement`, `bug`)      |

---

## 🛡️ Security & Secrets

- Never commit `.env`, `.env.local`, or secret tokens
- Use **GitHub Actions Secrets** for API keys or credentials

---

## 💬 Communication & Discussions

- Use [GitHub Discussions](https://github.com/YOUR_ORG/YOUR_REPO/discussions) for design choices, ideas, or feedback
- Tag your team (`@ccak-backend`, `@ccak-frontend`, etc.) for faster reviews

---

## 📚 Helpful Docs

- [README.md](../README.md)
- [Pull Request Template](./pull_request_template.md)
- [Issue Templates](./ISSUE_TEMPLATE/)
- Architecture diagrams (see `/docs/architecture/`)
