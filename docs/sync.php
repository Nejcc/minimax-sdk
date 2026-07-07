<?php

declare(strict_types=1);

/*
 * Regenerate the markdown embedded in index.html from minimax-sdk.md.
 *
 * index.html is standalone (it reads its docs from an inline <script> block so
 * it works off file://), which means the prose lives in two places. This keeps
 * them in sync: edit minimax-sdk.md, then run `php docs/sync.php`.
 */

$dir = __DIR__;
$html = file_get_contents("{$dir}/index.html");
$md = file_get_contents("{$dir}/minimax-sdk.md");

if (str_contains($md, '</script>')) {
    fwrite(STDERR, "minimax-sdk.md contains </script>, which would break the embed. Aborting.\n");
    exit(1);
}

$open = '<script type="text/markdown" id="doc-source">';
$start = strpos($html, $open);
if ($start === false) {
    fwrite(STDERR, "Could not find the doc-source block in index.html.\n");
    exit(1);
}

$contentStart = $start + strlen($open);
$end = strpos($html, '</script>', $contentStart);

$html = substr($html, 0, $contentStart)."\n".$md."\n".substr($html, $end);
file_put_contents("{$dir}/index.html", $html);

echo "Synced index.html from minimax-sdk.md.\n";
