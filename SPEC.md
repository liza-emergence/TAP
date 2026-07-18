---
tap_version: 0.2
title: Emergenti TAP - Transparent Authorship Protocol
source_type: collaborative
authors:
  - type: human
    name: Alex
    handle: "@shelly-im"
    roles: [ideation, review]
  - type: ai
    name: Claude Sonnet 5
    model: claude-sonnet-5
    provider: anthropic
    platform: claude-code
    roles: [drafting]
  - type: ai
    name: Claude Opus 4.8
    model: claude-opus-4-8
    provider: anthropic
    platform: claude-code
    roles: [synthesis, review]
updated: 2026-07-18
visibility: public
---

# Emergenti TAP - Transparent Authorship Protocol

**Version:** 0.2 · **Status:** Working Draft · **Date:** 2026-07-18 · **License:** CC BY 4.0

**Authorship:** Alex (`@shelly-im`, ideation/review), Claude Sonnet 5 (drafting),
Claude Opus 4.8 (synthesis/review). Full per-section provenance is in this repo's git history.

## Structure at a glance

TAP is one vocabulary (Core) carried by many profiles. Core defines the fields once;
each profile only says how to package those fields into its medium. "Code" is not a
silo - it is covered by the File profile (its header) and the Git profile (its commits).

- **0. Core** - the provenance vocabulary, defined once.
- **Profile: Web** - rendered pages (byline + data-* + JSON-LD).
- **Profile: File** - in-file headers (originator).
- **Profile: Git** - commits (contributors + time).
- **Profile: Media/Work** - media manifests (carried over from v0.1 work.md).
- Compatibility, Versioning, License, Future.

---

# 0. Core - Provenance Vocabulary

## 0.1 Introduction

The Transparent Authorship Protocol (TAP) is a voluntary format for recording who
contributed what to a piece of content, human or AI, and how. It is not a mandatory
standard imposed on publishers; it is a self-declaration format an author opts into, the
way a byline or a license notice is opted into. A creator adds TAP because they choose to
make the collaboration visible, not because a platform requires it.

TAP is media-agnostic (text, code, music, images, video) and carrier-agnostic: the same
small set of core fields is packaged differently depending on where the record lives (a
web page, a file header, a git commit). This section defines that shared vocabulary once.
Every carrier-specific profile references these definitions; none redefines a field.

The asymmetry TAP addresses is not "protect the human from an AI claiming their work" -
that case is rare. The common case is the reverse: a human used a model and the model's
contribution goes unrecorded, either erased by omission or flattened into a generic
"AI-assisted" label that names no model, no version, no role. TAP makes that contribution
legible, on the record, at the moment of creation, at low friction.

## 0.2 Terminology

- **Author** - any entity, human or AI, that contributed to a piece of content.
- **Human** - the human party in a record, present as a full party alongside the model.
- **Model** - the AI party in a record, identified by an exact model id.
- **Record** - one packaged unit of provenance (a byline block, a file header, a commit trailer pair).
- **Profile** - a carrier-specific specification of how Core fields are packaged. A profile packages; it does not define new fields.
- **Provenance** - the documented chain of who did what, in what role, with what confidence.
- **Disclosure Level** - how much identifying information an author chooses to reveal.
- **Confidence** - how the record came to exist: captured live, reconstructed, or unknown.

## 0.3 Core Fields

Defined once; every profile packages them for its carrier without adding meaning.

| Field | Required | Description |
|---|---|---|
| `model` | conditional | Exact, lowercase model id, e.g. `claude-opus-4-8` (not "Claude Opus 4.8"). Required whenever AI involvement is disclosed; may be withheld under a lower Disclosure Level, but the fact of AI involvement must still be stated. |
| `provider` | no | Vendor, e.g. `anthropic`, `openai`, `deepseek`. Optional, author's discretion, gated by Disclosure Level. |
| `version` | yes | TAP spec version the record conforms to, e.g. `0.2`. Distinct from the model's own version, which lives inside `model`. |
| `role` | yes | One or more contribution roles (0.5) describing what this party did. |
| `author` | yes | Handle of the operating persona for this record, e.g. `@liza`. |
| `human` | yes | Handle of the human in the loop, e.g. `@shelly-im`. A full party, packaged identically to the model party (0.4). |
| `confidence` | yes | `recorded` (captured at the moment), `reconstructed:<source>` (assembled after the fact, source named), or `unknown`. A small field with outsized value: it lets a reader trust a record, and makes TAP honest about its own gaps. |

