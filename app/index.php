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

function getRepository(): FirestoreRestRepository
{
    $credentialsPath = getenv('GOOGLE_APPLICATION_CREDENTIALS');

    if (!is_string($credentialsPath) || $credentialsPath === '') {
        throw new ValidationException('Variável GOOGLE_APPLICATION_CREDENTIALS não configurada.');
    }

    $tokenService = new GoogleAccessTokenService($credentialsPath);

    return new FirestoreRestRepository($tokenService);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_exam') {
    try {
        $examIdToDelete = trim((string) ($_POST['exam_id'] ?? ''));

        if ($examIdToDelete === '') {
            throw new ValidationException('ID da prova não informado para exclusão.');
        }

        $repository = getRepository();
        $repository->deleteExam($examIdToDelete);

        $successMessage = 'Prova excluída com sucesso.';
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'delete_exam') {
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

        $repository = getRepository();

        $csvPath = 'upload/' . date('Y/m/d/') . basename($uploadedFileName);

        $examId = $repository->saveExam($parsedExam, $csvPath);
        $repository->saveQuestions($examId, $parsedExam->questions);

        $successMessage = "Arquivo validado e salvo com sucesso. ID da prova: {$examId}";
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
    }
}

try {
    $repository = getRepository();
    $exams = $repository->listExams();
} catch (Throwable $e) {
    // Não bloqueia a página se a listagem falhar.
}

include __DIR__ . '/templates/home.php';