<?php
// Stone Pho Website - PHP Entry Point
// This file helps bypass hosting restrictions

// Check if index.html exists
if (file_exists('index.html')) {
    // Read and output the HTML file
    $html = file_get_contents('index.html');
    
    // Set proper headers
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: public, max-age=3600');
    
    // Output the HTML
    echo $html;
} else {
    // Fallback if index.html not found
    header('HTTP/1.1 404 Not Found');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Stone Pho - Loading...</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            text-align: center; 
            padding: 50px;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: rgba(255,255,255,0.1);
            padding: 2rem;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        h1 { font-size: 2.5rem; margin-bottom: 1rem; }
        p { font-size: 1.2rem; margin-bottom: 2rem; }
        .btn {
            background: white;
            color: #dc2626;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍜 Stone Pho</h1>
        <p>Website is being updated...</p>
        <p>Please contact us: <strong>(229) 491-9905</strong></p>
        <a href="tel:+12294919905" class="btn">📞 Call Now</a>
    </div>
</body>
</html>';
}
?>