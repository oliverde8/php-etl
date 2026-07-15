# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer install                              # install dependencies (requires PHP >=8.3)
./vendor/bin/phpunit -c phpunit.xml.dist       # run the full test suite
./vendor/bin/phpunit --filter TestMethodName   # run a single test
./vendor/bin/rector process --dry-run          # check rector suggestions (src/ and examples/)
```

Tests live under `src/*/*/*/Tests` (e.g. `src/Oliverde8/Component/PhpEtl/Tests`, `src/Oliverde8/Component/RuleEngine/Tests`), matching the `testsuites` path in `phpunit.xml.dist` — new tests must follow that same nesting to be picked up.

There is no separate lint/static-analysis command configured beyond Rector.

## Architecture

This library has two independent components under `src/Oliverde8/Component/`:

- **PhpEtl** — the ETL chain engine (extract/transform/load pipelines).
- **RuleEngine** — a standalone rule-based data transformation engine (`Rules/`), used by PhpEtl's rule-transformer operation but usable on its own.

### Two coexisting chain-configuration paradigms (important)

PhpEtl 2.0 replaced the original YAML/array-driven configuration with typed PHP configuration objects. Both are present in the codebase simultaneously:

- **Old (deprecated, still supported)**: `ChainBuilder` (`src/Oliverde8/Component/PhpEtl/ChainBuilder.php`) consumes plain arrays (typically parsed from YAML). Operations are registered as `AbstractFactory` subclasses under `Builder/Factories/`, looked up by a string `operation` key. Instantiating `ChainBuilder` triggers a runtime deprecation notice. These factories are adapters: internally they now build the *new* `*Config`/operation classes, so operation logic itself is not duplicated.
- **Current**: `ChainBuilderV2` (`src/Oliverde8/Component/PhpEtl/ChainBuilderV2.php`) consumes a `ChainConfig` built by chaining `->addLink(new SomeOperationConfig(...))` calls. Operations are registered via `GenericChainFactory` (`src/Oliverde8/Component/PhpEtl/GenericChainFactory.php`), a single reflection-based factory: given an operation class + its matching `OperationConfigInterface` class, it auto-wires the operation's constructor (config, flavor, nested `ChainBuilderV2` for sub-chains, and named `injections`).

Each operation has a matching pair: an `OperationConfig/**` class (typed parameters, implements `OperationConfigInterface`) and a `ChainOperation/**` class (the actual logic, implements `ConfigurableChainOperationInterface`). When adding or changing an operation, both sides need updating.

The two builders are **separate registries** — a YAML-declared chain and a `ChainConfig`-declared chain can't embed each other directly.

### Flavor

Operation configs carry a `flavor` string (default `'default'`) so multiple `GenericChainFactory` registrations can share the same config/operation class — e.g. one factory per Flysystem storage adapter, selected at config-construction time.

### Chain execution flow

Both builders produce a chain processor that pushes `ItemInterface` instances (`Item/`: `DataItem`, `StopItem`, `FileItem`, `GroupedItem`, `ChainBreakItem`, etc.) through a sequence of operations. `ExecutionContext` (`Model/ExecutionContext.php`) carries shared state/resources through a single chain execution. Composite operations (`ChainSplitOperation`, `ChainMergeOperation`, `ChainRepeatOperation`, `FailSafeOperation`) wrap nested `ChainConfig`s and recurse into the builder to construct sub-chains.

`ChainSplitOperationV1` / `ChainRepeatOperationV1` are kept alongside the current versions purely to preserve old-paradigm (YAML) behavior — don't modify them when changing the current split/repeat operations.

### Docs site

`docs/` is a Jekyll site (published to the project's GitHub Pages domain) with source pages under `docs/doc/**` and navigation in `docs/_includes/menu.html` — any new doc page needs a corresponding link added there. `old-docs/` is pre-2.0 reference material, not maintained. `examples/` has paired old/new demonstrations for the same scenarios (e.g. `examples/01-SimpleYamlCases/` vs `examples/00-SimpleCases/`), useful as a source of truth for before/after documentation.
