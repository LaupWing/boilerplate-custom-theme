# Project Instructions

## Core Rule
**ALWAYS explain what you are about to do BEFORE doing it, and wait for user confirmation.** Do NOT use plan mode. Just explain conversationally and wait for a "yes" or confirmation before proceeding.

## Documentation
- **Changelog:** After making changes, always create or update `docs/changelog/YYYY-MM-DD.md` with a summary of what was done. One file per day — append if it already exists. Keep entries concise.
- **Todo:** When new tasks or ideas come up, add them to `docs/todo/YYYY-MM-DD.md`. Split into "Next up" (priority) and "Future" (parked).

## Block Development
- When creating content blocks (blocks that display page content like text, headings, articles), always add `data-seo-content` to the outer `<section>` tag in `render.php`. This tells Snel SEO which parts of the page contain real content for AI-powered SEO generation. Layout blocks (topbar, navbar, footer) should NOT have this attribute.

## Project Overview
- Boilerplate WordPress theme for starting new client projects.
- Base framework with multilingual system, SEO, Tailwind CSS, and custom Gutenberg blocks.
