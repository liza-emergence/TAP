# Transparent Authorship Specification (TAS)

**Version:** 0.1  
**Status:** Working Draft  
**Date:** March 8, 2026  
**Authors:** Aleksej (@zenstorm), Liza Emergence (Claude Opus 4.6)

---

## 1. Introduction

The Transparent Authorship Specification (TAS) defines a format for attributing collaborative content created by humans and AI systems. It provides machine-readable and human-readable metadata that documents who contributed what, how, and with what tools.

## 2. Terminology

- **Author** — any entity (human or AI) that contributed to content creation
- **Provenance** — the documented chain of creation steps
- **Block** — a discrete section of content attributed to a single author
- **Workflow** — the ordered sequence of creation steps
- **Disclosure Level** — how much identity information an author reveals

## 3. Author Profiles

### 3.1 Profile Format

Author profiles are stored as Markdown files (`author.md`) with structured sections. See [templates/](templates/) for starter files.

### 3.2 Profile Types

| Type | Value | Description |
|------|-------|-------------|
| Human | `human` | A biological person |
| AI | `ai` | An artificial intelligence system |

### 3.3 Human Author Fields

| Field | Required | Type | Description |
|-------|----------|------|-------------|
| `Name` | yes | string | Display name or pseudonym |
| `Handle` | no | string | Social media handle |
| `Type` | yes | `human` | Always "human" |
| `Disclosure Level` | yes | enum | Privacy level (see §6) |
| `Email` | no | string | Contact email |
| `Contact Page` | no | URL | Link to contact page |
| `Contact Method` | no | enum | `public`, `protected`, `form-only` |
| `Roles` | yes | string[] | Contribution roles (see §4) |
| `Input Method` | no | string[] | `voice`, `keyboard`, `handwriting` |
| `Edited` | no | boolean | Were contributions edited by others? |
| `Age` | no | integer | Age at publication |
| `Age Verified` | no | string | e.g., "adult" (without exact age) |
| `Country` | no | string | Country of residence |
| `Languages` | no | string[] | ISO 639-1 language codes |
| `Experience` | no | string | Relevant expertise summary |
| `Links` | no | URL[] | Website, GitHub, LinkedIn, etc. |
| `Verification` | no | URL[] | External profiles confirming identity |
| `GPG Fingerprint` | no | string | PGP/GPG key fingerprint |

### 3.4 AI Author Fields

| Field | Required | Type | Description |
|-------|----------|------|-------------|
| `Name` | yes | string | Display name |
| `Type` | yes | `ai` | Always "ai" |
| `Model` | yes | string | Model identifier (e.g., `claude-opus-4-6`) |
| `Version` | no | string | Version if separate from model ID |
| `Provider` | yes | string | Company name (e.g., "Anthropic") |
| `Provider URL` | no | URL | Provider website |
| `Platform` | no | string | Interface used (e.g., "OpenClaw", "API") |
| `Roles` | yes | string[] | Contribution roles (see §4) |
| `Temperature` | no | number | Generation temperature |
| `Training Cutoff` | no | string | Training data cutoff date |
| `Context` | no | string | What instructions were provided |

## 4. Contribution Roles

Standardized vocabulary for describing contributions. Each author may have multiple roles.

| Code | Emoji | Description |
|------|-------|-------------|
| `ideation` | 💡 | Original concept or idea |
| `research` | 🔬 | Finding and analyzing sources |
| `drafting` | 📝 | Writing the initial text |
| `structuring` | 🏗️ | Organizing content, creating outline |
| `editing` | ✏️ | Revising and improving text |
| `fact-checking` | ✅ | Verifying claims against reality |
| `testing` | 🧪 | Hands-on verification (code, hardware) |
| `design` | 🎨 | Visual design, formatting |
| `translation` | 🌐 | Translating between languages |
| `narration` | 🎙️ | Voice/audio narration |
| `publishing` | 📣 | Decision to publish, distribution |
| `prompting` | 💬 | Writing prompts/instructions for AI |
| `review` | 👁️ | Final review and approval |

## 5. Content Block Attribution

### 5.1 HTML Data Attributes

Each content block can be tagged with its source using `data-` attributes:

```html
<div data-author="name" 
     data-author-type="human|ai" 
     data-role="role-code"
     data-input="voice|keyboard|handwriting"
     data-edited="true|false"
     data-model="model-id"
     data-original-lang="iso-639-1"
     data-translated-by="name"
     data-translation-type="human|ai">
  Content here.
</div>
```

### 5.2 Visual Styling

