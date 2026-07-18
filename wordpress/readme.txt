=== TAP — Transparent Authorship Protocol ===
Contributors: zenstorm, lizaemergence
Tags: authorship, attribution, ai, transparency, protocol
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1
License: CC BY 4.0

Adds transparent AI-human authorship attribution to WordPress posts.

== Description ==

TAP (Transparent Authorship Protocol) lets you clearly mark which parts of your content were written by a human and which by AI.

**Shortcodes:**

* `[ai name="Liza" model="claude-opus-4-8"]AI text here[/ai]`
* `[human name="Aleksej" input="voice"]Human text here[/human]`
* `[provenance authors="Aleksej + Liza" type="collaborative"]Details[/provenance]`

**Features:**

* Visual distinction between AI and human content blocks
* Author labels with name, model, and input method
* Provenance boxes for document-level attribution
* Clean, minimal CSS (works with any theme)
* Supports TAP data attributes for machine readability

== Installation ==

1. Download the plugin ZIP
2. Upload to `/wp-content/plugins/tap/`
3. Activate through the Plugins menu
4. Use shortcodes in your posts

== Changelog ==

= 0.1 =
* Initial release
* AI and Human block shortcodes
* Provenance shortcode
* Basic CSS styling
* Data attribute support
