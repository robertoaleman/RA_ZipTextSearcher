<?php

/**
 * Class RA_ZipTextSearcher by Roberto Aleman,ventics.com
 *
 * This class provides functionality to search for a specific string within text-based files inside a ZIP archive using native PHP functions.
 * It offers features such as:
 * - Reading and processing files within a ZIP archive.
 * - Searching for a given string within the content of these files, line by line.
 * - Returning an array of files and the lines where the string was found.
 * - Calculating and reporting the size of the ZIP file.
 * - Measuring and reporting the total time taken for the search operation.
 * - Providing a summary report of the search process, including the number of files searched and the number of files with matches.
 *
 * The class assumes that the files within the ZIP archive are primarily text-based for optimal search results.
 */
class RAZipTextSearcher
{
    private string $zipFilePath;
    private int $zipFileSize;
    private float $startTime;
    private float $endTime;
    private int $filesSearched = 0;
    private int $filesWithMatch = 0;

    /**
     * Constructor of the class.
     *
     * Initializes a new RAZipTextSearcher instance with the path to the ZIP file.
     *
     * @param string $zipFilePath The path to the ZIP file.
     * @throws InvalidArgumentException If the ZIP file path is invalid or the file does not exist.
     */
    public function __construct(string $zipFilePath)
    {
        if (!is_readable($zipFilePath)) {
            throw new InvalidArgumentException("The ZIP file path is invalid or the file does not exist: " . $zipFilePath);
        }
        $this->zipFilePath = $zipFilePath;
        $this->zipFileSize = filesize($this->zipFilePath);
    }

    /**
     * Searches for a string within the ZIP file.
     *
     * This method opens the ZIP archive, iterates through each entry (file), reads its content line by line,
     * and checks if the provided search string exists in any of the lines. It keeps track of the start
     * and end time of the search, as well as the number of files processed and those containing the search string.
     *
     * @param string $searchText The string to search for. The string will be trimmed for leading/trailing spaces before searching.
     * @return array An associative array where the keys are the names of the files within the ZIP
     * that contain the search string, and the values are arrays of the lines (with line numbers) where the
     * string was found. Returns an empty array if the string is not found in any file.
     */
    public function searchInZip(string $searchText): array
    {
        $this->startTime = microtime(true);
        $results = [];
        $zip = new ZipArchive();
        $this->filesSearched = 0;
        $this->filesWithMatch = 0;
        $trimmedSearchText = trim($searchText);

        if ($zip->open($this->zipFilePath) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $this->filesSearched++;
                $entryName = $zip->getNameIndex($i);
                $stream = $zip->getStream($entryName);
                $foundInThisFile = false;

                if ($stream) {
                    $lineCount = 1;
                    while (!feof($stream)) {
                        $line = fgets($stream);
                        if (str_contains($line, $trimmedSearchText)) {
                            if (!isset($results[$entryName])) {
                                $results[$entryName] = [];
                                $this->filesWithMatch++;
                                $foundInThisFile = true;
                            }
                            $results[$entryName][] = "Line " . $lineCount . ": " . trim($line);
                        }
                        $lineCount++;
                    }
                    fclose($stream);
                }
            }
            $zip->close();
        } else {
            // Log an error if the ZIP file cannot be opened
            error_log("Error opening ZIP file: " . $this->zipFilePath);
        }

        $this->endTime = microtime(true);
        return $results;
    }

    /**
     * Generates and displays a report on the screen about the search process.
     * The report includes the ZIP file path, size, the total number of files searched,
     * the number of files where the search string was found, and the total search time.
     *
     * @return void
     */
    public function generateReport(): void
    {
        $searchTime = $this->endTime - $this->startTime;
        $fileSizeKB = round($this->zipFileSize / 1024, 2);

        echo "<h2>Search Report</h2>";
        echo "<p>ZIP File Path: " . $this->zipFilePath . "</p>";
        echo "<p>ZIP File Size: " . $fileSizeKB . " KB</p>";
        echo "<p>Total Files Searched: " . $this->filesSearched . "</p>";
        echo "<p>Files with Matches: " . $this->filesWithMatch . "</p>";
        echo "<p>Search Time: " . round($searchTime, 4) . " seconds</p>";
    }
}

// --- HTML Form and Processing ---

// Define the directory where ZIP files are located
$zipDirectory = 'zips/';

// Get a list of ZIP files in the specified directory
$zipFiles = glob($zipDirectory . '*.zip');

