<?php
// Mengatur tingkat pelaporan error untuk mengabaikan pesan deprecation
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');

require 'vendor/autoload.php';
require 'config.php'; // Include the database configuration

use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Cwin\BasicWord\WordProcessing\Source\Indonesia\WordFactoryIndonesia;
use Cwin\BasicWord\WordSpelling;
use Cwin\Component\Suggestion\Suggestion;

function getTextFromWordElement($element)
{
    $text = '';
    if (method_exists($element, 'getText')) {
        $text .= $element->getText() . ' ';
    } elseif (method_exists($element, 'getElements')) {
        foreach ($element->getElements() as $childElement) {
            $text .= getTextFromWordElement($childElement);
        }
    }
    return $text;
}

function getTextFromFile($filePath, $fileType)
{
    if ($fileType == 'pdf') {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($filePath);
        return $pdf->getText();
    } elseif ($fileType == 'docx') {
        $phpWord = WordIOFactory::load($filePath);
        $text = '';
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text .= getTextFromWordElement($element);
            }
        }
        return $text;
    }
    return '';
}

function isScientificTerm($word)
{
    $scientificTerms = [
        'DNA', 'RNA', 'COVID-19', 'Python', 'JavaScript', 'Laravel', 'HTML', 'CSS',
        'React', 'Vue', 'Angular', 'Node.js', 'Express.js',
        'PHP', 'SQL', 'NoSQL', 'MySQL', 'MongoDB',
        'algorithm', 'machine learning', 'data science',
        'neural network', 'artificial intelligence', 'API',
        'JSON', 'REST', 'GraphQL', 'DevOps', 'CI/CD',
        'Git', 'Docker', 'Kubernetes', 'Terraform',
    ];
    return in_array($word, $scientificTerms);
}

function loadEnglishWords($filePath)
{
    $words = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return array_flip(array_map('strtolower', $words));
}

function isEnglishWord($word, $englishWords)
{
    return isset($englishWords[strtolower($word)]);
}

function cleanText($text)
{
    $text = preg_replace('/"[^"]*"/', '', $text);
    $text = preg_replace('/[^a-zA-Z\s]/', '', $text);
    return $text;
}

$name = '';
$class = '';
$allFilesData = [];
$totalWordsAllFiles = 0;
$totalErrorsAllFiles = 0;
$totalErrorPercentageAllFiles = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $class = $_POST['class'];

    if (isset($_FILES['files']) && count($_FILES['files']['name']) <= 5) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($_FILES['files']['tmp_name'] as $index => $fileTmpPath) {
            $fileName = $_FILES['files']['name'][$index];
            $fileType = $_FILES['files']['type'][$index];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $fileDestination = $uploadDir . basename($fileName);

            if (move_uploaded_file($fileTmpPath, $fileDestination)) {
                $allowedfileExtensions = ['pdf', 'doc', 'docx'];
                if (in_array($fileExtension, $allowedfileExtensions)) {
                    $fileText = getTextFromFile($fileDestination, $fileExtension);

                    if ($fileText) {
                        $fileText = cleanText($fileText);
                        $englishWords = loadEnglishWords('./kamus/words.txt');
                        $wordSpelling = new WordSpelling(new WordFactoryIndonesia);
                        $suggestion = new Suggestion();

                        $cleanedText = cleanText($fileText);

                        $checkSpelling = $wordSpelling->checkSpelling($cleanedText);
                        $suggestion->setMaxListSuggestion(3);

                        $totalWords = str_word_count($cleanedText);
                        $errorCount = 0;
                        $suggestionsList = [];
                        $incorrectWords = [];

                        foreach ($checkSpelling->spellingResult() as $spelling) {
                            $word = $spelling->getWord();

                            if (preg_match('/^[A-Za-z]+$/', $word) && !ctype_upper($word) && !isScientificTerm($word) && !isEnglishWord($word, $englishWords)) {
                                if ($spelling->hasError()) {
                                    $errorCount++;
                                    $incorrectWords[] = $word;
                                    $suggestions = $suggestion->setSpelling($spelling)->suggest();
                                    $suggestionsList[] = [
                                        'word' => $word,
                                        'suggestions' => $suggestions
                                    ];
                                }
                            }
                        }

                        $errorPercentage = ($errorCount / $totalWords) * 100;

                        $fileData = [
                            'file_name' => $fileName,
                            'total_words' => $totalWords,
                            'error_count' => $errorCount,
                            'error_percentage' => $errorPercentage,
                            'suggestions' => $suggestionsList
                        ];
                        $allFilesData[] = $fileData;

                        $totalWordsAllFiles += $totalWords;
                        $totalErrorsAllFiles += $errorCount;
                    } else {
                        $fileData = [
                            'file_name' => $fileName,
                            'error' => 'Tidak dapat membaca konten file'
                        ];
                        $allFilesData[] = $fileData;
                    }
                } else {
                    $fileData = [
                        'file_name' => $fileName,
                        'error' => 'Jenis file tidak diperbolehkan'
                    ];
                    $allFilesData[] = $fileData;
                }
            } else {
                $fileData = [
                    'file_name' => $fileName,
                    'error' => 'Gagal mengunggah file'
                ];
                $allFilesData[] = $fileData;
            }
        }

        $totalErrorPercentageAllFiles = ($totalErrorsAllFiles / $totalWordsAllFiles) * 100;
        $summaryData = [
            'total_words' => $totalWordsAllFiles,
            'total_errors' => $totalErrorsAllFiles,
            'total_error_percentage' => $totalErrorPercentageAllFiles
        ];

        $stmt = $pdo->prepare("INSERT INTO uploads (name, class, files_data, summary_data) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $class, json_encode($allFilesData), json_encode($summaryData)]);
    }
}
?>

