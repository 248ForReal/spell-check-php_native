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

<div class="max-w-7xl bg-white rounded-2xl shadow-sm p-10 mx-auto">
  <h1 class="font-semibold text-lg">Result</h1>
  <table class="table p-4 bg-white shadow mt-6 w-full">
    <thead>
      <tr>
        <th class="border p-4 dark:border-dark-5 whitespace-nowrap font-normal text-gray-900">
          #
        </th>
        <th class="border p-4 dark:border-dark-5 whitespace-nowrap font-normal text-gray-900">
          Name
        </th>
        <th class="border p-4 dark:border-dark-5 whitespace-nowrap font-normal text-gray-900">
          Class
        </th>
        <th class="border p-4 dark:border-dark-5 whitespace-nowrap font-normal text-gray-900">
          File Data
        </th>
        <th class="border p-4 dark:border-dark-5 whitespace-nowrap font-normal text-gray-900">
          Summary Data
        </th>
        <th class="border p-4 dark:border-dark-5 whitespace-nowrap font-normal text-gray-900">
          Created At
        </th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($uploads as $upload) : ?>
        <tr class="text-gray-700">
          <td class="border p-4"><?php echo htmlspecialchars($upload['id']); ?></td>
          <td class="border p-4"><?php echo htmlspecialchars($upload['name']); ?></td>
          <td class="border p-4"><?php echo htmlspecialchars($upload['class']); ?></td>
          <td class="border p-4">
            <ul>
              <?php
              $filesData = json_decode($upload['files_data'], true);
              foreach ($filesData as $file) : ?>
                <li>
                  <strong>File Name:</strong> <?php echo htmlspecialchars($file['file_name']); ?><br>
                  <strong>Total Words:</strong> <?php echo htmlspecialchars($file['total_words']); ?><br>
                  <strong>Error Count:</strong> <?php echo htmlspecialchars($file['error_count']); ?><br>
                  <strong>Error Percentage:</strong> <?php echo htmlspecialchars(round($file['error_percentage'], 2)); ?>%<br>
                  <strong>Suggestions:</strong>
                  <ul>
                    <?php foreach ($file['suggestions'] as $suggestion) : ?>
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
          <td class="border p-4">
            <?php
            $summaryData = json_decode($upload['summary_data'], true);
            echo '<strong>Total Words:</strong> ' . htmlspecialchars($summaryData['total_words']) . '<br>';
            echo '<strong>Total Errors:</strong> ' . htmlspecialchars($summaryData['total_errors']) . '<br>';
            echo '<strong>Total Error Percentage:</strong> ' . htmlspecialchars(round($summaryData['total_error_percentage'], 2)) . '%';
            ?>
          </td>
          <td class="border p-4"><?php echo htmlspecialchars($upload['created_at']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

</div>