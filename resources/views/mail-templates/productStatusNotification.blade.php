<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notifData['title'] }}</title>
</head>
<body>
    <p>{{ $notifData['message'] }}</p>
    <p>You can view more details <a href="{{ $notifData['link'] }}">here</a>.</p>
</body>
</html>
