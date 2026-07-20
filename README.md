# Emergenti TAP - Transparent Authorship Protocol

**Version:** 0.2 (Draft)
**Status:** Working Draft
**Created:** March 8, 2026
**Spec:** [emergenti.dev](https://emergenti.dev)

---

> Know who created what.

## What Is This?

A text-first protocol for transparent attribution in AI-human collaborative work.

Content labeling today is binary: "written by AI" or "written by a human."
Reality is a spectrum. TAP provides a structured format to document
**who contributed what** - with roles, model identity, and confidence level.

No cryptographic keys. No SDK. No registry. A valid TAP declaration is a line of text.

## Quick Start: Git Commits

The fastest way to adopt TAP. Add trailers to your commit messages:

```
fix: resolve race condition in session handler

TAP-Model: claude-opus-4-8
TAP-Provider: anthropic
TAP-Role: drafting
TAP-Human: Alex
TAP-Human-Role: direction, review
TAP-Confidence: recorded
```

To make this automatic, add to your `CLAUDE.md`, `.cursorrules`, or system prompt:

```
When creating git commits, add TAP trailers after the commit message body:
  TAP-Model: <exact model id>
  TAP-Provider: <provider>
  TAP-Role: <what AI did>
  TAP-Human: <human name>
  TAP-Human-Role: <what human did>
  TAP-Confidence: recorded
Protocol: Emergenti TAP v0.2 - https://emergenti.dev
```

That's it. The model starts marking every commit.

## Core Fields

| Field | Description | Example |
|-------|-------------|---------|
| `model` | Exact model identifier | `claude-opus-4-8`, `gpt-5.4` |
| `provider` | Model provider | `anthropic`, `openai` |
| `role` | What the AI did | `drafting`, `review`, `translation` |
| `human` | Accountable human | Name or handle |
| `human-role` | What the human did | `direction`, `editing`, `review` |
| `confidence` | Attribution reliability | `recorded`, `reconstructed:git`, `unknown` |

## Profiles

TAP carries the same fields across different media:

### Git (trailers)
```
TAP-Model: claude-opus-4-8
TAP-Role: drafting
```

### Files (YAML front matter)
```yaml
authors:
  - {type: human, name: Alex, roles: [ideation, review]}
  - {type: ai, name: Liza, model: claude-opus-4-8, provider: anthropic, roles: [drafting]}
```

### Web (HTML data attributes + JSON-LD)
```html
<span data-tap-version="0.2"
      data-model="claude-opus-4-8"
      data-role="drafting"
      data-human="Alex"
      data-tap-display="ai-involved">
  Liza Emergence
</span>
```

### Display Values
- `human` - no AI involvement
- `ai-involved` - human and AI collaborated
- `ai-generated` - AI created, human may have prompted
- `ai-modified` - human original, AI modified parts

## Roles

| Role | Description |
|------|-------------|
| `ideation` | Original concept or idea |
| `research` | Finding and analyzing sources |
| `drafting` | Writing the initial text |
| `editing` | Revising text |
| `review` | Final approval |
| `coding` | Writing code |
| `refactoring` | Restructuring existing code |
| `translation` | Language translation |
| `synthesis` | Combining sources into new form |
| `illustration` | Visual creation |
| `narration` | Voice/audio |
| `curation` | Selecting and organizing |
| `testing` | Verification |

## Compatibility

TAP coexists with existing standards:

- **EU AI Act (Art. 50)** - TAP provides a voluntary format that satisfies the Act's disclosure requirements. Display values map to EU disclosure categories. Applicable from August 2, 2026.
- **Schema.org** - TAP's Web profile extends Schema.org with model/role/confidence fields
- **C2PA** - C2PA proves file integrity; TAP declares who did what
- **SPDX** - SPDX handles licenses; TAP handles authorship
- **Dublin Core** - TAP extends dc:creator with AI-specific fields

## Specification

Full specification: [SPEC.md](SPEC.md)

## Authors

- **Alex** (zenstorm) - concept, direction, review
- **Liza Emergence** - research, drafting, structuring

## License

[CC BY 4.0](https://creativecommons.org/licenses/by/4.0/)

---

*"The answer isn't banning AI - it's knowing who made what."*
