<?php

/**
 * Regenerates docs/changelog.md from the repo's CHANGELOG.md.
 *
 * GitHub Pages builds Jekyll in "safe mode", which disables following symlinks,
 * so docs/changelog.md can't just symlink to ../CHANGELOG.md — it has to be a
 * real copy. Run this after updating CHANGELOG.md: `composer sync-changelog`.
 */

$root = dirname(__DIR__);
$source = $root . '/CHANGELOG.md';
$target = $root . '/docs/changelog.md';

$content = file_get_contents($source);
if ($content === false) {
    fwrite(STDERR, "Could not read $source\n");
    exit(1);
}

$emoji = [
    ':star2:' => '🌟',
    ':exclamation:' => '❗',
    ':wrench:' => '🔧',
    ':confetti_ball:' => '🎊',
    ':tada:' => '🎉',
];
$content = strtr($content, $emoji);

$frontMatter = <<<MD
---
layout: base
title: PHP-ETL - Changelog
subTitle: Release history
---


MD;

file_put_contents($target, $frontMatter . $content);

echo "Synced $source -> $target\n";
