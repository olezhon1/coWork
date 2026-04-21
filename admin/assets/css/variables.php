<?php
// assets/css/variables.php  — повертає рядок CSS :root{}

$design = require __DIR__ . '/../../config/design.php';

$css = ":root {\n";
foreach ($design as $tokens) {
    foreach ($tokens as $prop => $val) {
        $css .= "  {$prop}: {$val};\n";
    }
}
$css .= "}\n";
return $css;
