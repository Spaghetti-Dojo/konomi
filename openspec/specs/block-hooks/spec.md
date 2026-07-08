# block-hooks Specification

## Purpose

Defines how the `konomi/konomi` block is auto-inserted after the post title via WordPress Block Hooks, and how such hooked instances are populated with their default inner blocks so they render on the front-end. WordPress inserts a *bare* hooked block (it does not apply the editor inner-blocks template), which would otherwise render empty; the `hooked_block_konomi/konomi` filter injects the same `core/group → reaction + bookmark` structure the editor defines, so a hooked instance renders — and stays editable — exactly like a hand-placed one.

## Requirements

### Requirement: Konomi block declares an after-post-title block hook
The `konomi/konomi` block SHALL declare, in its `block.json`, a `blockHooks` entry auto-inserting itself `after` the `core/post-title` block.

#### Scenario: Block hook is registered for core/post-title
- **WHEN** WordPress resolves registered block hooks after block registration
- **THEN** `get_hooked_blocks()['core/post-title']['after']` SHALL include `konomi/konomi`

### Requirement: Hooked instances receive default inner blocks via filter
The system SHALL provide `Konomi\HookedContent::injectDefaultInnerBlocks(mixed $parsedHookedBlock, string $hookedBlockType, string $relativePosition, mixed $parsedAnchorBlock, mixed $context)` as the callback for the `hooked_block_konomi/konomi` filter, which populates a bare hooked `konomi/konomi` instance with its default inner blocks.

#### Scenario: Bare hooked block is populated
- **WHEN** the filter receives a parsed `konomi/konomi` array with an empty `innerBlocks`
- **THEN** `innerBlocks` SHALL resolve to a `core/group` containing `konomi/reaction` and `konomi/bookmark`
- **AND** `innerContent` and `innerHTML` SHALL be populated from the same default markup
- **AND** any incoming `attrs` on the parsed block SHALL be preserved

#### Scenario: Prior filter suppressed insertion
- **WHEN** the filter receives a `$parsedHookedBlock` that is not an array (e.g. `null`, because a prior filter suppressed insertion)
- **THEN** it SHALL return the value unchanged

#### Scenario: Instance already has inner blocks
- **WHEN** the filter receives a parsed `konomi/konomi` array whose `innerBlocks` is non-empty (e.g. a hand-placed block, or one already populated by another filter)
- **THEN** it SHALL return the block unchanged, performing no override

### Requirement: Default inner markup mirrors the editor template
The default inner-block markup injected by `HookedContent` SHALL mirror the editor inner-blocks template defined in `sources/Blocks/Konomi/edit/index.tsx`: a `core/group` with a flex, no-wrap layout (`{"layout":{"type":"flex","flexWrap":"nowrap"}}`) wrapping `konomi/reaction` followed by `konomi/bookmark`.

#### Scenario: Default markup structure
- **WHEN** `HookedContent` builds the default inner blocks
- **THEN** the outer inner block SHALL be `core/group` with layout `{"type":"flex","flexWrap":"nowrap"}`
- **AND** it SHALL contain `konomi/reaction` and `konomi/bookmark` in that order
- **AND** this structure SHALL match the `useInnerBlocksProps` template in `edit/index.tsx`

### Requirement: Module wiring registers and applies the filter
`Blocks\Module` SHALL register `Konomi\HookedContent` in `services()` and SHALL add the `hooked_block_konomi/konomi` filter in `run()` via `initBlockHooks()`, at priority `10` with `5` accepted arguments, mirroring the `ConditionalBlockRender` wiring.

#### Scenario: HookedContent is a registered service
- **WHEN** the Blocks module registers services
- **THEN** `Konomi\HookedContent::class` SHALL resolve to a `Konomi\HookedContent` instance

#### Scenario: Filter is added during run
- **WHEN** `Blocks\Module::run()` executes
- **THEN** `initBlockHooks()` SHALL add the `hooked_block_konomi/konomi` filter bound to `HookedContent::injectDefaultInnerBlocks` at priority `10` accepting `5` arguments

### Requirement: Hooked Konomi block renders after the post title
When post-title content is passed through the Block Hooks pipeline and rendered, the resulting front-end output SHALL contain the reaction and bookmark markup after the post title.

#### Scenario: End-to-end front-end render
- **WHEN** `apply_block_hooks_to_content('<!-- wp:post-title /-->', $post)` is applied and its output is passed through `do_blocks()`
- **THEN** the rendered output SHALL contain `konomi-reaction` and `konomi-bookmark`
- **AND** they SHALL appear after the rendered post title
