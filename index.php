<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spell Checker</title>
</head>
<body>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <label for="name">Nama:</label>
        <input type="text" name="name" id="name" required>
        <br>
        <label for="class">Kelas:</label>
        <input type="text" name="class" id="class" required>
        <br>
        <label for="files">Upload PDF or Word file (max 5):</label>
        <input type="file" name="files[]" id="files" accept=".pdf,.doc,.docx" multiple>
        <button type="submit">Upload</button>
    </form>
    <br>
    <a href="view_uploads.php">View Uploaded Data</a>
</body>
</html>
