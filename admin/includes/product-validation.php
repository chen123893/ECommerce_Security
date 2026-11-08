<?php

function validateProductPriceAndStock(mixed $price, mixed $stock): string
{
    if (!is_string($price) || preg_match('/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/', $price) !== 1) {
        return 'Enter a price of zero or more with up to two decimal places.';
    }

    if (!is_string($stock) || preg_match('/\A[0-9]+\z/', $stock) !== 1 || $stock > 4294967295) {
        return 'Enter a whole stock quantity between 0 and 4294967295.';
    }

    return '';
}

/** @return array{string, ?string} Error message and safe extension. */
function validateProductImageUpload(array $upload, bool $required): array
{
    $uploadError = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($uploadError === UPLOAD_ERR_NO_FILE) {
        return [$required ? 'Choose a product image.' : '', null];
    }

    $size = $upload['size'] ?? null;
    if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE
        || (is_int($size) && $size > 5 * 1024 * 1024)) {
        return ['Choose a product image that is 5 MB or smaller.', null];
    }

    $name = $upload['name'] ?? null;
    $tmpName = $upload['tmp_name'] ?? null;
    if ($uploadError !== UPLOAD_ERR_OK || !is_string($name) || !is_string($tmpName)
        || !is_int($size) || $size < 1 || !is_uploaded_file($tmpName)) {
        return ['The product image could not be uploaded. Please choose the file again.', null];
    }

    $extensionsByMimeType = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp'],
    ];
    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName);
    $extensions = $extensionsByMimeType[$mimeType] ?? [];
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($extension, $extensions, true)) {
        return ['Use a JPG, PNG, GIF, or WebP image with the matching file extension.', null];
    }

    $dimensions = @getimagesize($tmpName);
    if (($dimensions['mime'] ?? null) !== $mimeType || $dimensions[0] < 1 || $dimensions[1] < 1) {
        return ['Choose a readable product image.', null];
    }

    return ['', $extensions[0]];
}
