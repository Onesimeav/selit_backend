<!DOCTYPE html>
<html>
<head>
    <title>Laravel Mail</title>
</head>
<body>
    <h1>We have received your request to reset your account password</h1>

    <p>You can use the following code to recover your account:</p>
    <p>{{ $code }}</p>
    <p>The allowed duration of the code is one hour from the time the message was sent</p>

</body>
</html>
