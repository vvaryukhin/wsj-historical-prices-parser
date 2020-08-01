<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Document</title>
</head>

<body>

  <form action="./parser.php" enctype="multipart/form-data" method="POST">
    <input type="hidden" name="MAX_FILE_SIZE" value="300000" />
    <input type="file" name="names">
    <input type="date" name="startDate" value="<?= date('Y-m-d', time() - 60 * 60 * 24 * 360) ?>">
    <input type="date" name="endDate" value="<?= date('Y-m-d') ?>">
    <input type="submit">
  </form>

</body>

</html>