<div class="bg-white rounded-2xl shadow-sm p-10">
    <h1 class="font-semibold">Document Analysis Result</h1>
    <div class="mt-4 flow-root rounded-lg border border-gray-100 py-3 shadow-sm">
        <dl class="-my-3 divide-y divide-gray-100 text-sm">
            <div class="grid grid-cols-1 gap-1 p-3 sm:grid-cols-3 sm:gap-4">
                <dt class="font-medium text-gray-900">Name</dt>
                <dd class="text-gray-700 sm:col-span-2"><?= htmlspecialchars($name) ?></dd>
            </div>

            <div class="grid grid-cols-1 gap-1 p-3 sm:grid-cols-3 sm:gap-4">
                <dt class="font-medium text-gray-900">Class</dt>
                <dd class="text-gray-700 sm:col-span-2"><?= htmlspecialchars($class) ?></dd>
            </div>
            <?php foreach ($allFilesData as $fileData) : ?>
                <div class="grid grid-cols-1 gap-1 p-3 sm:grid-cols-3 sm:gap-4">
                    <dt class="font-medium text-gray-900">File</dt>
                    <dd class="text-gray-700 sm:col-span-2"><?= htmlspecialchars($fileData['file_name']) ?></dd>
                </div>
                <?php if (isset($fileData['error'])) : ?>
                    <p>Error: <?= htmlspecialchars($fileData['error']) ?></p>
                <?php else : ?>
                    <div class="grid grid-cols-1 gap-1 p-3 sm:grid-cols-3 sm:gap-4">
                        <dt class="font-medium text-gray-900">Persentase kesalahan</dt>
                        <dd class="text-gray-700 sm:col-span-2"><?= round($fileData['error_percentage'], 2) ?>%</dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 p-3 sm:grid-cols-3 sm:gap-4">
                        <dt class="font-medium text-gray-900">Jumlah kata yang berindikasi suggest</dt>
                        <dd class="text-gray-700 sm:col-span-2"><?= $fileData['error_count'] ?></dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 p-3 sm:grid-cols-3 sm:gap-4">
                        <dt class="font-medium text-gray-900">Kata yang salah</dt>
                        <dd class="text-gray-700 sm:col-span-2 text-justify">
                            <?= implode(', ', array_column($fileData['suggestions'], 'word')) ?>
                        </dd>
                    </div>
                    <?php if ($fileData['error_count'] > 0) : ?>
                        <div class="grid grid-cols-1 gap-1 p-3 sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-gray-900">Saran Koreksi</dt>
                            <dd class="text-gray-700 sm:col-span-2">
                                <ul>
                                    <?php foreach ($fileData['suggestions'] as $item) : ?>
                                        <li>Kata: <?= htmlspecialchars($item['word']) ?> - Saran: <?= implode(', ', $item['suggestions']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </dd>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($totalWordsAllFiles > 0) : ?>
                <div class="grid grid-cols-1 gap-1 p-3 sm:grid-cols-3 sm:gap-4">
                    <dt class="font-medium text-gray-900">Total kata dari semua file</dt>
                    <dd class="text-gray-700 sm:col-span-2"><?= $totalWordsAllFiles ?></dd>
                </div>
                <div class="grid grid-cols-1 gap-1 p-3 sm:grid-cols-3 sm:gap-4">
                    <dt class="font-medium text-gray-900">Total kesalahan kata dari semua file</dt>
                    <dd class="text-gray-700 sm:col-span-2"><?= $totalErrorsAllFiles ?></dd>
                </div>
                <div class="grid grid-cols-1 gap-1 p-3 sm:grid-cols-3 sm:gap-4">
                    <dt class="font-medium text-gray-900">Total persentase kesalahan dari semua file</dt>
                    <dd class="text-gray-700 sm:col-span-2"><?= round($totalErrorPercentageAllFiles, 2) ?>%</dd>
                </div>
            <?php endif; ?>
        </dl>
    </div>

</div>