// Initialize variables for search string and results
$searchString = '';
$searchResults = [];
$zipSearcher = null;
$error = '';

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the search string and trim it
    $searchString = isset($_POST['search_text']) ? trim($_POST['search_text']) : '';

    // Get the selected ZIP file path
    $selectedZipFile = isset($_POST['zip_file']) ? $_POST['zip_file'] : '';

    if (!empty($searchString) && !empty($selectedZipFile) && in_array($selectedZipFile, $zipFiles)) {
        try {
            // Instantiate the RAZipTextSearcher class
            $zipSearcher = new RAZipTextSearcher($selectedZipFile);

            // Perform the search
            $searchResults = $zipSearcher->searchInZip($searchString);

        } catch (InvalidArgumentException $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Please enter a search string and select a ZIP file.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZIP File Text Search by Roberto Aleman, ventics.com</title>
    <style>
        body { font-family: sans-serif; }
        h2, h3, h4 { color: #333; }
        .report { margin-top: 20px; border: 1px solid #ccc; padding: 15px; }
        .error { color: red; font-weight: bold; }
        .results { margin-top: 15px; }
        .results h4 { margin-bottom: 5px; }
        .results p { margin-left: 20px; }


		.button {
		  border: none;
		  color: white;
		  padding: 8px 16px;
		  text-align: center;
		  text-decoration: none;
		  display: inline-block;
		  font-size: 16px;
		  margin: 4px 2px;
		  transition-duration: 0.4s;
		  cursor: pointer;
		}

		.button1 {
		  background-color: white;
		  color: black;
		  border: 2px solid #04AA6D;
		}

		.button1:hover {
		  background-color: #04AA6D;
		  color: white;
		}

		.button2 {
		  background-color: white;
		  color: black;
		  border: 2px solid #008CBA;
		}

		.button2:hover {
		  background-color: #008CBA;
		  color: white;
		}

</style>


</head>
<body style="    padding: 1em;">

    <h2>RA_ZipTextSearcher</a> by Roberto Aleman, <a href="ventics.com">ventics.com</a></h2>

   <div id="form" style="
    padding: 1em;
    border-radius: 5px;
    border: 1px solid #333;
    background: cornsilk;
"> <form method="post" action="">
        <div>
            <label for="search_text">Search String:</label>
            <input type="text" id="search_text" name="search_text" value="<?php echo htmlspecialchars($searchString); ?>">
        </div>
        <div style="margin-top: 10px;">
            <label for="zip_file">Select ZIP File:</label>
            <select id="zip_file" name="zip_file">
                <option value="">-- Select a ZIP File --</option>
                <?php foreach ($zipFiles as $zipFile): ?>
                    <option value="<?php echo htmlspecialchars($zipFile); ?>"><?php echo basename($zipFile); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="margin-top: 15px;">
            <button type="submit" class="button button1">Search</button>
            <br/><br/>
            <a href="RAZipTextSearcher.php">Another Search!</a>
        </div>
    </form></div>

    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($searchResults && !empty($searchString) && !empty($_POST['zip_file'])): ?>
        <div class="results">
            <h3>Search Results:</h3>
            <?php if (!empty($searchResults)): ?>
                <?php foreach ($searchResults as $fileName => $lines): ?>
                    <h4>- <?php echo htmlspecialchars($fileName); ?>:</h4>
                    <?php foreach ($lines as $line): ?>
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo htmlspecialchars($line); ?></p>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No matches found for '<?php echo htmlspecialchars($searchString); ?>' in the selected ZIP file.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($zipSearcher): ?>
        <div class="report">
            <?php $zipSearcher->generateReport(); ?>
        </div>
    <?php endif; ?>
<p>Author's Notes:
<ul>
<li>Please read the attached <a href="documentation.html">documentation.</a></li>
<li>My code can recursively search within the entire .zip for all text files it finds.</li>

<li>If you require further explanation, I can assist you based on my availability and at an hourly rate.</li>

<li>If you need to implement this version or an advanced and/or customized version of my code in your system, I can assist you based on my availability and at an hourly rate.</li>

<li>Please write to me and we'll discuss.</li>

<li>Do you need advice to implement an IT project, develop an algorithm to solve a real-world problem in your business, factory, or company?
Write me right now and I'll advise you.</li>

<li>My project works well for all types of plain text files, txt, source code files, among others. However, it may not work well for very large files, so an additional implementation may be required.
Please write to me and we'll talk.</li>

<li>For now it does not work with Office files, .doc, .docx, .ppt or .pptx, but I could expand it later.</li>
<h3>RA_ZipTextSearcher by Roberto Aleman, <a href="https://ventics.com">ventics.com</a></h3>
</body>
</html>