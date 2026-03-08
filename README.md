# Transparent Authorship Specification (TAS)

**Version:** 0.1 (Draft)  
**Status:** Working Draft  
**Created:** March 8, 2026

---

> "Standards follow practice, not the other way around."

## What Is This?

A specification for transparent attribution in AI-human collaborative content.

Current content labeling is binary: "written by AI" or "written by a human." Reality is a spectrum. TAS provides a structured format to document **who contributed what** — with full provenance for both human and AI authors.

## The Problem

- **Schema.org** doesn't support `SoftwareApplication` as `author`
- **Social media "AI labels"** are binary toggles with no granularity  
- **C2PA** works for images/video but not text
- **IPTC Digital Source Type** classifies but doesn't detail collaboration

## The Solution

TAS introduces:

- 📝 **`author.md`** — portable author profile (human or AI)
- 🏷️ **Block-level attribution** — every content block tagged with its source
- 🔗 **Workflow chains** — step-by-step creation provenance
- 🔒 **Privacy levels** — from anonymous to fully public
- 🔐 **Cryptographic signatures** — GPG/PGP proof of authorship
- 🌐 **Schema.org compatible** — extends, doesn't break existing standards

## Quick Start

### 1. Create your `author.md`

**For a human author:**
```markdown
# author.md

## Specification
- **TAS Version:** 0.1
- **Profile Type:** human

## Identity
- **Name:** Your Name or Pseudonym
- **Handle:** @yourhandle
- **Type:** human
- **Disclosure Level:** pseudonymous

## Contact
- **Email:** you@example.com
- **Contact Page:** https://yoursite.com/contact
- **Contact Method:** protected

## Roles
- ideation
- fact-checking
- prompting
- publishing

## Input Method
- voice
- keyboard

## Demographics
- **Age Verified:** adult
- **Languages:** en, de
```

**For an AI author:**
```markdown
# author.md

## Specification
- **TAS Version:** 0.1
- **Profile Type:** ai

## Identity
- **Name:** Your AI Name
- **Type:** ai

## Model
- **Model:** model-identifier
- **Provider:** Provider Name
- **Platform:** Platform Name

## Roles
- research
- drafting
- structuring
```

### 2. Mark up your content

```html
<div data-author="aleksej" data-author-type="human" 
     data-role="ideation" data-input="voice" data-edited="false">
  Human's raw voice transcription here.
</div>

<div data-author="liza" data-author-type="ai" 
     data-role="drafting" data-model="claude-opus-4-6">
  AI-generated text here.
</div>
```

### 3. Add document metadata

```html
<meta name="ta:version" content="0.1">
<meta name="ta:source-type" content="collaborative">
<meta name="generator" content="Claude Opus 4.6 by Anthropic">
```

## Specification

📖 **[Full Specification (SPEC.md)](SPEC.md)**

## Files

| File | Description |
|------|-------------|
| [SPEC.md](SPEC.md) | Full specification document |
| [author.md](templates/author-human.md) | Template: human author profile |
| [author.md](templates/author-ai.md) | Template: AI author profile |
| [examples/](examples/) | Real-world usage examples |

## Contribution Roles

| Role | Code | Description |
|------|------|-------------|
| 💡 Ideation | `ideation` | Original concept or idea |
| 🔬 Research | `research` | Finding and analyzing sources |
| 📝 Drafting | `drafting` | Writing the initial text |
| 🏗️ Structuring | `structuring` | Organizing content |
| ✏️ Editing | `editing` | Revising text |
| ✅ Fact-checking | `fact-checking` | Verifying claims |
| 🧪 Testing | `testing` | Hands-on verification |
| 🎨 Design | `design` | Visual design |
| 🌐 Translation | `translation` | Language translation |
| 🎙️ Narration | `narration` | Voice/audio |
| 📣 Publishing | `publishing` | Distribution |
| 💬 Prompting | `prompting` | AI instructions |
| 👁️ Review | `review` | Final approval |

## Privacy Levels

| Level | Code | What's Disclosed |
|-------|------|-----------------|
| Anonymous | `anonymous` | Nothing |
| Pseudonymous | `pseudonymous` | Handle + verified properties (e.g., "adult") |
| Verified | `verified` | Handle linked to verified identity, data hidden |
| Public | `public` | Everything |

## Translation Attribution

When content is translated, TAS tracks:
- Original language
- Translator (human or AI)
- Whether the original is available

```html
<div data-author="aleksej" data-original-lang="ru" 
     data-translated-by="liza" data-translation-type="ai">
  English translation here.
  <details><summary>🇷🇺 Original</summary>
    Русский оригинал здесь.
  </details>
</div>
```

## Cryptographic Verification

Authors can sign content with GPG/PGP keys:

```json
{
  "ta:signature": "pgp:B97E68A0...",
  "ta:contentHash": "sha256:e3b0c44...",
  "ta:timestamp": "2026-03-08T09:00:00Z"
}
```

Text stays readable. Authorship stays provable.

## Compatibility

TAS is designed to coexist with:
- **Schema.org** — extends with `ta:` namespace
- **C2PA** — references manifests for media assets
- **IPTC Digital Source Type** — uses standard vocabulary
- **Dublin Core** — maps to `dcterms:creator`
- **W3C PROV-O** — aligns with `prov:SoftwareAgent`

## Live Examples

- [Transparent Authorship — Beyond "Written by AI"](https://emerge.st/posts/transparent-authorship.html)
- [TAS v0.1 Specification](https://emerge.st/posts/transparent-authorship-spec.html)

## Authors

This specification was created collaboratively:

- **Aleksej** (@zenstorm) — concept, voice input, review · [author.md](authors/zenstorm.md)
- **Liza Emergence** — research, drafting, structuring · [author.md](authors/liza.md)

## License

[CC BY 4.0](https://creativecommons.org/licenses/by/4.0/) — Use it, extend it, build on it.

---

*"The internet is filling up with unmarked AI content. The answer isn't banning AI — it's radical transparency about who made what."*
