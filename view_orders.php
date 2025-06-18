<!DOCTYPE html>
<html>
<head>
    <title>Laptop Repair Orders</title>
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
            max-width: 900px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: rgba(0, 0, 0, 0.4);
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        th {
            background-color: rgba(0, 128, 0, 0.5);
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background-color: rgba(0, 255, 0, 0.1);
        }
        .no-records {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #ccc;
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
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-header img {
            width: 80px;
            margin-bottom: 10px;
            filter: drop-shadow(0 0 8px rgba(0, 255, 0, 0.7));
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <img src="https://cdn-icons-png.flaticon.com/512/3659/3659899.png" alt="Repair Service Logo">
            <h2>Laptop Repair Orders</h2>
        </div>
        
        <?php
        // Include the database connection
        require_once 'connector.php';

        try {
            // Fetch all records
            $stmt = $conn->prepare("SELECT * FROM repairs ORDER BY submit_date DESC");
            $stmt->execute();
            
            // Get all results as an associative array
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($results) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Username</th><th>Laptop Brands</th><th>Submission Date</th></tr>";
                
                foreach($results as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['laptop_brands']) . "</td>";
                    echo "<td>" . $row['submit_date'] . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<div class='no-records'>No repair orders found in the database.</div>";
            }
        } catch(PDOException $e) {
            echo "<div class='no-records'>Error: " . $e->getMessage() . "</div>";
        }
        ?>
        
        <a href="orderform.php" class="back-btn">Submit New Repair</a>
        <a href="normalize.php" class="back-btn">Normalization Table</a>
    </div>
</body>
</html>