<?php
// Include the database connection
require_once 'connector.php';

// Function to get table structure and data
function getTableData($conn, $tableName) {
    try {
        // Get column information
        $stmt = $conn->prepare("DESCRIBE $tableName");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all data from the table
        $stmt = $conn->prepare("SELECT * FROM $tableName");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'columns' => $columns,
            'rows' => $rows
        ];
    } catch(PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

// Function to convert to 1NF (First Normal Form)
function convertTo1NF($data) {
    $result = [];
    
    // Copy column structure
    $result['columns'] = $data['columns'];
    
    // For 1NF, we need to:
    // 1. Ensure atomic values (no multi-valued attributes)
    // 2. Identify a primary key
    // 3. No repeating groups
    
    $rows1NF = [];
    foreach($data['rows'] as $row) {
        $newRow = $row;
        
        // Split multi-valued attributes (like comma-separated values)
        if(isset($row['laptop_brands'])) {
            $brands = explode(", ", $row['laptop_brands']);
            
            // Create individual rows for each brand
            foreach($brands as $brand) {
                $singleRow = $row;
                $singleRow['laptop_brands'] = trim($brand);
                $rows1NF[] = $singleRow;
            }
        } else {
            $rows1NF[] = $newRow;
        }
    }
    
    $result['rows'] = $rows1NF;
    $result['explanation'] = "1NF ensures atomic values with no repeating groups. Each laptop brand is now in a separate row.";
    
    return $result;
}

// Function to convert to 2NF (Second Normal Form)
function convertTo2NF($data) {
    // Start with 1NF data
    $data1NF = convertTo1NF($data);
    
    // For 2NF:
    // 1. Table must be in 1NF
    // 2. All non-key attributes must depend on the entire primary key
    
    // Create new tables
    $repairsTable = [
        'columns' => [
            ['Field' => 'id', 'Type' => 'int', 'Key' => 'PRI'],
            ['Field' => 'username', 'Type' => 'varchar(255)'],
            ['Field' => 'submit_date', 'Type' => 'datetime']
        ],
        'rows' => []
    ];
    
    $brandsTable = [
        'columns' => [
            ['Field' => 'repair_id', 'Type' => 'int', 'Key' => 'PRI'],
            ['Field' => 'brand', 'Type' => 'varchar(255)', 'Key' => 'PRI']
        ],
        'rows' => []
    ];
    
    // Populate the tables
    $processedRepairIds = [];
    
    foreach($data1NF['rows'] as $row) {
        // Only add each repair record once
        if(!in_array($row['id'], $processedRepairIds)) {
            $repairsTable['rows'][] = [
                'id' => $row['id'],
                'username' => $row['username'],
                'submit_date' => $row['submit_date']
            ];
            $processedRepairIds[] = $row['id'];
        }
        
        // Add brand record
        $brandsTable['rows'][] = [
            'repair_id' => $row['id'],
            'brand' => $row['laptop_brands']
        ];
    }
    
    return [
        'repairs' => $repairsTable,
        'brands' => $brandsTable,
        'explanation' => "2NF removes partial dependencies by separating the main repair records from the laptop brands."
    ];
}

// Function to convert to 3NF (Third Normal Form)
function convertTo3NF($data) {
    // Start with 2NF
    $data2NF = convertTo2NF($data);
    
    // For 3NF:
    // 1. Table must be in 2NF
    // 2. No transitive dependencies (non-key attributes depending on other non-key attributes)
    
    // Let's create a users table to remove transitive dependencies
    $usersTable = [
        'columns' => [
            ['Field' => 'username', 'Type' => 'varchar(255)', 'Key' => 'PRI'],
            ['Field' => 'user_id', 'Type' => 'int', 'Key' => '']
        ],
        'rows' => []
    ];
    
    // Modify repairs table to reference username instead of storing it
    $repairsTable = $data2NF['repairs'];
    $updatedRepairsRows = [];
    $processedUsernames = [];
    $userCounter = 1;
    
    foreach($repairsTable['rows'] as $row) {
        $username = $row['username'];
        
        // Add user if not already processed
        if(!in_array($username, $processedUsernames)) {
            $usersTable['rows'][] = [
                'username' => $username,
                'user_id' => $userCounter
            ];
            $processedUsernames[] = $username;
            $userCounter++;
        }
        
        // Find the user_id for this username
        $userId = array_search($username, array_column($usersTable['rows'], 'username'));
        $userId = $usersTable['rows'][$userId]['user_id'];
        
        // Update repair record to use user_id instead of username
        $row['user_id'] = $userId;
        unset($row['username']); // Remove the username field
        $updatedRepairsRows[] = $row;
    }
    
    // Update repairs table columns
    $repairsNewColumns = [
        ['Field' => 'id', 'Type' => 'int', 'Key' => 'PRI'],
        ['Field' => 'user_id', 'Type' => 'int', 'Key' => 'FK'], // Foreign key to users
        ['Field' => 'submit_date', 'Type' => 'datetime']
    ];
    
    return [
        'users' => $usersTable,
        'repairs' => [
            'columns' => $repairsNewColumns,
            'rows' => $updatedRepairsRows
        ],
        'brands' => $data2NF['brands'],
        'explanation' => "3NF removes transitive dependencies by creating a separate users table."
    ];
}

// Function to render a table HTML
function renderTable($tableData, $tableName) {
    $html = "<div class='normalized-table'>";
    $html .= "<h3>Table: $tableName</h3>";
    
    if(isset($tableData['error'])) {
        $html .= "<div class='error'>" . $tableData['error'] . "</div>";
        return $html . "</div>";
    }
    
    // Table headers
    $html .= "<table class='data-table'><thead><tr>";
    foreach($tableData['columns'] as $column) {
        $keyInfo = '';
        if(isset($column['Key']) && $column['Key']) {
            $keyInfo = " (" . $column['Key'] . ")";
        }
        $html .= "<th>" . $column['Field'] . $keyInfo . "</th>";
    }
    $html .= "</tr></thead><tbody>";
    
    // Table rows
    if(count($tableData['rows']) > 0) {
        foreach($tableData['rows'] as $row) {
            $html .= "<tr>";
            foreach($tableData['columns'] as $column) {
                $fieldName = $column['Field'];
                $value = isset($row[$fieldName]) ? $row[$fieldName] : '';
                $html .= "<td>" . htmlspecialchars($value) . "</td>";
            }
            $html .= "</tr>";
        }
    } else {
        $html .= "<tr><td colspan='" . count($tableData['columns']) . "'>No data</td></tr>";
    }
    
    $html .= "</tbody></table></div>";
    return $html;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Normalization Tool</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background-image: url('imgs.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: white;
        }
        .container {
            background: rgba(34, 34, 34, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(1, 19, 7, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.18);
            max-width: 1200px;
            margin: 0 auto;
        }
        h2 {
            text-align: center;
            font-size: 32px;
            margin-bottom: 30px;
            font-weight: 600;
            color: #00ff00;
            text-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        }
        h3 {
            color: #00ff00;
            margin-top: 25px;
            margin-bottom: 15px;
            border-bottom: 1px solid rgba(0, 255, 0, 0.3);
            padding-bottom: 8px;
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-header img {
            width: 80px;
            margin-bottom: 10px;
            filter: drop-shadow(0 0 8px rgba(0, 255, 0, 0.7));
        }
        .buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        .normalize-btn {
            padding: 12px 25px;
            background: linear-gradient(to right, rgb(4, 221, 55), rgb(10, 65, 22));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .normalize-btn:hover {
            background: linear-gradient(to right, #218838, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .normalize-btn.active {
            background: linear-gradient(to right, #085214, #063f0d);
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.5);
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            background-color: rgba(0, 0, 0, 0.4);
        }
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .data-table th {
            background-color: rgba(0, 128, 0, 0.5);
            color: white;
            font-weight: 600;
        }
        .data-table tr:hover {
            background-color: rgba(0, 255, 0, 0.1);
        }
        .explanation {
            background-color: rgba(0, 128, 0, 0.2);
            border-left: 4px solid #00ff00;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(to right, rgb(4, 221, 55), rgb(10, 65, 22));
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            background: linear-gradient(to right, #218838, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .normalized-table {
            margin-bottom: 30px;
        }
        #normalization-results {
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        #normalization-results.show {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <img src="https://cdn-icons-png.flaticon.com/512/3659/3659899.png" alt="Database Normalization Logo">
            <h2>Database Normalization Tool</h2>
        </div>
        
        <div class="buttons">
            <button id="btn-original" class="normalize-btn active">Original Data</button>
            <button id="btn-1nf" class="normalize-btn">Convert to 1NF</button>
            <button id="btn-2nf" class="normalize-btn">Convert to 2NF</button>
            <button id="btn-3nf" class="normalize-btn">Convert to 3NF</button>
        </div>
        
        <div id="normalization-results">
            <?php
            // Get original table data
            $tableData = getTableData($conn, 'repairs');
            echo "<div id='original-data'>";
            echo "<h3>Original Data Structure</h3>";
            echo renderTable($tableData, 'repairs');
            echo "<div class='explanation'>The original table before normalization. Note that laptop_brands can contain multiple values.</div>";
            echo "</div>";
            
            // Prepare the normalized versions but don't display them yet
            echo "<div id='1nf-data' style='display:none;'>";
            echo "<h3>First Normal Form (1NF)</h3>";
            $data1NF = convertTo1NF($tableData);
            echo renderTable($data1NF, 'repairs_1nf');
            echo "<div class='explanation'>" . $data1NF['explanation'] . "</div>";
            echo "</div>";
            
            echo "<div id='2nf-data' style='display:none;'>";
            echo "<h3>Second Normal Form (2NF)</h3>";
            $data2NF = convertTo2NF($tableData);
            echo renderTable($data2NF['repairs'], 'repairs_2nf');
            echo renderTable($data2NF['brands'], 'laptop_brands_2nf');
            echo "<div class='explanation'>" . $data2NF['explanation'] . "</div>";
            echo "</div>";
            
            echo "<div id='3nf-data' style='display:none;'>";
            echo "<h3>Third Normal Form (3NF)</h3>";
            $data3NF = convertTo3NF($tableData);
            echo renderTable($data3NF['users'], 'users_3nf');
            echo renderTable($data3NF['repairs'], 'repairs_3nf');
            echo renderTable($data3NF['brands'], 'laptop_brands_3nf');
            echo "<div class='explanation'>" . $data3NF['explanation'] . "</div>";
            echo "</div>";
            ?>
        </div>
        
        <div class="buttons">
            <a href="orderform.php" class="back-btn">Back to Repair Form</a>
            <a href="view_orders.php" class="back-btn">View All Orders</a>
        </div>
    </div>
    
    <script>
        // JavaScript to handle button clicks and display the appropriate normalization
        document.addEventListener('DOMContentLoaded', function() {
            const originalBtn = document.getElementById('btn-original');
            const nf1Btn = document.getElementById('btn-1nf');
            const nf2Btn = document.getElementById('btn-2nf');
            const nf3Btn = document.getElementById('btn-3nf');
            
            const originalData = document.getElementById('original-data');
            const nf1Data = document.getElementById('1nf-data');
            const nf2Data = document.getElementById('2nf-data');
            const nf3Data = document.getElementById('3nf-data');
            
            const results = document.getElementById('normalization-results');
            
            // Show original data by default
            results.classList.add('show');
            
            // Function to reset all buttons and hide all data
            function resetAll() {
                originalBtn.classList.remove('active');
                nf1Btn.classList.remove('active');
                nf2Btn.classList.remove('active');
                nf3Btn.classList.remove('active');
                
                originalData.style.display = 'none';
                nf1Data.style.display = 'none';
                nf2Data.style.display = 'none';
                nf3Data.style.display = 'none';
            }
            
            // Event listeners for buttons
            originalBtn.addEventListener('click', function() {
                resetAll();
                originalBtn.classList.add('active');
                originalData.style.display = 'block';
            });
            
            nf1Btn.addEventListener('click', function() {
                resetAll();
                nf1Btn.classList.add('active');
                nf1Data.style.display = 'block';
            });
            
            nf2Btn.addEventListener('click', function() {
                resetAll();
                nf2Btn.classList.add('active');
                nf2Data.style.display = 'block';
            });
            
            nf3Btn.addEventListener('click', function() {
                resetAll();
                nf3Btn.classList.add('active');
                nf3Data.style.display = 'block';
            });
        });
    </script>
</body>
</html>