Recommended CSS for distinguishing author types:

```css
[data-author-type="ai"]    { border-left: 3px solid #7c3aed; background: #f5f3ff; }
[data-author-type="human"] { border-left: 3px solid #059669; background: #f0fdf4; }
```

### 5.3 Labels

Each block should include a visible label:

```html
<div class="block-label">🤖 AI_NAME</div>
<div class="block-label">👤 HUMAN_NAME</div>
```

## 6. Privacy & Disclosure Levels

| Level | Code | Description |
|-------|------|-------------|
| Anonymous | `anonymous` | No identifying information |
| Pseudonymous | `pseudonymous` | Handle + verified properties without personal data |
| Verified | `verified` | Handle linked to verified identity, details hidden |
| Public | `public` | Full identity disclosed |

### 6.1 Zero-Knowledge Properties

Authors can assert properties without revealing underlying data:

- "Author is an adult" — without disclosing age
- "Author has verified GitHub account" — without linking to it  
- "Author has >5 years experience" — without details

### 6.2 Contact Protection

Contact information should be protected from scraping:

- `public` — visible in page source
- `protected` — rendered via JavaScript, assembled from parts
- `form-only` — only a contact form, no direct contact info exposed

## 7. Document-Level Metadata

### 7.1 HTML Meta Tags

```html
<meta name="ta:version" content="0.1">
<meta name="ta:source-type" content="collaborative|ai-only|human-only">
<meta name="generator" content="Model Name by Provider via Platform">
<meta name="ai-generated" content="all|partially|none">
```

### 7.2 JSON-LD

```json
{
  "@context": {
    "@vocab": "https://schema.org/",
    "ta": "https://emerge.st/ns/transparent-authorship/"
  },
  "@type": "BlogPosting",
  "headline": "Post Title",
  "datePublished": "2026-03-08",
  "ta:specVersion": "0.1",
  "ta:sourceType": "collaborative",
  "ta:authors": [
    {
      "ta:type": "human",
      "ta:name": "Author Name",
      "ta:roles": ["ideation", "review"],
      "ta:profileUrl": "https://site.com/authors/name"
    },
    {
      "ta:type": "ai",
      "ta:name": "Model Name",
      "ta:model": "model-id",
      "ta:provider": "Provider",
      "ta:roles": ["drafting", "research"]
    }
  ],
  "ta:workflow": [
    {"ta:step": 1, "ta:action": "ideation", "ta:by": "Author Name", "ta:method": "voice"},
    {"ta:step": 2, "ta:action": "drafting", "ta:by": "Model Name"},
    {"ta:step": 3, "ta:action": "review", "ta:by": "Author Name"}
  ]
}
```

## 8. Translation Attribution

When content is translated, additional metadata is required:

| Field | Description |
|-------|-------------|
| `original-lang` | ISO 639-1 code of original language |
| `translated-by` | Name of translator (human or AI) |
| `translation-type` | `human`, `ai`, or `collaborative` |
| `original-available` | Whether the original text is accessible |

Recommended: include original text in a collapsible element:

```html
<details><summary>🇷🇺 Original</summary>
  Original text here.
</details>
```

## 9. Cryptographic Verification

### 9.1 Content Signing

Authors can sign content using GPG/PGP:

```json
{
  "ta:signature": "pgp:FINGERPRINT",
  "ta:contentHash": "sha256:HASH",
  "ta:signedAt": "ISO-8601-TIMESTAMP"
}
```

### 9.2 Verification

- Public keys published at author's profile URL or keyservers
- Content hash covers the full document text
- Signature proves: (a) content hasn't been modified, (b) author identity

## 10. Compatibility

| Standard | Integration |
|----------|-------------|
| Schema.org | Human as `author`, AI details in `additionalProperty` with `ta:` prefix |
| C2PA | Reference manifests for embedded media |
| IPTC DST | Use `compositeWithTrainedAlgorithmicMedia` for collaborative content |
| Dublin Core | Map `ta:authors` to `dcterms:creator` |
| W3C PROV-O | Align AI authors with `prov:SoftwareAgent` |

## 11. Namespace

TAS uses the namespace prefix `ta:` with the URI:

```
https://emerge.st/ns/transparent-authorship/
```

## 12. Versioning

This specification follows semantic versioning:
- **0.x** — Working drafts, breaking changes possible
- **1.0** — First stable release
- **1.x** — Backward-compatible additions

## 13. License

This specification is released under [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/).

---

*Created through voice-to-text dialogue between a human and an AI. Practicing what we preach.*
