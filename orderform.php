<?php
// Include the database connection
require_once 'connector.php';

// Initialize variables
$user = "";
$laptop_brands = "";

try {
    // Check if form was submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validate and sanitize username
        if(isset($_POST["username"]) && !empty($_POST["username"])) {
            $user = htmlspecialchars($_POST["username"]);
        } else {
            throw new Exception("Username is required");
        }
        
        // Process laptop brands
        if(isset($_POST["laptop_brand"]) && is_array($_POST["laptop_brand"])) {
            // Join all selected brands with commas
            $laptop_brands = implode(", ", $_POST["laptop_brand"]);
        } else {
            throw new Exception("At least one laptop brand must be selected");
        }
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO repairs (username, laptop_brands) VALUES (:username, :laptop_brands)");
        $stmt->bindParam(':username', $user);
        $stmt->bindParam(':laptop_brands', $laptop_brands);
        $stmt->execute();
        
        // Redirect to view all records
        header("Location: view_orders.php");
        exit();
    }
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
} catch(Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Laptop Repair Order Form</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-image: url('imgs.jpg'); /* Circuit board background */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        .container {
            background: rgba(222, 219, 219, 0.44);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(1, 19, 7, 0.99);
            border: 1px solid rgba(255, 255, 255, 0.18);
            width: 400px;
            height: 600px;
            color: white;
            top: 0%;
        }
        h2 {
            text-align: center;
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: 600;
            text-shadow: 2px 3px 10px rgba(1, 35, 1, 0.84);
        }
        input{
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.05);
            color: white;
            box-sizing: border-box;
        }
        input::placeholder{
            color: white;
            text-shadow: 2px 3px 10px rgba(1, 35, 1, 0.84);

        }
        input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            background-color: rgba(19, 17, 17, 0.59);
            letter-spacing: 2px;
            color: #00ff00;
            text-shadow: 4px 7px 50px rgba(1, 13, 1, 0.84);

        }
        button {
            width: 100%;
            padding: 14px;
            margin-top: 20px;
            background: linear-gradient(to right,rgb(4, 221, 55),rgb(10, 65, 22));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        button:hover {
            background: linear-gradient(to right, #218838, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: 500;
            text-shadow: 2px 3px 10px rgba(1, 35, 1, 0.84);

        }
        .brand-selection {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin: 15px 0;
            cursor: pointer;
        }
        .brand-option {
            width: 30%;
            margin-bottom: 15px;
            text-align: center;
            cursor: pointer;
        }
        .brand-logo {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 10px;
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin: 0 auto;
            display: block;
            transition: all 0.3s ease;
            border: 3px solid transparent;
        }
        .brand-option input {
            display: none;
        }
        .selected {
            border: 4px solid #00ff00 !important;
            transform: scale(1.1);
            box-shadow: 0 0 45px rgba(2, 63, 16, 0.5);
        }
        .brand-name {
            margin-top: 5px;
            font-size: 14px;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
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
        /* Adding a glow effect for the glassmorphism container */
        .container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: rgba(0, 255, 0, 0.1);
            z-index: -1;
            filter: blur(40px);
            border-radius: 16px;
        }
        /* Add laptop issue field that was missing in original */
        #laptop_issue {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <img src="https://cdn-icons-png.flaticon.com/512/3659/3659899.png" alt="Repair Service Logo">
            <h2>Laptop Repair Form</h2>
        </div>
        <form action="orderform.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" placeholder="Enter your username" required>
            
            <label>Select Laptop Brand(s):</label>
            <div class="brand-selection">
                <div class="brand-option">
                    <input type="checkbox" id="hp" name="laptop_brand[]" value="HP">
                    <label for="hp">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/ad/HP_logo_2012.svg/1200px-HP_logo_2012.svg.png" class="brand-logo" alt="HP">
                        <div class="brand-name">HP</div>
                    </label>
                </div>
                
                <div class="brand-option">
                    <input type="checkbox" id="dell" name="laptop_brand[]" value="Dell">
                    <label for="dell">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/48/Dell_Logo.svg/1200px-Dell_Logo.svg.png" class="brand-logo" alt="Dell">
                        <div class="brand-name">Dell</div>
                    </label>
                </div>
                
                <div class="brand-option">
                    <input type="checkbox" id="lenovo" name="laptop_brand[]" value="Lenovo">
                    <label for="lenovo">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b8/Lenovo_logo_2015.svg/1200px-Lenovo_logo_2015.svg.png" class="brand-logo" alt="Lenovo">
                        <div class="brand-name">Lenovo</div>
                    </label>
                </div>
                
                <div class="brand-option">
                    <input type="checkbox" id="asus" name="laptop_brand[]" value="Asus">
                    <label for="asus">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2e/ASUS_Logo.svg/1280px-ASUS_Logo.svg.png" class="brand-logo" alt="Asus">
                        <div class="brand-name">Asus</div>
                    </label>
                </div>
                
                <div class="brand-option">
                    <input type="checkbox" id="acer" name="laptop_brand[]" value="Acer">
                    <label for="acer">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/00/Acer_2011.svg/1200px-Acer_2011.svg.png" class="brand-logo" alt="Acer">
                        <div class="brand-name">Acer</div>
                    </label>
                </div>
                
                <div class="brand-option">
                    <input type="checkbox" id="apple" name="laptop_brand[]" value="Apple">
                    <label for="apple">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Apple_logo_black.svg/1200px-Apple_logo_black.svg.png" class="brand-logo" alt="Apple">
                        <div class="brand-name">Apple</div>
                    </label>
                </div>
            </div>
            <button type="submit">Submit Repair Request</button>
        </form>
    </div>

    <script>
        // JavaScript to handle brand selection with click and double-click
        document.querySelectorAll('.brand-logo').forEach(logo => {
            // Track clicks for double-click detection
            let lastClickTime = 0;
            
            logo.addEventListener('click', function(event) {
                const currentTime = new Date().getTime();
                const timeDiff = currentTime - lastClickTime;
                lastClickTime = currentTime;
                
                // Find the associated checkbox input
                const checkboxInput = this.parentElement.previousElementSibling;
                
                // Double-click detection (if click happened within 300ms of previous click)
                if (timeDiff < 300) {
                    // Double-click - toggle off
                    checkboxInput.checked = false;
                    this.classList.remove('selected');
                } else {
                    // Single click - toggle on
                    checkboxInput.checked = true;
                    this.classList.add('selected');
                }
                
                // Prevent default behavior to avoid selection issues
                event.preventDefault();
            });
        });
    </script>
</body>
</html>