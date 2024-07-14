<?php
// Mengatur tingkat pelaporan error untuk mengabaikan pesan deprecation
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');

require 'vendor/autoload.php';
require 'config.php'; // Include the database configuration

try {
    // Fetch all uploads data from the database
    $stmt = $pdo->query("SELECT * FROM uploads");
    $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo 'Failed to retrieve data: ' . $e->getMessage();
    die();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uploaded Files Data</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 15px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Uploaded Files Data</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Class</th>
                    <th>File Data</th>
                    <th>Summary Data</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($uploads as $upload): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($upload['id']); ?></td>
                        <td><?php echo htmlspecialchars($upload['name']); ?></td>
                        <td><?php echo htmlspecialchars($upload['class']); ?></td>
                        <td>
                            <ul>
                                <?php 
                                $filesData = json_decode($upload['files_data'], true);
                                foreach ($filesData as $file): ?>
                                    <li>
                                        <strong>File Name:</strong> <?php echo htmlspecialchars($file['file_name']); ?><br>
                                        <strong>Total Words:</strong> <?php echo htmlspecialchars($file['total_words']); ?><br>
                                        <strong>Error Count:</strong> <?php echo htmlspecialchars($file['error_count']); ?><br>
                                        <strong>Error Percentage:</strong> <?php echo htmlspecialchars(round($file['error_percentage'], 2)); ?>%<br>
                                        <strong>Suggestions:</strong>
                                        <ul>
                                            <?php foreach ($file['suggestions'] as $suggestion): ?>
                                                <li>
                                                    <strong>Word:</strong> <?php echo htmlspecialchars($suggestion['word']); ?><br>
                                                    <strong>Suggestions:</strong> <?php echo htmlspecialchars(implode(', ', $suggestion['suggestions'])); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                        <td>
                            <?php 
                            $summaryData = json_decode($upload['summary_data'], true); 
                            echo '<strong>Total Words:</strong> ' . htmlspecialchars($summaryData['total_words']) . '<br>';
                            echo '<strong>Total Errors:</strong> ' . htmlspecialchars($summaryData['total_errors']) . '<br>';
                            echo '<strong>Total Error Percentage:</strong> ' . htmlspecialchars(round($summaryData['total_error_percentage'], 2)) . '%';
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($upload['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
