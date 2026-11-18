<?php
declare(strict_types=1);

function is_valid_uploaded_pdf(string $path): bool
{
    $contents = file_get_contents($path);
    if (!is_string($contents) || strlen($contents) < 20 ||
        !str_starts_with($contents, '%PDF-') ||
        !preg_match('~/Type\s*/Catalog\b~', $contents) ||
        !preg_match('~/Type\s*/Pages\b~', $contents) ||
        !preg_match('/startxref\s+(\d+)\s+%%EOF\s*\z/s', $contents, $matches)) {
        return false;
    }

    $xrefOffset = filter_var($matches[1], FILTER_VALIDATE_INT);
    if ($xrefOffset === false || $xrefOffset < 0 || $xrefOffset >= strlen($contents)) {
        return false;
    }
    $xref = substr($contents, $xrefOffset, 1024);
    if (preg_match('/\A\s*xref\b/', $xref)) return true;
    return preg_match('/\A\s*\d+\s+\d+\s+obj\b/', $xref) === 1 &&
        preg_match('~/Type\s*/XRef\b~', $xref) === 1;
}
