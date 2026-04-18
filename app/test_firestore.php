<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\CsvExamParser;
use App\GoogleAccessTokenService;
use App\FirestoreRestRepository;

$parser = new CsvExamParser();
$credentialsPath = getenv('GOOGLE_APPLICATION_CREDENTIALS');

$tokenService = new GoogleAccessTokenService($credentialsPath);
$repository = new FirestoreRestRepository($tokenService);

try {
    $exam = $parser->parse(__DIR__ . '/sample/valid_exam.csv');

    $examId = $repository->saveExam($exam, 'local-test/valid_exam.csv');
    $repository->saveQuestions($examId, $exam->questions);

    echo "Prova salva com sucesso no Firestore.\n";
    echo "Exam ID: {$examId}\n";
} catch (Throwable $e) {
    echo "Erro:\n";
    echo $e->getMessage() . "\n";
}