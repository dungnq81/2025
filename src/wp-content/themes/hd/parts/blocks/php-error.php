<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Error</title>
    <style>
        body {
            font-family: sans-serif;
            text-align: center;
            margin: 100px;
        }

        h1 {
            font-size: 2em;
            color: #d00;
        }
    </style>
</head>
<body>
<div class="php-version-error">
    <h1>Error</h1>
    <p><?php echo isset( $args['error_message'] ) ? esc_html( $args['error_message'] ) : 'An error occurred.'; ?></p>
</div>
</body>
</html>
