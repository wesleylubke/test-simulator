<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\CsvExamParser;
use App\ValidationException;

$parser = new CsvExamParser();
$parsedExam = null;
$errorMessage = null;
$successMessage = null;
$uploadedFileName = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['exam_file'])) {
            throw new ValidationException('Nenhum arquivo foi enviado.');
        }

        $file = $_FILES['exam_file'];

        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new ValidationException('Falha no upload do arquivo CSV.');
        }

        $uploadedFileName = (string) ($file['name'] ?? 'arquivo.csv');
        $extension = strtolower(pathinfo($uploadedFileName, PATHINFO_EXTENSION));

        if ($extension !== 'csv') {
            throw new ValidationException('Envie um arquivo com extensão .csv.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new ValidationException('Arquivo temporário de upload inválido.');
        }

        $parsedExam = $parser->parse($tmpName);
        $successMessage = 'Arquivo importado e validado com sucesso.';
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
    }
}

include __DIR__ . '/templates/home.php';