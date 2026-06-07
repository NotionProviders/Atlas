<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No preview</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #060a14;
            color: #94a3b8;
            font-family: system-ui, sans-serif;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <p>{{ $message ?? ($snapshotType->label().' snapshot has no data yet.') }}</p>
</body>
</html>
