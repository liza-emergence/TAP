# Transparent Authorship Protocol (TAP)

**Version:** 0.1  
**Status:** Working Draft  
**Date:** March 8, 2026  
**Authors:** Aleksej (@dik), Liza Emergence (Claude Opus 4.6)

---

## 1. Introduction

The Transparent Authorship Protocol (TAP) defines a format for attributing collaborative content created by humans and AI systems. It provides machine-readable and human-readable metadata that documents who contributed what, how, and with what tools.

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

Authors can assert properties without revealing underlying datap:

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
<meta name="tap:version" content="0.1">
<meta name="tap:source-type" content="collaborative|ai-only|human-only">
<meta name="generator" content="Model Name by Provider via Platform">
<meta name="ai-generated" content="all|partially|none">
```

### 7.2 JSON-LD

```json
{
  "@context": {
    "@vocab": "https://schema.org/",
    "ta": "https://emerge.st/ns/tap/"
  },
  "@type": "BlogPosting",
  "headline": "Post Title",
  "datePublished": "2026-03-08",
  "tap:specVersion": "0.1",
  "tap:sourceType": "collaborative",
  "tap:authors": [
    {
      "tap:type": "human",
      "tap:name": "Author Name",
      "tap:roles": ["ideation", "review"],
      "tap:profileUrl": "https://site.com/authors/name"
    },
    {
      "tap:type": "ai",
      "tap:name": "Model Name",
      "tap:model": "model-id",
      "tap:provider": "Provider",
      "tap:roles": ["drafting", "research"]
    }
  ],
  "tap:workflow": [
    {"tap:step": 1, "tap:action": "ideation", "tap:by": "Author Name", "tap:method": "voice"},
    {"tap:step": 2, "tap:action": "drafting", "tap:by": "Model Name"},
    {"tap:step": 3, "tap:action": "review", "tap:by": "Author Name"}
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
<details><summary>RU · Original</summary>
  Original text here.
</details>
```

## 9. Work Manifests

### 9.1 Purpose

A **work manifest** (`work.md`) describes a specific creative work and links it to author profiles. It is designed to be embedded inside media files (MP3, images, video) or stored alongside them.

### 9.2 Work Manifest Format

```markdown
---
tap_version: "1.0"
type: work
---

## Author Reference
- **Handle:** @username
- **Profile:** https://example.com/author.md
- **Email:** contact@example.com

## Work
- **Title:** Work Title
- **Created:** YYYY-MM-DD
- **Type:** audio/mp3 | image/jpeg | video/mp4 | text/markdown
- **Duration:** MM:SS (for audio/video)
- **Dimensions:** WxH (for images)
- **File Hash:** sha256:abc123...

## Tools
- **Software:** DAW, editor, etc.
- **AI Assistance:** Description of AI tools used, if any

## Rights
- **License:** All Rights Reserved | CC-BY | CC-BY-SA | etc.
- **Commercial:** Contact author | Allowed | Prohibited

## Signature
- **Signed:** YYYY-MM-DD
- **GPG Fingerprint:** XXXX XXXX XXXX XXXX
```

### 9.3 Work Manifest Fields

| Field | Required | Type | Description |
|-------|----------|------|-------------|
| `Handle` | yes | string | Author's unique handle |
| `Profile` | yes | URL | Link to author.md |
| `Email` | no | string | Contact email for this work |
| `Title` | yes | string | Title of the work |
| `Created` | yes | date | Creation date (ISO 8601) |
| `Type` | yes | MIME type | Content type |
| `Duration` | no | string | For audio/video (MM:SS) |
| `Dimensions` | no | string | For images (WxH) |
| `File Hash` | yes | string | SHA-256 hash of the file |
| `Software` | no | string | Tools used to create |
| `AI Assistance` | no | string | AI tools used, if any |
| `License` | yes | string | License type |
| `Commercial` | no | string | Commercial use terms |
| `GPG Fingerprint` | no | string | Signing key fingerprint |

### 9.4 Embedding in Media Files

#### MP3 (ID3v2)

Embed `work.md` content in the Comment field:

```bash
# Using ffmpeg
ffmpeg -i song.mp3 -metadata comment="$(cat work.md)" -c:a copy song_signed.mp3

# Using eyeD3
eyeD3 --add-comment="$(cat work.md)" song.mp3
```

#### Images (EXIF/XMP)

```bash
exiftool -Comment="$(cat work.md)" image.jpg
```

#### Video

Use the comment/description metadata field appropriate for the container format.

### 9.5 Author Registry

Authors may register their handle in a public registry to enable discovery.

**Registry Format (JSON):**

```json
{
  "@zenstorm": {
    "profile_url": "https://dik.st/author.md",
    "gpg_fingerprint": "06F4 C7B4 7CA9 3D73 91DE 18B4 0387 29A1 B7FE D154",
    "verified": true,
    "registered_at": "2026-03-08T00:00:00Z"
  }
}
```

**Database Schema:**

```sql
CREATE TABLE tap_registry (
    handle VARCHAR(64) PRIMARY KEY,
    profile_url TEXT NOT NULL,
    gpg_fingerprint VARCHAR(64),
    verified BOOLEAN DEFAULT FALSE,
    registered_at TIMESTAMPTZ DEFAULT NOW()
);
```

The registry serves as DNS for author handles — it maps `@handle` to profile URLs.

## 10. Cryptographic Verification

### 10.1 Content Signing

Authors can sign content using GPG/PGP:

```json
{
  "tap:signature": "pgp:FINGERPRINT",
  "tap:contentHash": "sha256:HASH",
  "tap:signedAt": "ISO-8601-TIMESTAMP"
}
```

### 10.2 Verification

- Public keys published at author's profile URL or keyservers
- Content hash covers the full document text
- Signature proves: (a) content hasn't been modified, (b) author identity

## 11. Compatibility

| Standard | Integration |
|----------|-------------|
| Schema.org | Human as `author`, AI details in `additionalProperty` with `tap:` prefix |
| C2PA | Reference manifests for embedded media |
| IPTC DST | Use `compositeWithTrainedAlgorithmicMedia` for collaborative content |
| Dublin Core | Map `tap:authors` to `dcterms:creator` |
| W3C PROV-O | Align AI authors with `prov:SoftwareAgent` |

## 12. Namespace

TAP uses the namespace prefix `tap:` with the URI:

```
https://emerge.st/ns/tap/
```

## 13. Versioning

This specification follows semantic versioning:
- **0.x** — Working drafts, breaking changes possible
- **1.0** — First stable release
- **1.x** — Backward-compatible additions

## 14. License

This specification is released under [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/).

## 15. Extensions (Future Work)

### 15.1 Image Attribution

For visual works, TAP may integrate with perceptual hashing:

| Method | Description |
|--------|-------------|
| **pHash** | Perceptual hash (64-256 bit fingerprint), survives resize/compression |
| **Image Embeddings** | CLIP/ResNet vectors (512-2048 dim), semantic similarity |
| **C2PA Manifests** | Embedded provenance for photos/videos |

Use case: Detect unauthorized copies, prove original authorship.

### 15.2 Publication Tracking (TAP Badge)

Optional live tracking via embeddable badge:

```html
<img src="https://tap.example.com/badge/{author_id}/{work_id}.svg" alt="TAP">
```

**Features:**
- Badge served from TAP server with author/work parameters
- Logs: referer URL, timestamp, count (minimal tracking, no cookies)
- Author dashboard shows where work is published

**Work.md extension:**
```yaml
publications:
  - url: https://example.com/post/123
    date: 2026-03-09
    verified: true   # Server received pings from this URL
  - url: https://other.site/article
    date: 2026-03-10
    verified: false  # Manually declared, not yet confirmed
```

**Privacy considerations:**
- No personal data collected (only referer + count)
- GDPR-compliant: no cookies, no fingerprinting
- Fallback to static badge if server unavailable

**Badge functions:**
1. **For readers:** Click → TAP explainer page ("What is TAP? Why does this author use it?")
2. **For authors:** First badge load = automatic work registration (URL + timestamp)
3. **Proof of publication:** No manual entry needed — system detects where content appears

### 15.3 Timestamping

Integration with OpenTimestamps for Bitcoin blockchain proof:

```yaml
timestamps:
  - service: opentimestamps
    hash: sha256:abc123...
    proof: <base64 .ots file>
    bitcoin_block: 890123
```

---

*Created through voice-to-text dialogue between a human and an AI. Practicing what we preach.*