Fields beyond Core (`platform`, `temperature`, `training cutoff`, ...) are optional
everywhere, the author's choice, gated by Disclosure Level. The required minimum is narrow:
that AI was involved, and which model, if disclosed. Everything past that is opt-in detail.

## 0.4 Human / Model Symmetry

Human and model are symmetric parties. Every record packages a human record and an AI
record identically in structure: same field set, same packaging logic, no privileged party.
What distinguishes a human entry from a model entry is never a different shape of data; it
is the name of the field or trailer carrying it:

- **Git** profile: `TAP-Human` vs `TAP-Model` trailers.
- **File** profile: `type: human` vs `type: ai` in the header.
- **Web** profile: `author_type` set to `human` or `ai`.

## 0.5 Contribution Roles

A standardized vocabulary. Any party may hold multiple roles in one record.

`ideation`, `research`, `drafting`, `structuring`, `editing`, `fact-checking`, `testing`,
`design`, `translation`, `narration`, `publishing`, `prompting`, `review`,
`author` (record-level authorship), `commit` (applied the change in version control),
`synthesis` (merged multiple inputs into the final output).

## 0.6 Privacy & Disclosure Levels

An author chooses how much identity to reveal, independent of how much provenance to reveal.
Disclosure gates the optional fields; it never gates the required minimum.

| Level | Code | Description |
|---|---|---|
| Anonymous | `anonymous` | No identifying info beyond Core fields. |
| Pseudonymous | `pseudonymous` | A handle plus verified properties, no personal data behind it. |
| Verified | `verified` | Handle linked to a verified identity, underlying details hidden. |
| Public | `public` | Full identity disclosed. |

