<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\CsvExamParser;
use App\FirestoreRestRepository;
use App\GoogleAccessTokenService;
use App\ValidationException;

$parser = new CsvExamParser();

$exams = [];
$parsedExam = null;
$errorMessage = null;
$successMessage = null;
$uploadedFileName = null;
$examId = null;

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

        $credentialsPath = getenv('GOOGLE_APPLICATION_CREDENTIALS');
        if (!is_string($credentialsPath) || $credentialsPath === '') {
            throw new ValidationException('Variável GOOGLE_APPLICATION_CREDENTIALS não configurada.');
        }

        $tokenService = new GoogleAccessTokenService($credentialsPath);
        $repository = new FirestoreRestRepository($tokenService);

        $csvPath = 'upload/' . date('Y/m/d/') . basename($uploadedFileName);

        $examId = $repository->saveExam($parsedExam, $csvPath);
        $repository->saveQuestions($examId, $parsedExam->questions);

        $successMessage = "Arquivo validado e salvo com sucesso. ID da prova: {$examId}";
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
    }
}

try {
    $credentialsPath = getenv('GOOGLE_APPLICATION_CREDENTIALS');

    if (is_string($credentialsPath) && $credentialsPath !== '') {
        $tokenService = new GoogleAccessTokenService($credentialsPath);
        $repository = new FirestoreRestRepository($tokenService);
        $exams = $repository->listExams();
    }
} catch (Throwable $e) {
    // Não bloqueia a página se a listagem falhar.
}

include __DIR__ . '/templates/home.php';