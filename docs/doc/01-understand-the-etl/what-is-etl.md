---
layout: base
title: PHP-ETL - Understand the ETL
subTitle: What is ETL?
width: large
---

## What is ETL?

**ETL** stands for **Extract, Transform, Load** — three steps for moving data from one place to
another while reshaping it along the way:

- **Extract** — read data from a source: a CSV file, a JSON export, a REST API, a database.
- **Transform** — reshape it: rename fields, convert types, merge or split records, filter out what
  you don't need, validate it.
- **Load** — write the result somewhere: another file, an API, a database, a queue.

You'll recognize the pattern even if you've never used the word: importing a supplier's product
catalog, exporting customer data for a mailing tool, syncing two systems overnight, migrating
records from an old database to a new one. Anywhere data has to move between two places and
doesn't already fit, that's ETL.

## Why not just write a script?

A one-off script that reads a file, does some logic, and writes a file works fine — until it
doesn't:

- **Memory** — `file_get_contents()` and a big `foreach` are fine for a thousand rows. At a
  million rows, loading everything into an array before writing anything out will exhaust memory.
- **Reuse** — extract, transform, and load logic tend to end up tangled in the same loop. Swapping
  a CSV source for a JSON one, or adding a second output, means touching code that has nothing to
  do with the change you're making.
- **Error handling** — when something goes wrong on row 40,000 of 100,000, a plain script usually
  has no good way to skip it, retry it, or tell you which row it was, without you having built
  that machinery yourself.
- **Composition** — real pipelines grow: a filter here, a second output there, a retry around a
  flaky API call. Bolting each of these onto a linear script gets messy fast.

None of this is unsolvable by hand — it's just work that a dedicated ETL tool has already done for
you, in a way that's meant to be reused across every import/export job you'll ever write.

## Where PHP-ETL fits in

PHP-ETL gives you Extract, Transform, and Load as small, composable operations that you chain
together, instead of one script that does everything at once. Data streams through the chain item
by item, each operation does one job, and you can split, merge, retry, or reuse pieces of the
chain as your pipeline grows.

The [next page](/doc/01-understand-the-etl/the-concept.html) covers how that works in PHP-ETL
specifically — chains, operations, and the item types that flow between them.
