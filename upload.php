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

function getTextFromWordElement($element) {
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

function getTextFromFile($filePath, $fileType) {
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

function isScientificTerm($word) {
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

function loadEnglishWords($filePath) {
    $words = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return array_flip(array_map('strtolower', $words));
}

function isEnglishWord($word, $englishWords) {
    return isset($englishWords[strtolower($word)]);
}

function cleanText($text) {
    $text = preg_replace('/"[^"]*"/', '', $text);
    $text = preg_replace('/[^a-zA-Z\s]/', '', $text);
    return $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $class = $_POST['class'];

    if (isset($_FILES['files']) && count($_FILES['files']['name']) <= 5) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $totalWordsAllFiles = 0;
        $totalErrorsAllFiles = 0;
        $allFilesData = [];

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

                        echo '<div class="summary">';
                        echo '<p>File: ' . $fileName . '</p>';
                        echo '<p>Persentase kesalahan: ' . round($errorPercentage, 2) . '%</p>';
                        echo '<p>Jumlah kata yang berindikasi suggest: ' . $errorCount . '</p>';
                        echo '<p>Kata yang salah: ' . implode(', ', $incorrectWords) . '</p>';

                        if ($errorCount > 0) {
                            echo '<h3>Saran Koreksi:</h3>';
                            echo '<ul>';
                            foreach ($suggestionsList as $item) {
                                echo '<li>Kata: ' . $item['word'] . ' - Saran: ' . implode(', ', $item['suggestions']) . '</li>';
                            }
                            echo '</ul>';
                        }

                        echo '</div>';
                    } else {
                        echo 'Tidak dapat membaca konten file: ' . $fileName;
                    }
                } else {
                    echo 'Jenis file tidak diperbolehkan untuk file: ' . $fileName;
                }
            } else {
                echo 'Gagal mengunggah file: ' . $fileName;
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

        echo '<div class="summary">';
        echo '<p>Total kata dari semua file: ' . $totalWordsAllFiles . '</p>';
        echo '<p>Total kesalahan kata dari semua file: ' . $totalErrorsAllFiles . '</p>';
        echo '<p>Total persentase kesalahan dari semua file: ' . round($totalErrorPercentageAllFiles, 2) . '%</p>';
        echo '</div>';
    } else {
        echo 'Jumlah file yang diunggah tidak boleh lebih dari 5.';
    }
}
?>
