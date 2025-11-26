<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting...</title>
    <meta http-equiv="refresh" content="0;url={{ url('/login') }}">
    <script>
        // Fallback redirect via JavaScript
        window.location.href = "{{ url('/login') }}";
    </script>
</head>
<body>
    <noscript>
        <p>Redirecting to login...</p>
        <p><a href="{{ url('/login') }}">Click here if you are not redirected automatically.</a></p>
    </noscript>
</body>
</html>
