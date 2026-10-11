<?php
// Turns Markdown docs into styled A4 PDFs (GitHub-flavoured tables work) using
// the dompdf + league/commonmark packages already in vendor/.
//
// Usage (run from the project root):
//   php docs/tools/make-pdf.php . docs "whatsapp-team-inbox|docs/whatsapp-team-inbox.md"
//   php docs/tools/make-pdf.php . docs/full-documentation //        "seller-guide|docs/full-documentation/seller-guide.md" //        "user-guide|docs/full-documentation/user-guide.md" //        "developer-guide|docs/full-documentation/developer-guide.md"
// Arguments: <project-root> <output-dir> then one or more "<pdf-name>|<markdown-file>".
// Emoji are dropped on purpose (the PDF font cannot draw them).

use Dompdf\Dompdf;
use Dompdf\Options;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

$root = $argv[1];
$out  = $argv[2];
require $root . '/vendor/autoload.php';

$env = new Environment(['html_input' => 'strip', 'allow_unsafe_links' => false]);
$env->addExtension(new CommonMarkCoreExtension());
$env->addExtension(new GithubFlavoredMarkdownExtension());
$converter = new MarkdownConverter($env);

$css = <<<CSS
@page { margin: 22mm 18mm 20mm 18mm; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9.6pt; line-height: 1.5; color: #1f2933; }
h1 { font-size: 20pt; color: #0b6b4f; border-bottom: 2px solid #25D366; padding-bottom: 6px; margin: 0 0 14px; }
h2 { font-size: 14pt; color: #0b6b4f; margin: 22px 0 8px; page-break-after: avoid; }
h3 { font-size: 11.5pt; color: #1f2933; margin: 16px 0 6px; page-break-after: avoid; }
p { margin: 0 0 8px; }
ul, ol { margin: 0 0 8px 0; padding-left: 18px; }
li { margin-bottom: 3px; }
code { font-family: DejaVu Sans Mono, monospace; font-size: 8.6pt; background: #eef2f5; padding: 1px 3px; }
pre { background: #eef2f5; padding: 8px; font-size: 8.4pt; white-space: pre-wrap; }
table { border-collapse: collapse; width: 100%; margin: 8px 0 12px; font-size: 8.8pt; }
th { background: #e8f5e9; color: #0b6b4f; text-align: left; }
th, td { border: 1px solid #cfd8dc; padding: 4px 6px; vertical-align: top; }
hr { border: 0; border-top: 1px solid #cfd8dc; margin: 14px 0; }
blockquote { margin: 8px 0; padding: 4px 10px; border-left: 3px solid #25D366; color: #52606d; }
CSS;

foreach (array_slice($argv, 3) as $spec) {
    [$name, $file] = explode('|', $spec, 2);
    $md = file_get_contents($file);
    // dompdf's fonts have no emoji — drop them (and variation selectors) so no tofu boxes appear.
    // Symbols with a plain-text stand-in are swapped first, then the rest are removed.
    $md = str_replace(['☐', '☑', '✕', '⚠'], ['[ ]', '[x]', 'x', '!'], $md);
    $md = preg_replace('/[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $md);
    $html = $converter->convert($md)->getContent();

    $doc = '<!doctype html><html><head><meta charset="utf-8"><style>' . $css . '</style></head><body>' . $html . '</body></html>';

    $opts = new Options();
    $opts->set('isRemoteEnabled', false);
    $opts->set('defaultFont', 'DejaVu Sans');
    $pdf = new Dompdf($opts);
    $pdf->loadHtml($doc, 'UTF-8');
    $pdf->setPaper('A4');
    $pdf->render();

    // page numbers
    $canvas = $pdf->getCanvas();
    $font = $pdf->getFontMetrics()->getFont('DejaVu Sans');
    $canvas->page_text(500, 820, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, [0.4, 0.4, 0.4]);

    file_put_contents($out . '/' . $name . '.pdf', $pdf->output());
    echo $name . '.pdf  ' . round(filesize($out . '/' . $name . '.pdf') / 1024) . " KB\n";
}