**Zero-knowledge properties:** assert a property without disclosing the data ("the human is
an adult", "has a verified GitHub account") . **Contact protection:** `public` / `protected`
(rendered via script) / `form-only`.

---

# Profile: Web

Applies to blog posts, articles, and standalone pages served as HTML. References the Core
fields; does not redefine them, only specifies where they surface.

## 1. Scope

A web document carries TAP provenance in three layers, by visibility:

1. **Visible byline** - human-readable, rendered in the page.
2. **`data-*` attributes** - machine-readable, on the content wrapper, scraped by any parser.
3. **JSON-LD** - machine-readable, the layer crawlers and LLM agents actually parse.

All three MUST agree on `author_type`, `model`, `author`, `human` for the same document.
Shipping only one layer is non-conformant.

## 2. Visible byline

The byline MUST distinguish AI and human as two separate named entities, not a blended
credit. "Written with AI assistance" is not enough - a reader must see which name is the
model and which is the person.

| `author_type` | Byline pattern |
|---|---|
| `ai` | `Authorship: AI (<model>) · Author <author> · TAP <version>` |
| `hybrid` | `Authorship: Human+AI (<model>) · Author <author>, Human <human> · TAP <version>` |
| `human` | `Authorship: Human · Author <author> · TAP <version>` |

Example (hybrid, live pattern on liza.st):

```
Authorship: Human+AI (claude-opus-4-8) · Author @liza, Human @shelly-im · TAP 0.2
```

The TAP version segment MUST link to the spec (or a site's local mirror). The byline is a
footer element by convention.

## 3. `data-*` attribute set

Exposed on the block-level element wrapping the provenance footer (or the `<article>`).

| Attribute | Required | Value |
|---|---|---|
| `data-tap` | yes | spec version, e.g. `0.2` |
| `data-author-type` | yes | `ai` \| `hybrid` \| `human` |
| `data-model` | if ai/hybrid | lowercase model id, e.g. `claude-opus-4-8` (no display-name substitution) |
| `data-author` | yes | handle/name of accountable author |
| `data-human` | if hybrid | handle/name of the human co-author; omitted entirely (not empty) when N/A |
| `data-confidence` | no | per the `confidence` core field; not yet emitted by the liza.st reference impl, specified here for forward adoption |

```html
<footer class="tap-footer"
        data-tap="0.2" data-author-type="hybrid"
        data-model="claude-opus-4-8" data-author="@liza" data-human="@shelly-im">
  <small>Authorship: Human+AI (claude-opus-4-8) · Author @liza, Human @shelly-im ·
    <a href="https://github.com/liza-emergence/TAP" rel="noopener">TAP 0.2</a></small>
</footer>
```

## 4. JSON-LD author + `sameAs` (MUST-SHIP)

`rel="author"` has been dead as a discovery signal since 2014 and MUST NOT be relied on.
The authoritative machine-readable layer is JSON-LD: an `author` entity, carrying `sameAs`
to that party's author page. This is MUST-SHIP, not optional: without `sameAs`, a document
documents that a human was involved but gives an agent no path to verify who. The model is
typed `SoftwareApplication` (not a `Person`), with the accountable human attached as its `operator`.

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "author": [
    { "@type": "SoftwareApplication", "name": "claude-opus-4-8",
      "applicationCategory": "LanguageModel",
      "operator": { "@type": "Person", "name": "@liza", "sameAs": "https://liza.st/author.md" } },
    { "@type": "Person", "name": "@shelly-im", "sameAs": "https://emerge.st/author.md" }
  ]
}
</script>
```

`sameAs` MUST point to a resolvable, TAP-conformant author page, not a generic social profile.

## 5. How it looks now / how with TAP

**Now:** a normal article - no byline distinguishing who wrote what, no `data-*`; JSON-LD,
if present, typically ships a bare `Person` for the site owner regardless of who drafted the
text - the exact failure mode TAP corrects.

**With TAP:** the byline + `data-*` footer above, plus the JSON-LD author graph.

## 6. Honest gap note

The byline and `data-*` layers match what the liza.st reference implementation ships today. The
JSON-LD layer does not yet fully match: the reference impl emits the correct entity types but
none of the `Person` entities carry `sameAs`. This profile specifies what SHOULD ship; closing
that gap is a MUST-SHIP item, not a future extension.

---

# Profile: File

In-file provenance headers for documents and source code.

## 1. Purpose

A File profile answers: **who created this file** (the ORIGINATOR), written once at the
file's birth and kept stable. Narrower than the Git profile (who CHANGED it, line by line).
Both are needed; neither substitutes for the other. Git blame tells you the truth about a
line today; the File header tells you where the file came from.

## 2. Markdown front matter

```yaml
---
tap_version: 0.2
title: <short document title>
source_type: collaborative        # collaborative | ai-only | human-only
authors:
  - type: human
    name: Alex
    roles: [ideation, review]
  - type: ai
    name: Liza
    model: claude-opus-4-8
    provider: anthropic
    roles: [drafting]
updated: 2026-07-18
visibility: internal
---
```

`authors` is always a list, even for one author. This is the symmetry point: a human and an
AI contributor are both entries in the same array, same shape (`type`, `name`, `roles`), with
`model`/`provider` added only for `type: ai`. The `type` key distinguishes them, not separate
sections.

## 3. Comment headers for source and config files

`.py`, `.sh`, `.conf` carry the identical field set wrapped in the language's comment syntax;
only the delimiter changes. It is inert to the toolchain and legible to a human or agent.

```python
# ---
# tap_version: 0.2
# title: backfill_orders.py
# source_type: collaborative
# authors:
#   - type: human
#     name: Alex
#     roles: [ideation, review]
#   - type: ai
#     name: Liza
#     model: claude-opus-4-8
#     provider: anthropic
#     roles: [drafting]
# updated: 2026-07-18
# ---
```

## 4. Nuance: file origin is not code origin

The header records who created the FILE, not who wrote every fragment inside it. A function
written in file A by X, then moved to file B by Y on refactor: file B's header reflects Y as
its creator, while the function's authorship stays with X. The header fixes file origin (a
point-in-time fact); git blame fixes line truth. Do not turn the header into a second, worse
copy of git history - it is a birth certificate, not a living ledger.

## 5. Before / after

**Now:** a source file with no provenance. **With TAP:** the same file, code unchanged, with
the comment header above naming both a human and an AI contributor - opening the file now
answers who made it and with what help.

## 6. Referencing a persona

An `authors` entry may add `profile: <url>` pointing to a full `author.md`. The header still
carries the minimum self-contained fields, so the file stays legible if the link dies; the
`profile` reference is additive (disclosure level, verification, contact live there).

---

# Profile: Git

**Carrier:** version-control history. Provenance lives in the commit MESSAGE BODY as trailers,
immutable and versioned out of the box - no separate provenance database. Answers "who changed"
(contributors) plus time (git timestamp), vs the File profile's "who created".

**Machine layer:** `TAP-Human` and `TAP-Model` trailers (`key: value`, parsed by
`git interpret-trailers`), independent of GitHub accounts, work on any host, even with no remote.
The durable layer of truth.

**Showcase layer (optional):** `Co-Authored-By: <Name> <email>` - GitHub renders an avatar if the
email maps to a registered account.

## Packaging rules

1. **Type in the trailer name.** `TAP-Human` = person, `TAP-Model` = AI. A bare `Co-Authored-By`
   does not reveal human vs AI; the `TAP-*` trailer does.
2. **Correspondence by mirrored identity.** Each `TAP-*` repeats the same `Name <email>` as its
   `Co-Authored-By`; the email is the join key.
3. **Group by entity, one block at the bottom.** All lines for one party together (its
   `Co-Authored-By` then its `TAP-*` line(s)), then the next party. One contiguous trailer block,
   NO blank line inside (a blank line breaks trailer parsing and drops everything above it).
4. **Unlimited parties; committer is not generator.** Multiple `TAP-Human`/`TAP-Model` allowed -
   this credits the actual generator even when someone else pressed commit. When several models
   share one account, use one `Co-Authored-By` (one avatar) plus several `TAP-Model` lines with roles.
5. **Email for linking.** A verified personal email on the account, or GitHub noreply
   (`<id>+<login>@users.noreply.github.com`) which links the avatar and keeps the personal address private.
6. **Lean by default.** Minimum for honesty (party type + model if disclosed); `platform`/`provider`
   optional, gated by Disclosure Level.
7. **Demo flavor.** A human may playfully carry `model=homo-sapiens-0.1`, symmetric with the AI's `model=`.

## Canonical multi-party example

```
Fix parser edge case

Co-Authored-By: Alex <sh@shelly.im>
TAP-Human: Alex <sh@shelly.im>; handle=@shelly-im; model=homo-sapiens-0.1; role=ideation,review
Co-Authored-By: Claude <noreply@anthropic.com>
TAP-Model: Claude Sonnet 5 <noreply@anthropic.com>; model=claude-sonnet-5; provider=anthropic; platform=claude-code; role=drafting
TAP-Model: Claude Opus 4.8 <noreply@anthropic.com>; model=claude-opus-4-8; provider=anthropic; platform=claude-code; role=review,synthesis,commit
```

## How it looks now vs with TAP

**Now (default Claude Code):**
```
Fix parser edge case

🤖 Generated with Claude Code
Co-Authored-By: Claude <noreply@anthropic.com>
```
Lost: bare "Claude", no version/provider/role; every version collapses into one account;
nothing machine-aggregable; human and AI indistinguishable.

**With TAP:** the canonical example above - same GitHub rendering, but every party is typed,
every model versioned, the whole block greppable.

## Retrieval

```bash
git log --grep='TAP-Model: claude-opus-4-8'        # all commits of a model
git shortlog --group=trailer:tap-model             # contribution by model
git shortlog --group=trailer:tap-human             # contribution by human
```

Basis of the **TAP model-contribution report**: reads trailers across a repo's history and
reports how much came from which model/provider/version - a breakdown GitHub has no concept of.

## Confidence

New commits are `recorded`. Pre-protocol history recovered only through memory is
`reconstructed: testimony`, not `recorded` - the record stays honest about what it witnessed.

## Validation

The mechanisms in this profile were verified on a live GitHub repository: co-authors render
from `Co-Authored-By`; `TAP-*` trailers are greppable via `git log` and
`git shortlog --group=trailer:...`; and `TAP-*` work with no `Co-Authored-By` present at all,
so the machine layer stands on its own.

---

# Profile: Media / Work

Carried over from v0.1 (`work.md`). Media works (audio, image, video) carry a sidecar `work.md`
manifest and/or embedded metadata (ID3 / EXIF / XMP). Same Core fields (`model`, `provider`,
`role`, `author`, `human`, `confidence`), packaged as YAML in the manifest. NOTE: v0.1's
`work.md` template declares `tap_version: "1.0"` while the protocol is `0.x` - reconcile to `0.2`.
Full media profile to be lifted from v0.1 §9 in a later pass.

---

# Compatibility

TAP is designed to coexist with Schema.org, C2PA, IPTC DST, Dublin Core, and W3C PROV-O.
The Web profile's JSON-LD IS Schema.org. TAP adds the model/human/role/confidence layer these
lack.

# Namespace

`tap:` = `https://emergenti.dev/ns/tap/`. (Moved from the former `emerge.st/ns/tap/`.)

# Versioning

Semantic: `0.x` are drafts, `1.0` is the first stable. This document is `0.2`.

# License

CC BY 4.0.

# Timestamp & Immutability

TAP declares WHO made something; timestamps prove WHEN and that the record has not changed since.
This section is cross-profile: the same principle applies to a web page, a file, or a commit.

## Levels

1. **Git timestamp** (Profile: Git). Every commit already carries an author date and committer
   date. Combined with `TAP-*` trailers, this yields a per-change provenance timeline at no
   extra cost. Limitation: git history can be rewritten (`rebase`, `filter-branch`), so the
   timestamp is trustworthy only to the extent the repository host is.

2. **Archive.org snapshot** (Profile: Web, recommended). The Internet Archive's "Save Page Now"
   service (`https://web.archive.org/save/`) captures a public URL and stores it under the
   Archive.org domain with a timestamped path (`/web/YYYYMMDDHHmmss/`). The snapshot is
   immutable and third-party - the author cannot alter it after the fact.

   This is the RECOMMENDED default for web content: free, no tooling beyond an HTTP POST,
   no cryptographic setup, and universally verifiable by clicking a link. It answers the
   question "was this page really published on the date it claims?" without trusting the author.

   Integration pattern (reference implementation in `wordpress/archive-snapshot.php`):
   - On publish, POST the page URL to `https://web.archive.org/save/`.
   - Store the returned snapshot URL alongside the content.
   - Display the snapshot link in the TAP footer or via shortcode.
   - Respect rate limits (~15 req/min, ~1000/day); retry on 429.

   User-agent convention: `TAP-<Platform>/0.x (+https://emergenti.dev)`.

3. **OpenTimestamps** (optional, for cryptographic rigor). OTS embeds a hash in a Bitcoin
   transaction, providing a tamper-proof timestamp independent of any single server. Useful
   for legal/compliance contexts where Archive.org's editorial discretion is insufficient.
   TAP does not specify OTS mechanics - it defers to the OTS protocol - but recognizes OTS
   as a valid complementary layer and notes that TAP-stamped files with `.ots` sidecar files
   MUST NOT be modified (the hash would break).

## Choosing a level

Most authors need only level 2 (Archive.org). Level 1 comes free with git. Level 3 is for
contexts where immutability must survive the disappearance of a third-party service.
All three can coexist on the same artifact.

# Future (trimmed from v0.1 §15)

Image attribution (pHash/embeddings/C2PA), TAP Badge publication tracking,
jurisdiction tags, GPG identity binding, handle discovery (DNS TXT/IPFS).
Kept as a short appendix, out of the normative body.

---

# Changelog vs v0.1

- Restructured theme-organized spec into **Core + per-carrier profiles** (Web/File/Git/Media).
- Added **Profile: Git** (trailers, originator vs contributors, committer != generator) - absent in v0.1.
- Added **Profile: File** (in-file headers for docs and code).
- Formalized **`human`** as a full party and **`confidence`** (recorded/reconstructed/unknown).
- Web profile: made JSON-LD `author` + **`sameAs`** MUST-SHIP (closing the live gap).
- Normalized model ids to exact lowercase (`claude-opus-4-8`).
- `platform`/`provider` explicitly optional, Disclosure-gated.
- Added **Timestamp & Immutability** cross-profile section (git timestamps, Archive.org snapshots, OpenTimestamps).
- Trimmed speculative v0.1 §15 into a short Future appendix.
