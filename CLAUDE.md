# CLAUDE.md

Guidance for Claude Code in `D:\CHATGPT PROJECTS\Bagisto` (MAN SECRET PLEASURE, a Bagisto 2.4.12 store).

@AGENTS.md

## Before you start

Read `PROJECT_CONTEXT.md`. It holds the project facts: what has been built, what is available, known issues and next steps. When you finish, add a row to its progress log.

## Claude Code notes for this machine

- The machine is Windows. The Bash tool is Git Bash and the PowerShell tool is PowerShell 7. Windows `php` is Laravel Herd's `php.bat`; use it for `php -l` and Pint. It cannot reach the Docker database, so run artisan, Pest and Composer inside the `app` container: `docker exec -u www-data -e HOME=/tmp bagisto-local-app-1 ...`.
- The `bagisto-*` skills that upstream Bagisto ships are not installed here. Do not try to load them; follow `AGENTS.md` and the surrounding code.
- Ask before anything hard to reverse or outward-facing: committing, pushing, merging, deploying, deleting data, editing `.env`, creating databases or containers beyond a throwaway test database.
- The owner reads and writes Chinese and English. Answer in the language of the question; keep storefront content in English.
